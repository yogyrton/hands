<?php

declare(strict_types=1);

namespace App\Support\Export;

use App\Filament\Resources\Visits\Widgets\MasterEarningsSummary;
use App\Models\Master;
use App\Models\Service;
use App\Models\Visit;
use App\Support\WorktimeCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Данные за период для выгрузок: список мастеров, деньги по дням
 * (нал / безнал / сертификаты) и рабочее время по дням.
 *
 * Один источник для обеих кнопок — и Excel с суммами, и табель в Word считают
 * ровно те же цифры, что показывает страница «Учёт рабочего времени»:
 * деньги — через MasterEarningsSummary, время — массаж + подготовка кабинета.
 */
class WorktimeExportData
{
    /** @var Collection<int, Master>|null */
    private ?Collection $masters = null;

    /** @var array<string, array{cash: float, card: float, cert: float, total: float}>|null ключ «masterId|Y-m-d» */
    private ?array $money = null;

    /** @var array<string, int>|null ключ «masterId|Y-m-d» → минуты (массаж + подготовка) */
    private ?array $minutes = null;

    public function __construct(
        private readonly Carbon $from,
        private readonly Carbon $until,
    ) {}

    public static function for(Carbon $from, Carbon $until): self
    {
        return new self($from->copy()->startOfDay(), $until->copy()->endOfDay());
    }

    public function from(): Carbon
    {
        return $this->from;
    }

    public function until(): Carbon
    {
        return $this->until;
    }

    /**
     * Дни периода включительно — по одной строке на день в Excel.
     *
     * @return array<int, Carbon>
     */
    public function days(): array
    {
        $days = [];
        $cursor = $this->from->copy()->startOfDay();
        $last = $this->until->copy()->startOfDay();

        while ($cursor->lessThanOrEqualTo($last)) {
            $days[] = $cursor->copy();
            $cursor->addDay();
        }

        return $days;
    }

    /**
     * Мастера для документов: активные плюс все, у кого есть посещения за период
     * (чтобы уволенный попадал в старые месяцы и не мешал в новых).
     * Порядок — как на странице учёта: по sort_order, затем по id.
     *
     * @return Collection<int, Master>
     */
    public function masters(): Collection
    {
        if ($this->masters !== null) {
            return $this->masters;
        }

        $withVisits = Visit::query()
            ->whereBetween('performed_at', [$this->from, $this->until])
            ->distinct()
            ->pluck('master_id')
            ->all();

        return $this->masters = Master::query()
            ->where(fn ($q) => $q->where('is_active', true)->orWhereIn('id', $withVisits))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Деньги мастера за день: нал / безнал / сертификаты / итого.
     *
     * @return array{cash: float, card: float, cert: float, total: float}
     */
    public function moneyFor(int $masterId, Carbon $day): array
    {
        $this->loadMoney();

        return $this->money[$masterId.'|'.$day->toDateString()]
            ?? ['cash' => 0.0, 'card' => 0.0, 'cert' => 0.0, 'total' => 0.0];
    }

    /**
     * Рабочее время мастера за день в минутах: массаж + 15 мин подготовки на визит.
     */
    public function minutesFor(int $masterId, Carbon $day): int
    {
        $this->loadMinutes();

        return $this->minutes[$masterId.'|'.$day->toDateString()] ?? 0;
    }

    /**
     * Рабочее время мастера за весь период, минут.
     */
    public function minutesTotal(int $masterId): int
    {
        $this->loadMinutes();

        $total = 0;
        foreach ($this->minutes as $key => $value) {
            if (str_starts_with($key, $masterId.'|')) {
                $total += $value;
            }
        }

        return $total;
    }

    private function loadMoney(): void
    {
        if ($this->money !== null) {
            return;
        }

        $query = Visit::query()
            ->whereBetween('performed_at', [$this->from, $this->until])
            ->reorder()
            ->toBase()
            ->selectRaw('master_id, DATE(performed_at) as d')
            ->groupBy('master_id', 'd');

        foreach (MasterEarningsSummary::moneySelects() as $expr) {
            $query->selectRaw($expr);
        }

        $map = [];
        foreach ($query->get() as $row) {
            $day = Carbon::parse((string) $row->d)->toDateString();
            $map[((int) $row->master_id).'|'.$day] = MasterEarningsSummary::moneyFromRow($row);
        }

        $this->money = $map;
    }

    private function loadMinutes(): void
    {
        if ($this->minutes !== null) {
            return;
        }

        $rows = Visit::query()
            ->whereBetween('performed_at', [$this->from, $this->until])
            ->reorder()
            ->toBase()
            ->selectRaw('master_id, DATE(performed_at) as d, service_id, duration_minutes, base_price, COUNT(*) as cnt')
            ->groupBy('master_id', 'd', 'service_id', 'duration_minutes', 'base_price')
            ->get();

        if ($rows->isEmpty()) {
            $this->minutes = [];

            return;
        }

        $services = Service::query()->with('prices')->whereIn('id', $rows->pluck('service_id'))->get()->keyBy('id');
        $tiers = $this->masters()->keyBy('id');

        $map = [];
        foreach ($rows as $row) {
            $masterId = (int) $row->master_id;
            $count = (int) $row->cnt;

            $duration = $row->duration_minutes !== null
                ? (int) $row->duration_minutes
                : WorktimeCalculator::inferDuration(
                    $services->get($row->service_id),
                    (float) $row->base_price,
                    $tiers->get($masterId)?->tier,
                );

            $key = $masterId.'|'.Carbon::parse((string) $row->d)->toDateString();
            $map[$key] = ($map[$key] ?? 0)
                + (int) ($duration ?? 0) * $count
                + WorktimeCalculator::PREP_MINUTES * $count;
        }

        $this->minutes = $map;
    }
}
