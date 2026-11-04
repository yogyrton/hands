<?php

declare(strict_types=1);

namespace App\Support\Export;

use App\Models\Master;
use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use ZipArchive;

/**
 * Табель учёта рабочего времени в Word.
 *
 * Собирается из готового шаблона resources/templates/timesheet.docx — копии
 * бумажного табеля, который раньше заполнялся от руки. Переписываем в нём
 * только содержимое (период, строку состава и строки таблицы), поэтому вёрстка,
 * шрифты, границы и альбомная ориентация остаются ровно такими же.
 *
 * Время в клетках — общее: массаж плюс 15 минут подготовки кабинета на каждый
 * визит. Формат по условным обозначениям табеля: «3» — ровно 3 часа,
 * «3/05» — 3 часа 5 минут, пустая клетка — в этот день не работал.
 */
class TimesheetWordExport
{
    private const NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    /** Кегль (в полукеглях) для чисел в клетках дней — «3/05» должно влезать в узкую колонку. */
    private const DAY_FONT_HALF_POINTS = 16;

    /** Кегль для колонок «Норма» и «Часы» — они шире, поэтому крупнее. */
    private const TOTAL_FONT_HALF_POINTS = 20;

    /** Колонок с днями в шаблоне: 1–31. */
    private const DAY_COLUMNS = 31;

    public function __construct(
        private readonly WorktimeExportData $data,
        private readonly int $hoursNorm,
    ) {}

    public function fileName(): string
    {
        $from = $this->data->from();

        return 'Табель_'.MonthName::nominative($from).'_'.$from->year.'.docx';
    }

    public static function templatePath(): string
    {
        return resource_path('templates/timesheet.docx');
    }

    /**
     * Собрать табель и записать во временный файл; возвращает путь к нему.
     */
    public function toTempFile(): string
    {
        $from = $this->data->from();
        $month = $from->month;

        $rows = [];
        $staffLines = [];

        foreach ($this->data->masters() as $master) {
            /** @var Master $master */
            $days = [];
            foreach ($this->data->days() as $day) {
                // Табель — за календарный месяц; дни другого месяца в колонки 1–31 не попадают.
                if ($day->month !== $month) {
                    continue;
                }

                $days[$day->day] = self::hoursAndMinutes($this->data->minutesFor((int) $master->id, $day));
            }

            $staffLines[] = $master->timesheetName().' — '.$master->job_title.', '.$master->employment_type->label();

            $rows[] = [
                'fio' => $master->timesheetName(),
                'days' => $days,
                'norm' => (string) $this->hoursNorm,
                'hours' => self::hoursAndMinutes($this->data->minutesTotal((int) $master->id)),
            ];
        }

        $path = tempnam(sys_get_temp_dir(), 'timesheet-docx-').'.docx';

        self::render(
            self::templatePath(),
            $path,
            'за '.MonthName::nominative($from).' '.$from->year.' г.',
            $staffLines,
            $rows,
        );

        return $path;
    }

    /**
     * Чистая сборка документа из шаблона — без обращения к БД, чтобы результат
     * можно было проверять на синтетических данных.
     *
     * @param  array<int, string>  $staffLines  строки состава, без завершающих запятых
     * @param  array<int, array{fio: string, days: array<int, string>, norm: string, hours: string}>  $rows
     */
    public static function render(string $templatePath, string $outPath, string $period, array $staffLines, array $rows): void
    {
        if (! is_file($templatePath)) {
            throw new RuntimeException('Не найден шаблон табеля: '.$templatePath);
        }

        if ($templatePath !== $outPath && ! copy($templatePath, $outPath)) {
            throw new RuntimeException('Не удалось подготовить файл табеля');
        }

        $zip = new ZipArchive;
        if ($zip->open($outPath) !== true) {
            throw new RuntimeException('Не удалось открыть шаблон табеля');
        }

        $xml = $zip->getFromName('word/document.xml');
        if ($xml === false) {
            $zip->close();
            throw new RuntimeException('Шаблон табеля повреждён: нет word/document.xml');
        }

        $zip->addFromString('word/document.xml', self::fill($xml, $period, $staffLines, $rows));
        $zip->close();
    }

    /**
     * Подстановка данных в XML документа.
     *
     * @param  array<int, string>  $staffLines
     * @param  array<int, array{fio: string, days: array<int, string>, norm: string, hours: string}>  $rows
     */
    private static function fill(string $xml, string $period, array $staffLines, array $rows): string
    {
        $dom = new DOMDocument;
        $dom->preserveWhiteSpace = true;
        $dom->loadXML($xml);

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::NS);

        self::setPlaceholderText(self::placeholderParagraph($xpath, '{{PERIOD}}'), $period);
        self::fillStaff($dom, $xpath, $staffLines);
        self::fillTable($dom, $xpath, $rows);

