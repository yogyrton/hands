<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EmploymentType;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Filament\Pages\WorktimeReport;
use App\Models\Master;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Models\Visit;
use App\Support\Export\TimesheetWordExport;
use App\Support\Export\WorktimeExcelExport;
use App\Support\Export\WorktimeExportData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Tests\TestCase;
use ZipArchive;

class WorktimeExportTest extends TestCase
{
    use RefreshDatabase;

    private function master(string $name, string $timesheetName, int $sort = 0, string $employment = 'main'): Master
    {
        return Master::create([
            'slug' => 'm-'.uniqid(), 'name' => $name, 'timesheet_name' => $timesheetName,
            'job_title' => 'массажист', 'employment_type' => $employment,
            'name_dative' => $name, 'role' => 'Массажист', 'yclients_url' => 'https://e.com',
            'bio1' => 'a', 'bio2' => 'b', 'salary_rate' => 35, 'sort_order' => $sort,
        ]);
    }

    private function service(): Service
    {
        return Service::create(['slug' => 's-'.uniqid(), 'name' => 'Классический массаж', 'level' => 4, 'lead' => 'l']);
    }

    private function visit(Master $m, Service $s, string $day, int $duration, float $price, PaymentType $type): void
    {
        Visit::create([
            'master_id' => $m->id, 'service_id' => $s->id, 'duration_minutes' => $duration,
            'base_price' => $price, 'service_price' => $price, 'paid_amount' => $price,
            'payment_type' => $type, 'performed_at' => Carbon::parse($day.' 12:00'),
        ]);
    }

    // --- формат времени в табеле ---

    public function test_timesheet_time_format(): void
    {
        // Ровные часы — без дробной части, иначе «часы/минуты» с ведущим нулём.
        $this->assertSame('3', TimesheetWordExport::hoursAndMinutes(180));
        $this->assertSame('3/05', TimesheetWordExport::hoursAndMinutes(185));
        $this->assertSame('0/55', TimesheetWordExport::hoursAndMinutes(55));
        $this->assertSame('1/15', TimesheetWordExport::hoursAndMinutes(75));
        // Не работал — пустая клетка, отдельный «0» по условным обозначениям не ставится.
        $this->assertSame('', TimesheetWordExport::hoursAndMinutes(0));
    }

    public function test_short_name_for_excel_is_surname_only(): void
    {
        $master = $this->master('Анна Петрова', 'Петрова А.А.');

        $this->assertSame('Петрова А.А.', $master->timesheetName());
        $this->assertSame('Петрова', $master->shortName());
    }

    public function test_timesheet_name_falls_back_to_site_name(): void
    {
        $master = Master::create([
            'slug' => 'm-'.uniqid(), 'name' => 'Анна Петрова', 'name_dative' => 'Анне',
            'role' => 'Массажист', 'yclients_url' => 'https://e.com', 'bio1' => 'a', 'bio2' => 'b',
        ]);

        $this->assertSame('Анна Петрова', $master->timesheetName());
    }

    // --- данные за период ---

