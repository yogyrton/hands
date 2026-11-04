<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasPeriodShortcuts;
use App\Filament\Resources\Visits\Widgets\MasterEarningsSummary;
use App\Models\Setting;
use App\Models\Visit;
use App\Support\Export\TimesheetWordExport;
use App\Support\Export\WorktimeExcelExport;
use App\Support\Export\WorktimeExportData;
use App\Support\WorktimeCalculator;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Учёт рабочего времени: выбор периода (по умолчанию текущий месяц) и карточки
 * мастеров с суммарным временем за этот период. Клик по мастеру открывает
 * подробную страницу с сохранённым периодом. Только для администратора.
 */
class WorktimeReport extends Page
{
    use HasPeriodShortcuts;

    protected string $view = 'filament.pages.worktime-report';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?int $navigationSort = 6;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Учёт';
    }

    public static function getNavigationLabel(): string
    {
        return 'Учёт рабочего времени';
    }

    public function getTitle(): string
    {
        return 'Учёт рабочего времени';
    }

    /**
     * Кнопки выгрузок. Период берётся из формы сверху — что на экране, то и в файле.
     *
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportTimesheet')
                ->label('Табель в Word')
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('gray')
                ->modalHeading('Табель учёта рабочего времени')
                ->modalDescription(fn (): string => 'Период: '.$this->periodLabel()
                    .'. В клетках — общее время: массаж плюс '.WorktimeCalculator::PREP_MINUTES.' мин подготовки кабинета на каждый визит.'
                    .$this->partialMonthWarning())
                ->modalSubmitActionLabel('Скачать')
                ->schema([
                    TextInput::make('hours_norm')
                        ->label('Норма часов за месяц')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(400)
                        ->required()
                        ->default(fn (): ?string => Setting::get('timesheet_hours_norm'))
                        ->helperText('Подставляется в колонку «Норма» всем мастерам. Значение запомнится до следующего раза'),
                ])
                ->action(fn (array $data) => $this->downloadTimesheet((int) $data['hours_norm'])),

            Action::make('exportSums')
                ->label('Суммы в Excel')
                ->icon(Heroicon::OutlinedTableCells)
                ->color('gray')
                ->modalHeading('Выгрузка сумм в Excel')
                ->modalDescription(fn (): string => 'Период: '.$this->periodLabel()
                    .'. На каждого мастера три колонки — наличные, безналичные, абонемент/сертификат.')
                ->modalSubmitActionLabel('Скачать')
                ->schema([
                    Placeholder::make('masters')
                        ->label('Попадут в файл')
                        ->content(fn (): string => $this->exportMastersLabel()),
                ])
                ->action(fn () => $this->downloadSums()),
        ];
    }

    /**
     * Выгрузка сумм по дням: столько колонок, сколько мастеров в периоде.
     */
    public function downloadSums(): ?StreamedResponse
    {
        $data = WorktimeExportData::for($this->from(), $this->until());

        if ($data->masters()->isEmpty()) {
            Notification::make()
                ->title('Некого выгружать')
                ->body('За выбранный период нет ни активных мастеров, ни посещений.')
                ->danger()
                ->send();

            return null;
        }

        $export = new WorktimeExcelExport($data);

        return $this->streamFile(
            $export->toTempFile(),
            $export->fileName(),
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );
    }

    /**
     * Табель в Word. Табель — документ за календарный месяц, поэтому период,
     * залезающий на соседний месяц, не выгружаем: часть дней просто не поместилась
     * бы в колонки 1–31.
     */
    public function downloadTimesheet(int $hoursNorm): ?StreamedResponse
    {
        if (! $this->from()->isSameMonth($this->until())) {
            Notification::make()
                ->title('Период больше одного месяца')
                ->body('Табель составляется за календарный месяц — выберите «Этот месяц» или «Пред. месяц».')
                ->danger()
                ->send();

            return null;
        }

        $data = WorktimeExportData::for($this->from(), $this->until());

        if ($data->masters()->isEmpty()) {
            Notification::make()
                ->title('Некого выгружать')
                ->body('За выбранный период нет ни активных мастеров, ни посещений.')
                ->danger()
                ->send();

            return null;
        }

        // Норму вводят каждый раз, но подставляем прошлую — чтобы не вспоминать.
        Setting::query()->updateOrCreate(['key' => 'timesheet_hours_norm'], ['value' => (string) $hoursNorm]);

        $export = new TimesheetWordExport($data, $hoursNorm);

        return $this->streamFile(
            $export->toTempFile(),
            $export->fileName(),
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        );
    }

    /**
     * Отдать временный файл на скачивание и сразу убрать его с диска.
     */
    private function streamFile(string $path, string $fileName, string $contentType): StreamedResponse
    {
        return response()->streamDownload(function () use ($path): void {
            readfile($path);
            @unlink($path);
        }, $fileName, ['Content-Type' => $contentType]);
    }

    /**
     * Кого увидим в файле — подпись в модалке, чтобы не скачивать вслепую.
     */
    public function exportMastersLabel(): string
    {
        $names = WorktimeExportData::for($this->from(), $this->until())
            ->masters()
            ->map(fn ($master): string => $master->shortName())
            ->all();

        return $names === [] ? 'нет мастеров за этот период' : implode(' · ', $names);
    }

    public function periodLabel(): string
    {
        return $this->from()->format('d.m.Y').' — '.$this->until()->format('d.m.Y');
    }

    /**
     * Предупреждение, если выбран не весь календарный месяц: по умолчанию период
     * идёт с 1-го числа по сегодня, и табель вышел бы неполным.
     */
    private function partialMonthWarning(): string
    {
        $from = $this->from();
        $until = $this->until();

        if (! $from->isSameMonth($until)) {
            return ' Период захватывает два месяца — табель составляется за календарный месяц.';
        }

        if ($from->day !== 1 || $until->toDateString() !== $until->copy()->endOfMonth()->toDateString()) {
            return ' Выбран не весь месяц — дни вне периода останутся пустыми. Для полного табеля нажмите «Этот месяц» или «Пред. месяц».';
        }

        return '';
    }

    public function mount(): void
    {
        // Период может прийти из query (например, при возврате с подробной страницы).
        $this->form->fill([
            'from' => request()->query('from') ?: now()->startOfMonth()->toDateString(),
            'until' => request()->query('until') ?: now()->toDateString(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Период')
                ->columns(2)
                ->schema([
                    DatePicker::make('from')->label('С')->live(),
                    DatePicker::make('until')->label('По')->live(),
                    $this->periodShortcuts(),
                ]),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    private function from(): Carbon
    {
        return Carbon::parse($this->data['from'] ?? now()->startOfMonth())->startOfDay();
    }

    private function until(): Carbon
    {
        return Carbon::parse($this->data['until'] ?? now())->endOfDay();
    }

    /**
     * Мастера с посещениями за выбранный период: деньги (нал/безнал/серт/итого)
     * и суммарное время. Считается тем же сводом, что и над списком посещений.
     *
     * @return array<int, array<string, mixed>>
     */
    public function mastersSummary(): array
    {
        $query = Visit::query()->whereBetween('performed_at', [$this->from(), $this->until()]);

        return MasterEarningsSummary::summarize($query)
            ->map(fn (object $r): array => [
                'id' => $r->id,
                'name' => $r->name,
                'visits' => $r->count,
                'cash' => $r->cash,
                'card' => $r->card,
                'cert' => $r->cert,
                'total' => $r->total,
                'massage_minutes' => $r->massage_minutes,
                'prep_minutes' => $r->prep_minutes,
                'total_minutes' => $r->total_minutes,
            ])
            ->all();
    }

    /**
     * Ссылка на подробную страницу мастера с сохранением выбранного периода.
     */
    public function detailUrl(int $masterId): string
    {
        return MasterWorktime::getUrl([
            'master' => $masterId,
            'from' => $this->data['from'] ?? null,
            'until' => $this->data['until'] ?? null,
        ]);
    }

    public function hm(int $minutes): string
    {
        return WorktimeCalculator::hm($minutes);
    }

    public function money(float $value): string
    {
        return MasterEarningsSummary::money($value);
    }

    public function visitsWord(int $count): string
    {
        return MasterEarningsSummary::visitsWord($count);
    }
}