        return (string) $dom->saveXML();
    }

    /**
     * Строка состава: «Петрова А.А. — массажист, основная работа,» и так далее,
     * каждый мастер с новой строки, последняя строка — с точкой с запятой.
     *
     * @param  array<int, string>  $lines
     */
    private static function fillStaff(DOMDocument $dom, DOMXPath $xpath, array $lines): void
    {
        $paragraph = self::placeholderParagraph($xpath, '{{STAFF}}');

        // Прогон-образец несёт нужное оформление (кегль 14) — клонируем его на каждую строку.
        $sample = self::firstRun($paragraph);
        $sample->parentNode?->removeChild($sample);

        $lines = array_values($lines);
        $last = count($lines) - 1;

        foreach ($lines as $i => $line) {
            /** @var DOMElement $run */
            $run = $sample->cloneNode(true);
            $text = self::firstText($run);
            $text->nodeValue = '';
            $text->appendChild($dom->createTextNode($line.($i === $last ? ';' : ',')));

            // Перенос строки внутри абзаца — как в исходном табеле.
            if ($i > 0) {
                $run->insertBefore($dom->createElementNS(self::NS, 'w:br'), $text);
            }

            $paragraph->appendChild($run);
        }
    }

    /**
     * Строки таблицы: по одной на мастера, дни периода, норма и итог часов.
     *
     * @param  array<int, array{fio: string, days: array<int, string>, norm: string, hours: string}>  $rows
     */
    private static function fillTable(DOMDocument $dom, DOMXPath $xpath, array $rows): void
    {
        $table = $xpath->query('//w:tbl')->item(0);
        if (! $table instanceof DOMElement) {
            throw new RuntimeException('Шаблон табеля повреждён: нет таблицы');
        }

        $prototype = $xpath->query('./w:tr', $table)->item(1);
        if (! $prototype instanceof DOMElement) {
            throw new RuntimeException('Шаблон табеля повреждён: нет строки-образца');
        }

        foreach ($rows as $row) {
            /** @var DOMElement $tr */
            $tr = $prototype->cloneNode(true);
            $cells = self::cells($xpath, $tr);

            self::setPlaceholderText(self::paragraphOf($cells[0]), $row['fio']);

            foreach ($row['days'] as $day => $value) {
                if ($day >= 1 && $day <= self::DAY_COLUMNS) {
                    self::appendText($dom, self::paragraphOf($cells[$day]), $value, self::DAY_FONT_HALF_POINTS);
                }
            }

            self::appendText($dom, self::paragraphOf($cells[self::DAY_COLUMNS + 1]), $row['norm'], self::TOTAL_FONT_HALF_POINTS);
            self::appendText($dom, self::paragraphOf($cells[self::DAY_COLUMNS + 2]), $row['hours'], self::TOTAL_FONT_HALF_POINTS);

            $table->insertBefore($tr, $prototype);
        }

        $table->removeChild($prototype);
    }

    /**
     * Минуты в формат табеля: «3» — ровно 3 часа, «3/05» — 3 часа 5 минут,
     * пустая строка — не работал (отдельный «0» по условным обозначениям не ставится).
     */
    public static function hoursAndMinutes(int $minutes): string
    {
        if ($minutes <= 0) {
            return '';
        }

        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        return $rest === 0 ? (string) $hours : $hours.'/'.str_pad((string) $rest, 2, '0', STR_PAD_LEFT);
    }

    // --- мелкие помощники по XML ---

    private static function placeholderParagraph(DOMXPath $xpath, string $placeholder): DOMElement
    {
        $node = $xpath->query('//w:p[.//w:t[contains(text(), "'.$placeholder.'")]]')->item(0);

        if (! $node instanceof DOMElement) {
            throw new RuntimeException('Шаблон табеля повреждён: не найдено поле '.$placeholder);
        }

        return $node;
    }

    private static function setPlaceholderText(DOMElement $paragraph, string $value): void
    {
        $text = self::firstText($paragraph);
        $text->nodeValue = '';
        $text->appendChild($text->ownerDocument->createTextNode($value));
    }

    private static function firstRun(DOMElement $paragraph): DOMElement
    {
        foreach ($paragraph->getElementsByTagNameNS(self::NS, 'r') as $run) {
            return $run;
        }

        throw new RuntimeException('Шаблон табеля повреждён: в абзаце нет текста');
    }

    private static function firstText(DOMElement $node): DOMElement
    {
        foreach ($node->getElementsByTagNameNS(self::NS, 't') as $text) {
            return $text;
        }

        throw new RuntimeException('Шаблон табеля повреждён: в абзаце нет текста');
    }

    /**
     * @return array<int, DOMElement> ячейки строки: 0 — Ф.И.О., 1–31 — дни, 32 — норма, 33 — часы
     */
    private static function cells(DOMXPath $xpath, DOMElement $row): array
    {
        $cells = [];
        foreach ($xpath->query('./w:tc', $row) as $cell) {
            $cells[] = $cell;
        }

        if (count($cells) !== self::DAY_COLUMNS + 3) {
            throw new RuntimeException('Шаблон табеля повреждён: в строке '.count($cells).' ячеек вместо '.(self::DAY_COLUMNS + 3));
        }

        return $cells;
    }

    private static function paragraphOf(DOMElement $cell): DOMElement
    {
        foreach ($cell->getElementsByTagNameNS(self::NS, 'p') as $paragraph) {
            return $paragraph;
        }

        throw new RuntimeException('Шаблон табеля повреждён: в ячейке нет абзаца');
    }

    /**
     * Дописать текст в (пустую) ячейку, сохранив выравнивание по центру из шаблона.
     */
    private static function appendText(DOMDocument $dom, DOMElement $paragraph, string $value, int $halfPoints): void
    {
        if ($value === '') {
            return;
        }

        $run = $dom->createElementNS(self::NS, 'w:r');

        $rpr = $dom->createElementNS(self::NS, 'w:rPr');
        foreach (['w:sz', 'w:szCs'] as $tag) {
            $size = $dom->createElementNS(self::NS, $tag);
            $size->setAttributeNS(self::NS, 'w:val', (string) $halfPoints);
            $rpr->appendChild($size);
        }
        $run->appendChild($rpr);

        $text = $dom->createElementNS(self::NS, 'w:t');
        $text->appendChild($dom->createTextNode($value));
        $run->appendChild($text);

        $paragraph->appendChild($run);
    }
}
