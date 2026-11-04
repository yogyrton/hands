<?php

declare(strict_types=1);

namespace App\Support\Export;

use App\Models\Master;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Выгрузка сумм по дням в Excel — тот же лист, который раньше заполнялся руками.
 *
 * На каждого мастера три колонки (наличные / безналичные / абонемент-сертификат),
 * справа дневные итоги и «Всего за день», внизу строки «Сумма» и «Итого»
 * с выручкой (нал + безнал, без сертификатов). Дневные итоги и суммы —
 * настоящие формулы, чтобы лист оставался живым и правился руками.
 */
class WorktimeExcelExport
{
    /** Колонок на одного мастера: наличные, безналичные, абонемент/сертификат. */
    private const COLS_PER_MASTER = 3;

    /**
     * Цвета блока каждого мастера: [шапка, строки дней]. Насыщенный тон сверху,
     * светлый под ним — ровно та раскраска, которой лист вёлся руками. Если
     * мастеров больше, чем пар цветов, палитра идёт по кругу.
     *
     * @var array<int, array{string, string}>
     */
    private const MASTER_COLORS = [
        ['DCA3BC', 'EAD1DC'],   // розовый
        ['93C47D', 'D9EAD3'],   // зелёный
        ['B5A3DE', 'D9D2E9'],   // сиреневый
        ['F1C232', 'FFF2CC'],   // жёлтый
        ['76A5AF', 'D0E0E3'],   // бирюзовый
        ['E69138', 'FCE5CD'],   // оранжевый
    ];

    /** Колонки дневных итогов: [шапка, строки дней]. */
    private const TOTAL_COLORS = ['4D90CB', 'CFE2F3'];

    /** Угловая ячейка «Дата» и строки «Сумма»/«Итого». */
    private const GRAY = '999999';

    /** Колонка с датами. */
    private const DATE_COLOR = 'CCCCCC';

    /** Высота строки, как в листе-образце. */
    private const ROW_HEIGHT = 15.75;

    /** Шрифт и кегль по всему листу — как в листе-образце. */
    private const FONT = 'Arial';

    private const FONT_SIZE = 10;

    public function __construct(private readonly WorktimeExportData $data) {}

    /**
     * Имя файла — просто «Октябрь 2026.xlsx»: табель и суммы лежат в разных
     * папках, так что различать их в имени незачем. Период в несколько месяцев
     * месяцем не назовёшь, поэтому там остаются даты.
     */
    public function fileName(): string
    {
        $from = $this->data->from();
        $until = $this->data->until();

        if ($from->isSameMonth($until)) {
            return MonthName::withYear($from).'.xlsx';
        }

        return $from->format('d.m.Y').'-'.$until->format('d.m.Y').'.xlsx';
    }

    /**
     * Собрать книгу и записать во временный файл; возвращает путь к нему.
     */
    public function toTempFile(): string
    {
        $masters = $this->data->masters();

        $rows = [];
        foreach ($this->data->days() as $day) {
            $values = [];
            foreach ($masters as $master) {
                $money = $this->data->moneyFor((int) $master->id, $day);
                $values[] = [
                    'cash' => $money['cash'],
                    'card' => $money['card'],
                    'cert' => $money['cert'],
                ];
            }

            $rows[] = ['date' => $day->toDateString(), 'values' => $values];
        }

        $path = tempnam(sys_get_temp_dir(), 'worktime-xlsx-').'.xlsx';

        self::writeSheet(
            $masters->map(fn (Master $m): string => $m->shortName())->values()->all(),
            $rows,
            $path,
        );

        return $path;
    }

    /**
     * Чистая запись листа — без обращения к БД, чтобы разметку можно было
     * проверять на синтетических данных.
     *
     * @param  array<int, string>  $masterNames  подписи колонок, по одной на мастера
     * @param  array<int, array{date: string, values: array<int, array{cash: float, card: float, cert: float}>}>  $rows
     *                                                                                                                   по строке на день; values идут в том же порядке, что и $masterNames
     */
    public static function writeSheet(array $masterNames, array $rows, string $path): void
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getDefaultStyle()->getFont()->setName(self::FONT)->setSize(self::FONT_SIZE);

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('табл.');

        $masterCount = count($masterNames);

