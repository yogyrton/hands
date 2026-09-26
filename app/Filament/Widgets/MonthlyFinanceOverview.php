<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\Certificates\CertificateResource;
use App\Models\Certificate;
use App\Support\MonthlyFinance;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

/**
 * Главный финансовый блок дашборда: выбор месяца (‹ предыдущий · текущий ·
 * следующий ›, по умолчанию текущий) и авто-расчёт за него — выручка, зарплата
 * мастеров (≈½ наработанного), постоянные расходы и прибыль. Всё считается на
 * лету, ничего вручную не заводится. Только для администратора.
 */
class MonthlyFinanceOverview extends Widget
{
    protected string $view = 'filament.widgets.monthly-finance-overview';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -6;

    // Не ленивый — сразу считаем и держим состояние выбранного месяца.
    protected static bool $isLazy = false;

    public int $year;

    public int $month;

    public function mount(): void
    {
        $now = Carbon::now();
        $this->year = $now->year;
        $this->month = $now->month;
    }

    public function prevMonth(): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->subMonthNoOverflow();
        $this->year = $date->year;
        $this->month = $date->month;
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->addMonthNoOverflow();
        $this->year = $date->year;
        $this->month = $date->month;
    }

    public function currentMonth(): void
    {
        $now = Carbon::now();
        $this->year = $now->year;
        $this->month = $now->month;
    }

    public function isCurrentMonth(): bool
    {
        $now = Carbon::now();

        return $this->year === $now->year && $this->month === $now->month;
    }

    public function summary(): object
    {
        return MonthlyFinance::for($this->year, $this->month);
    }

    /**
     * Итоги предыдущего месяца — для сравнения (стрелка ▲/▼ и процент).
     */
    public function previousSummary(): object
    {
        $date = Carbon::create($this->year, $this->month, 1)->subMonthNoOverflow();

        return MonthlyFinance::for($date->year, $date->month);
    }

    /**
     * Сравнение показателя с прошлым месяцем — готовая подпись для стрелки.
     * Всегда единообразно «в N раз больше/меньше» (проценты не мешаем, чтобы на
     * одной странице не было где разы, где проценты). Почти без изменения —
     * «примерно как в прошлом месяце». Если есть ноль/минус (например, убыток) —
     * «разы» не имеют смысла, показываем разницу деньгами.
     *
     * @return array{up: bool, text: string}
     */
    public function changeText(float $current, float $previous): array
    {
        $up = $current >= $previous;

        if ($current > 0 && $previous > 0) {
            $ratio = $up ? $current / $previous : $previous / $current;

            if ($ratio < 1.05) {
                return ['up' => true, 'text' => 'примерно как в прошлом месяце'];
            }

            $r = rtrim(rtrim(number_format($ratio, 1, '.', ''), '0'), '.');

            return ['up' => $up, 'text' => 'в '.$r.' '.self::razWord($ratio).' '.($up ? 'больше' : 'меньше')];
        }

        // Ноль или минус в одном из месяцев — показываем разницу деньгами.
        $diff = round($current - $previous, 2);

        return ['up' => $up, 'text' => ($diff >= 0 ? '+' : '−').$this->money(abs($diff)).' р'];
    }

    /**
     * Правильное «раз / раза» для числа (в т.ч. дробного): в 2 раза, в 5 раз,
     * в 8.2 раза.
     */
    private static function razWord(float $ratio): string
    {
        // Дробное — всегда «раза» (в 8.2 раза).
        if (abs($ratio - round($ratio)) > 0.05) {
            return 'раза';
        }

        $n = (int) round($ratio);
        $mod100 = $n % 100;
        $mod10 = $n % 10;

        if ($mod100 >= 11 && $mod100 <= 14) {
            return 'раз';
        }

        return ($mod10 >= 2 && $mod10 <= 4) ? 'раза' : 'раз';
    }

    /**
     * Обязательства по действующим сертификатам (не за месяц, а всего): сколько
     * денег получено при продаже, но услугами ещё не отработано.
     */
    public function outstandingCerts(): float
    {
        return Certificate::outstandingLiability();
    }

    public function money(float $value): string
    {
        return number_format($value, 2, '.', ' ');
    }

    public function soldDate(Certificate $certificate): string
    {
        return Carbon::parse($certificate->sold_at)->format('d.m.Y');
    }

    public function soldShort(Certificate $certificate): string
    {
        return Carbon::parse($certificate->sold_at)->format('d.m');
    }

    public function certUrl(Certificate $certificate): string
    {
        return CertificateResource::getUrl('view', ['record' => $certificate]);
    }
}
