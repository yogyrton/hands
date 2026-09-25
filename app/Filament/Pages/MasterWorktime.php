<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasPeriodShortcuts;
use App\Filament\Resources\Visits\Widgets\MasterEarningsSummary;
use App\Models\Master;
use App\Models\Service;
use App\Models\Visit;
use App\Support\WorktimeCalculator;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Учёт рабочего времени одного мастера: выбор периода, таблица по дням
 * (часы массажа / доп. часы на подготовку / всего за день) + итог, и ниже —
 * разбивка по услугам и длительности. Открывается из списка мастеров.
 * Не в меню; только для администратора.
 */
class MasterWorktime extends Page
{
    use HasPeriodShortcuts;

    protected string $view = 'filament.pages.master-worktime';

    // Синхронизируемое свойство — id мастера из URL; сама модель грузится в booted().
    public int $masterId;

    protected Master $master;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function getRoutePath(Panel $panel): string
    {
        return '/master-worktime/{master}';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public function getTitle(): string
    {
        return 'Рабочее время · '.$this->master->name;
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Назад к списку')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn (): string => WorktimeReport::getUrl([
                    'from' => $this->data['from'] ?? null,
                    'until' => $this->data['until'] ?? null,
                ])),
        ];
    }

    public function mount(int|string $master): void
    {
        abort_unless((bool) auth()->user()?->isAdmin(), 403);

        $this->masterId = (int) $master;

        // Период приходит из списка мастеров через query-параметры; иначе — текущий месяц.
        $this->form->fill([
            'from' => request()->query('from') ?: now()->startOfMonth()->toDateString(),
            'until' => request()->query('until') ?: now()->toDateString(),
        ]);
    }

    public function booted(): void
    {
        // Модель мастера нужна на каждый запрос (mount вызывается только раз).
        $this->master = Master::findOrFail($this->masterId);
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
     * @return Collection<int, object>
     */
    private function rows(): Collection
    {
        return Visit::query()
            ->where('master_id', $this->master->id)
            ->whereBetween('performed_at', [$this->from(), $this->until()])
            ->reorder()
            ->toBase()
            ->selectRaw('DATE(performed_at) as d, service_id, duration_minutes, base_price, COUNT(*) as cnt')
            ->groupBy('d', 'service_id', 'duration_minutes', 'base_price')
            ->get();
    }

    /**
     * Длительность строки: из посещения или по цене из прайса.
     */
    private function durationFor(object $row, Collection $services): ?int
    {
        return $row->duration_minutes !== null
            ? (int) $row->duration_minutes
            : WorktimeCalculator::inferDuration($services->get($row->service_id), (float) $row->base_price, $this->master->tier);
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<int, Service>
     */
    private function servicesFor(Collection $rows): Collection
    {
        return Service::query()->with('prices')->whereIn('id', $rows->pluck('service_id'))->get()->keyBy('id');
    }

    /**
     * Таблица по дням: дата, часы массажа, доп. часы (подготовка), всего.
     *
     * @return array<int, array<string, mixed>>
     */
    public function days(): array
    {
        $rows = $this->rows();
        if ($rows->isEmpty()) {
            return [];
        }

        $services = $this->servicesFor($rows);
        $money = $this->moneyByDay();

        $byDay = [];
        foreach ($rows as $row) {
            $day = (string) $row->d;
            $count = (int) $row->cnt;
            $minutes = (int) ($this->durationFor($row, $services) ?? 0) * $count;

            $byDay[$day] ??= ['visits' => 0, 'massage' => 0];
            $byDay[$day]['visits'] += $count;
            $byDay[$day]['massage'] += $minutes;
        }

        ksort($byDay);

        return collect($byDay)->map(function (array $d, string $day) use ($money): array {
            $prep = $d['visits'] * WorktimeCalculator::PREP_MINUTES;
            $m = $money[$day] ?? ['cash' => 0.0, 'card' => 0.0, 'cert' => 0.0, 'total' => 0.0];

            return [
                'date' => Carbon::parse($day)->format('d.m.Y'),
                'visits' => $d['visits'],
                'massage_minutes' => $d['massage'],
                'prep_minutes' => $prep,
                'total_minutes' => $d['massage'] + $prep,
                'cash' => $m['cash'],
                'card' => $m['card'],
                'cert' => $m['cert'],
                'money_total' => $m['total'],
            ];
        })->values()->all();
    }

    /**
     * Деньги по дням: нал/безнал/сертификат/итого за каждый день (по «Y-m-d»).
     * Логика учёта общая со сводкой над списком посещений.
     *
     * @return array<string, array{cash: float, card: float, cert: float, total: float}>
     */
    private function moneyByDay(): array
    {
        $query = Visit::query()
            ->where('master_id', $this->master->id)
            ->whereBetween('performed_at', [$this->from(), $this->until()])
            ->reorder()
            ->toBase()
            ->selectRaw('DATE(performed_at) as d')
            ->groupBy('d');

        foreach (MasterEarningsSummary::moneySelects() as $expr) {
            $query->selectRaw($expr);
        }

        $map = [];
        foreach ($query->get() as $row) {
            $map[(string) $row->d] = MasterEarningsSummary::moneyFromRow($row);
        }

        return $map;
    }

    /**
     * Итоги по деньгам за весь период (нал/безнал/сертификат/итого).
     *
     * @return array{cash: float, card: float, cert: float, total: float}
     */
    public function moneyTotals(): array
    {
        $query = Visit::query()
            ->where('master_id', $this->master->id)
            ->whereBetween('performed_at', [$this->from(), $this->until()])
            ->reorder()
            ->toBase();

        foreach (MasterEarningsSummary::moneySelects() as $expr) {
            $query->selectRaw($expr);
        }

        $row = $query->first();

        return $row
            ? MasterEarningsSummary::moneyFromRow($row)
            : ['cash' => 0.0, 'card' => 0.0, 'cert' => 0.0, 'total' => 0.0];
    }

    /**
     * Разбивка по услугам и длительности за период (нижняя таблица).
     *
     * @return array<int, array<string, mixed>>
     */
    public function breakdown(): array
    {
        $rows = $this->rows();
        if ($rows->isEmpty()) {
            return [];
        }

        $services = $this->servicesFor($rows);

        $items = [];
        foreach ($rows as $row) {
            $count = (int) $row->cnt;
            $duration = $this->durationFor($row, $services);
            $inferred = $row->duration_minutes === null && $duration !== null;
            $label = $duration ? $duration.' мин'.($inferred ? ' ≈' : '') : '—';
            $key = $row->service_id.'|'.$label;

            if (isset($items[$key])) {
                $items[$key]['count'] += $count;
                $items[$key]['minutes'] += (int) ($duration ?? 0) * $count;
            } else {
                $items[$key] = [
                    'service' => $services->get($row->service_id)?->name ?? 'Услуга',
                    'duration' => $label,
                    'count' => $count,
                    'minutes' => (int) ($duration ?? 0) * $count,
                ];
            }
        }

        return collect($items)->sortByDesc('count')->values()->all();
    }

    /**
     * @return object{visits: int, massage_minutes: int, prep_minutes: int, total_minutes: int}
     */
    public function totals(): object
    {
        return WorktimeCalculator::forQuery(
            Visit::query()
                ->where('master_id', $this->master->id)
                ->whereBetween('performed_at', [$this->from(), $this->until()]),
        );
    }

    public function anyInferred(): bool
    {
        foreach ($this->breakdown() as $item) {
            if (str_contains((string) $item['duration'], '≈')) {
                return true;
            }
        }

        return false;
    }

    public function prepMinutes(): int
    {
        return WorktimeCalculator::PREP_MINUTES;
    }

    public function hm(int $minutes): string
    {
        return WorktimeCalculator::hm($minutes);
    }

    public function money(float $value): string
    {
        return MasterEarningsSummary::money($value);
    }
}