        // Колонки дневных итогов идут сразу за блоками мастеров.
        $firstTotal = 2 + $masterCount * self::COLS_PER_MASTER;
        $colDayCash = $firstTotal;
        $colDayCard = $firstTotal + 1;
        $colDayCert = $firstTotal + 2;
        $colDayAll = $firstTotal + 3;
        $colNote = $firstTotal + 4;

        $firstDataRow = 2;
        $lastDataRow = $firstDataRow + count($rows) - 1;
        $sumRow = $lastDataRow + 1;
        $totalRow = $sumRow + 1;

        // --- шапка ---
        $sheet->setCellValue('A1', 'Дата');

        foreach ($masterNames as $i => $name) {
            $base = 2 + $i * self::COLS_PER_MASTER;
            $sheet->setCellValue([$base, 1], $name.' (н)');
            $sheet->setCellValue([$base + 1, 1], $name.' (б/н)');
            $sheet->setCellValue([$base + 2, 1], $name.' (а/с)');
        }

        $sheet->setCellValue([$colDayCash, 1], 'За день (н)');
        $sheet->setCellValue([$colDayCard, 1], 'За день (б/н)');
        $sheet->setCellValue([$colDayCert, 1], 'За день аб/с');
        $sheet->setCellValue([$colDayAll, 1], 'Всего за день');
        $sheet->setCellValue([$colNote, 1], 'Примечание');

        // --- дни ---
        foreach ($rows as $i => $row) {
            $r = $firstDataRow + $i;

            $sheet->setCellValue([1, $r], ExcelDate::PHPToExcel(new \DateTimeImmutable($row['date'])));
            $sheet->getStyle([1, $r, 1, $r])->getNumberFormat()->setFormatCode('dd.mm.yyyy');

            foreach ($row['values'] as $i2 => $money) {
                $base = 2 + $i2 * self::COLS_PER_MASTER;

                // Пустой день мастера остаётся пустой клеткой, а не нулём — как в бумажном листе.
                foreach ([0 => 'cash', 1 => 'card', 2 => 'cert'] as $offset => $key) {
                    if (round((float) $money[$key], 2) !== 0.0) {
                        $sheet->setCellValue([$base + $offset, $r], round((float) $money[$key], 2));
                    }
                }
            }

            // Дневные итоги — формулами по блокам мастеров, как в исходном листе.
            $sheet->setCellValue([$colDayCash, $r], self::sumOfOffset($masterCount, 0, $r));
            $sheet->setCellValue([$colDayCard, $r], self::sumOfOffset($masterCount, 1, $r));
            $sheet->setCellValue([$colDayCert, $r], self::sumOfOffset($masterCount, 2, $r));
            $sheet->setCellValue([$colDayAll, $r], '=SUM('
                .self::cell($colDayCash, $r).','.self::cell($colDayCard, $r).','.self::cell($colDayCert, $r).')');
        }

        // --- строка «Сумма»: столбцы целиком ---
        $sheet->setCellValue([1, $sumRow], 'Сумма');
        for ($col = 2; $col <= $colDayAll; $col++) {
            $letter = Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue([$col, $sumRow], '=SUM('.$letter.$firstDataRow.':'.$letter.$lastDataRow.')');
        }

        // --- строка «Итого»: итог каждого мастера за период + выручка деньгами ---
        $sheet->setCellValue([1, $totalRow], 'Итого');
        for ($i = 0; $i < $masterCount; $i++) {
            $base = 2 + $i * self::COLS_PER_MASTER;
            $from = Coordinate::stringFromColumnIndex($base).$firstDataRow;
            $to = Coordinate::stringFromColumnIndex($base + 2).$lastDataRow;
            // Итог пишем в третью колонку блока — так же, как в исходном листе.
            $sheet->setCellValue([$base + 2, $totalRow], '=SUM('.$from.':'.$to.')');
        }

        $sheet->setCellValue([$colDayCert, $totalRow], 'Выручка');
        $sheet->setCellValue([$colDayAll, $totalRow], '=SUM('
            .self::cell($colDayCash, $sumRow).','.self::cell($colDayCard, $sumRow).')');