    public function test_export_data_splits_money_and_time_by_day(): void
    {
        $anna = $this->master('Анна Петрова', 'Петрова А.А.');
        $service = $this->service();

        $this->visit($anna, $service, '2026-09-03', 60, 65, PaymentType::Cash);
        $this->visit($anna, $service, '2026-09-03', 90, 85, PaymentType::Card);
        $this->visit($anna, $service, '2026-09-10', 60, 70, PaymentType::Certificate);

        $data = WorktimeExportData::for(Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30'));

        $third = $data->moneyFor((int) $anna->id, Carbon::parse('2026-09-03'));
        $this->assertSame(65.0, $third['cash']);
        $this->assertSame(85.0, $third['card']);
        $this->assertSame(0.0, $third['cert']);

        $tenth = $data->moneyFor((int) $anna->id, Carbon::parse('2026-09-10'));
        $this->assertSame(70.0, $tenth['cert']);

        // 3 сентября: массаж 60+90 = 150, подготовка 15×2 = 30 → 180 мин.
        $this->assertSame(180, $data->minutesFor((int) $anna->id, Carbon::parse('2026-09-03')));
        // 10 сентября: 60 + 15 = 75 мин.
        $this->assertSame(75, $data->minutesFor((int) $anna->id, Carbon::parse('2026-09-10')));
        $this->assertSame(255, $data->minutesTotal((int) $anna->id));
        // Дни без посещений остаются пустыми.
        $this->assertSame(0, $data->minutesFor((int) $anna->id, Carbon::parse('2026-09-04')));
        $this->assertCount(30, $data->days());
    }

    public function test_export_includes_inactive_master_with_visits_in_period(): void
    {
        $active = $this->master('Анна Петрова', 'Петрова А.А.', 1);
        $fired = $this->master('Дмитрий Ерошенко', 'Ерошенко Д.С.', 2);
        $fired->update(['is_active' => false]);

        $this->visit($fired, $this->service(), '2026-09-05', 60, 65, PaymentType::Cash);

        $names = WorktimeExportData::for(Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30'))
            ->masters()->pluck('timesheet_name')->all();

        $this->assertSame(['Петрова А.А.', 'Ерошенко Д.С.'], $names);

        // В месяце без его посещений уволенного уже нет.
        $october = WorktimeExportData::for(Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'))
            ->masters()->pluck('timesheet_name')->all();

        $this->assertSame(['Петрова А.А.'], $october);
        $this->assertSame([$active->timesheet_name], $october);
    }

    // --- Excel ---

    public function test_excel_sheet_has_three_columns_per_master_and_totals(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'test-xlsx-').'.xlsx';

        WorktimeExcelExport::writeSheet(
            ['Петрова', 'Марков'],
            [
                ['date' => '2026-09-01', 'values' => [
                    ['cash' => 65.0, 'card' => 0.0, 'cert' => 0.0],
                    ['cash' => 0.0, 'card' => 125.0, 'cert' => 0.0],
                ]],
                ['date' => '2026-09-02', 'values' => [
                    ['cash' => 0.0, 'card' => 0.0, 'cert' => 55.0],
                    ['cash' => 0.0, 'card' => 0.0, 'cert' => 0.0],
                ]],
            ],
            $path,
        );

        $sheet = IOFactory::load($path)->getActiveSheet();

        // Две тройки колонок мастеров: B–D и E–G, дальше дневные итоги.
        $this->assertSame('Дата', $sheet->getCell('A1')->getValue());
        $this->assertSame('Петрова (н)', $sheet->getCell('B1')->getValue());
        $this->assertSame('Петрова (а/с)', $sheet->getCell('D1')->getValue());
        $this->assertSame('Марков (н)', $sheet->getCell('E1')->getValue());
        $this->assertSame('За день (н)', $sheet->getCell('H1')->getValue());
        $this->assertSame('Всего за день', $sheet->getCell('K1')->getValue());
        $this->assertSame('Примечание', $sheet->getCell('L1')->getValue());
        // Третьей тройки нет — колонок ровно по числу мастеров.
        $this->assertSame('', (string) $sheet->getCell('M1')->getValue());

        // Значения: пустой день мастера остаётся пустой клеткой.
        $this->assertSame(65.0, $sheet->getCell('B2')->getValue());
        $this->assertNull($sheet->getCell('C2')->getValue());
        $this->assertSame(125.0, $sheet->getCell('F2')->getValue());
        $this->assertSame(55.0, $sheet->getCell('D3')->getValue());

        // Дневные итоги — формулы по одноимённым колонкам всех мастеров.
        $this->assertSame('=SUM(B2,E2)', $sheet->getCell('H2')->getValue());
        $this->assertSame('=SUM(C2,F2)', $sheet->getCell('I2')->getValue());
        $this->assertSame('=SUM(D2,G2)', $sheet->getCell('J2')->getValue());
        $this->assertSame('=SUM(H2,I2,J2)', $sheet->getCell('K2')->getValue());

        // Строка «Сумма» и строка «Итого» с выручкой деньгами (без сертификатов).
        $this->assertSame('Сумма', $sheet->getCell('A4')->getValue());
        $this->assertSame('=SUM(B2:B3)', $sheet->getCell('B4')->getValue());
        $this->assertSame('Итого', $sheet->getCell('A5')->getValue());
        $this->assertSame('=SUM(B2:D3)', $sheet->getCell('D5')->getValue());
        $this->assertSame('=SUM(E2:G3)', $sheet->getCell('G5')->getValue());
        $this->assertSame('Выручка', $sheet->getCell('J5')->getValue());
        $this->assertSame('=SUM(H4,I4)', $sheet->getCell('K5')->getValue());

        @unlink($path);
    }

    public function test_excel_colors_each_master_block_separately(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'test-xlsx-').'.xlsx';

        WorktimeExcelExport::writeSheet(
            ['Петрова', 'Марков'],
            [['date' => '2026-09-01', 'values' => [
                ['cash' => 65.0, 'card' => 0.0, 'cert' => 0.0],
                ['cash' => 0.0, 'card' => 0.0, 'cert' => 0.0],
            ]]],
            $path,
        );

        $sheet = IOFactory::load($path)->getActiveSheet();
        $rgb = fn (string $cell): string => $sheet->getStyle($cell)->getFill()->getStartColor()->getRGB();

        // У каждого мастера свой цвет: шапка насыщенная, дни под ней светлые.
        $this->assertSame('DCA3BC', $rgb('B1'));
        $this->assertSame('EAD1DC', $rgb('D2'));
        $this->assertSame('93C47D', $rgb('E1'));
        $this->assertSame('D9EAD3', $rgb('G2'));
        // Дневные итоги — синим, колонка дат серая.
        $this->assertSame('4D90CB', $rgb('H1'));
        $this->assertSame('CFE2F3', $rgb('K2'));
        $this->assertSame('999999', $rgb('A1'));
        $this->assertSame('CCCCCC', $rgb('A2'));
        // «Сумма» и «Итого» серым, но колонка примечаний остаётся белой.
        $this->assertSame('999999', $rgb('A3'));
        $this->assertSame('999999', $rgb('K4'));
        $this->assertNotSame('999999', $rgb('L3'));

        // Сетка и выравнивание по всей таблице, кегль 10 — как в листе-образце.
        $this->assertSame(Border::BORDER_THIN, $sheet->getStyle('C2')->getBorders()->getTop()->getBorderStyle());
        $this->assertSame(Border::BORDER_THIN, $sheet->getStyle('L4')->getBorders()->getRight()->getBorderStyle());
        $this->assertSame(Alignment::HORIZONTAL_CENTER, $sheet->getStyle('C2')->getAlignment()->getHorizontal());
        $this->assertSame(10.0, (float) $sheet->getStyle('C2')->getFont()->getSize());
        $this->assertTrue($sheet->getStyle('B1')->getFont()->getBold());

        @unlink($path);
    }

    public function test_excel_file_name_uses_month_for_whole_month_period(): void
    {
        $month = new WorktimeExcelExport(
            WorktimeExportData::for(Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30')),
        );
        $this->assertSame('Суммы_сентябрь_2026.xlsx', $month->fileName());

        $across = new WorktimeExcelExport(
            WorktimeExportData::for(Carbon::parse('2026-09-20'), Carbon::parse('2026-10-05')),
        );
        $this->assertSame('Суммы_20.09.2026-05.10.2026.xlsx', $across->fileName());
    }

    // --- Word ---

    public function test_word_timesheet_is_filled_from_template(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'test-docx-').'.docx';

        TimesheetWordExport::render(
            TimesheetWordExport::templatePath(),
            $path,
            'за сентябрь 2026 г.',
            [
                'Петрова А.А. — массажист, основная работа',
                'Ерошенко Д.С. — массажист, совместительство',
            ],
            [
                ['fio' => 'Петрова А.А.', 'days' => [1 => '1/15', 4 => '3/05'], 'norm' => '88', 'hours' => '34/45'],
                ['fio' => 'Ерошенко Д.С.', 'days' => [2 => '0/55'], 'norm' => '88', 'hours' => '0/55'],
            ],
        );

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();

        // Шапка и строка состава: последняя строка закрывается точкой с запятой.
        $this->assertStringContainsString('за сентябрь 2026 г.', $xml);
        $this->assertStringContainsString('Петрова А.А. — массажист, основная работа,', $xml);
        $this->assertStringContainsString('Ерошенко Д.С. — массажист, совместительство;', $xml);

        // Строки таблицы и значения в клетках дней (у части прогонов есть
        // xml:space="preserve", поэтому сверяем по закрывающему тегу).
        $this->assertStringContainsString('>Петрова А.А.</w:t>', $xml);
        $this->assertStringContainsString('>Ерошенко Д.С.</w:t>', $xml);
        $this->assertStringContainsString('<w:t>3/05</w:t>', $xml);
        $this->assertStringContainsString('<w:t>0/55</w:t>', $xml);
        $this->assertStringContainsString('<w:t>34/45</w:t>', $xml);

        // Строка-образец из шаблона в готовый документ не попадает.
        $this->assertStringNotContainsString('{{FIO}}', $xml);
        $this->assertStringNotContainsString('{{PERIOD}}', $xml);
        $this->assertStringNotContainsString('{{STAFF}}', $xml);

        // Прочерки шапки заменены, а подпись внизу осталась как была.
        $this->assertStringNotContainsString('за _____________________', $xml);
        $this->assertStringContainsString('Е.В. Парусов', $xml);

        @unlink($path);
    }

    public function test_word_file_name_uses_month(): void
    {
        $export = new TimesheetWordExport(
            WorktimeExportData::for(Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30')),
            88,
        );

        $this->assertSame('Табель_сентябрь_2026.docx', $export->fileName());
    }

    // --- кнопки на странице ---

    public function test_page_downloads_excel_for_selected_period(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $anna = $this->master('Анна Петрова', 'Петрова А.А.');
        $this->visit($anna, $this->service(), '2026-09-03', 60, 65, PaymentType::Cash);

        Livewire::test(WorktimeReport::class)
            ->set('data.from', '2026-09-01')
            ->set('data.until', '2026-09-30')
            ->callAction('exportSums')
            ->assertFileDownloaded('Суммы_сентябрь_2026.xlsx');
    }

    public function test_page_downloads_word_and_remembers_hours_norm(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $anna = $this->master('Анна Петрова', 'Петрова А.А.');
        $this->visit($anna, $this->service(), '2026-09-03', 60, 65, PaymentType::Cash);

        Livewire::test(WorktimeReport::class)
            ->set('data.from', '2026-09-01')
            ->set('data.until', '2026-09-30')
            ->callAction('exportTimesheet', ['hours_norm' => 88])
            ->assertFileDownloaded('Табель_сентябрь_2026.docx');

        // Норму вводят руками каждый раз, но прошлое значение подставляется.
        $this->assertSame('88', Setting::get('timesheet_hours_norm'));
    }

    public function test_word_export_refuses_period_across_two_months(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $anna = $this->master('Анна Петрова', 'Петрова А.А.');
        $this->visit($anna, $this->service(), '2026-09-03', 60, 65, PaymentType::Cash);

        Livewire::test(WorktimeReport::class)
            ->set('data.from', '2026-09-20')
            ->set('data.until', '2026-10-05')
            ->callAction('exportTimesheet', ['hours_norm' => 88])
            ->assertNoFileDownloaded()
            ->assertNotified('Период больше одного месяца');
    }

    public function test_employment_type_labels_match_paper_timesheet(): void
    {
        $this->assertSame('основная работа', EmploymentType::Main->label());
        $this->assertSame('совместительство', EmploymentType::PartTime->label());
    }
}
