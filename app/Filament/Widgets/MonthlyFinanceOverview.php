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

    protected static ?int $sort = -5;

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
