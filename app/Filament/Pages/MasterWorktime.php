<?php

namespace App\Filament\Pages;

use App\Models\Master;
use App\Models\Service;
use App\Models\Visit;
use App\Support\WorktimeCalculator;
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

    public function mount(int|string $master): void
    {
        abort_unless((bool) auth()->user()?->isAdmin(), 403);

        $this->masterId = (int) $master;
        $this->form->fill([
            'from' => now()->startOfMonth()->toDateString(),
            'until' => now()->toDateString(),
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

        return collect($byDay)->map(function (array $d, string $day): array {
            $prep = $d['visits'] * WorktimeCalculator::PREP_MINUTES;

            return [
                'date' => Carbon::parse($day)->format('d.m.Y'),
                'visits' => $d['visits'],
                'massage_minutes' => $d['massage'],
                'prep_minutes' => $prep,
                'total_minutes' => $d['massage'] + $prep,
            ];
        })->values()->all();
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
}