        // --- оформление ---
        // Вся таблица: Arial 10, по центру, тонкая сетка — как в листе-образце.
        $table = [1, 1, $colNote, $totalRow];
        $sheet->getStyle($table)->getFont()->setName(self::FONT)->setSize(self::FONT_SIZE);
        $sheet->getStyle($table)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_BOTTOM);
        $sheet->getStyle($table)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        for ($row = 1; $row <= $totalRow; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(self::ROW_HEIGHT);
        }

        // Заливка по блокам: шапка насыщенная, дни под ней светлые.
        self::fill($sheet, [1, 1, 1, 1], self::GRAY);
        self::fill($sheet, [1, $firstDataRow, 1, $lastDataRow], self::DATE_COLOR);

        foreach ($masterNames as $i => $name) {
            $base = 2 + $i * self::COLS_PER_MASTER;
            $last = $base + self::COLS_PER_MASTER - 1;
            [$head, $body] = self::MASTER_COLORS[$i % count(self::MASTER_COLORS)];

            self::fill($sheet, [$base, 1, $last, 1], $head);
            self::fill($sheet, [$base, $firstDataRow, $last, $lastDataRow], $body);
        }

        self::fill($sheet, [$colDayCash, 1, $colDayAll, 1], self::TOTAL_COLORS[0]);
        self::fill($sheet, [$colDayCash, $firstDataRow, $colDayAll, $lastDataRow], self::TOTAL_COLORS[1]);

        // «Сумма» — серая целиком, а в «Итого» серые только заполненные клетки:
        // пустые между итогами мастеров остаются белыми, как в листе-образце.
        self::fill($sheet, [1, $sumRow, $colDayAll, $sumRow], self::GRAY);

        $totalCells = [1, $colDayCert, $colDayAll];
        for ($i = 0; $i < $masterCount; $i++) {
            $totalCells[] = 2 + $i * self::COLS_PER_MASTER + self::COLS_PER_MASTER - 1;
        }
        foreach ($totalCells as $col) {
            self::fill($sheet, [$col, $totalRow, $col, $totalRow], self::GRAY);
        }

        $sheet->getStyle([1, 1, $colNote, 1])->getFont()->setBold(true);
        $sheet->getStyle([1, $sumRow, $colNote, $totalRow])->getFont()->setBold(true);

        // Ширина — под самую длинную подпись, чтобы шапка не обрезалась. Внутри
        // тройки мастера колонки одинаковой ширины, иначе блок смотрится рвано.
        $sheet->getColumnDimension('A')->setWidth(11);

        foreach ($masterNames as $i => $name) {
            $base = 2 + $i * self::COLS_PER_MASTER;
            $width = 0;
            for ($col = $base; $col < $base + self::COLS_PER_MASTER; $col++) {
                $width = max($width, self::labelWidth($sheet, $col));
            }
            for ($col = $base; $col < $base + self::COLS_PER_MASTER; $col++) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setWidth($width);
            }
        }

        $totalsWidth = 0;
        foreach ([$colDayCash, $colDayCard, $colDayCert, $colDayAll] as $col) {
            $totalsWidth = max($totalsWidth, self::labelWidth($sheet, $col));
        }
        foreach ([$colDayCash, $colDayCard, $colDayCert, $colDayAll] as $col) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setWidth($totalsWidth);
        }

        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colNote))->setWidth(20);

        // Шапка и колонка дат не уезжают при прокрутке.
        $sheet->freezePane('B2');

        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
    }

    /**
     * Ширина колонки, при которой её подпись в шапке помещается целиком.
     */
    private static function labelWidth(Worksheet $sheet, int $col): float
    {
        return max(14, mb_strlen((string) $sheet->getCell([$col, 1])->getValue()) + 2);
    }

    /**
     * Залить диапазон [колонкаОт, строкаОт, колонкаДо, строкаДо] цветом.
     *
     * @param  array{int, int, int, int}  $range
     */
    private static function fill(Worksheet $sheet, array $range, string $rgb): void
    {
        $sheet->getStyle($range)
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB($rgb);
    }

    /**
     * Формула «=SUM(B2,E2,H2)» — одна и та же колонка внутри блока каждого мастера.
     */
    private static function sumOfOffset(int $masterCount, int $offset, int $row): string
    {
        if ($masterCount === 0) {
            return '0';
        }

        $cells = [];
        for ($i = 0; $i < $masterCount; $i++) {
            $cells[] = self::cell(2 + $i * self::COLS_PER_MASTER + $offset, $row);
        }

        return '=SUM('.implode(',', $cells).')';
    }

    private static function cell(int $col, int $row): string
    {
        return Coordinate::stringFromColumnIndex($col).$row;
    }
}
