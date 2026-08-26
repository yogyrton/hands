<?php

namespace App\Filament\Pages;

use App\Models\Master;
use App\Models\Service;
use App\Models\Visit;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

/**
 * Учёт рабочего времени мастеров за период: количество посещений, разбивка по
 * услугам и длительности, суммарное время. К каждому массажу прибавляется
 * PREP_MINUTES на подготовку кабинета. Только для администратора.
 */
class WorktimeReport extends Page
{
    protected string $view = 'filament.pages.worktime-report';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?int $navigationSort = 6;

    /** Надбавка на подготовку кабинета к каждому массажу, минут. */
    private const PREP_MINUTES = 15;

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

    public function mount(): void
    {
        $this->form->fill([
            'from' => now()->startOfMonth()->toDateString(),
            'until' => now()->toDateString(),
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
     * По каждому мастеру с посещениями за период: разбивка по услуге+длительности,
     * суммарное время массажей, время на подготовку (15 мин × посещений) и итог.
     *
     * @return array<int, array<string, mixed>>
     */
    public function byMaster(): array
    {
        $rows = Visit::query()
            ->whereBetween('performed_at', [$this->from(), $this->until()])
            ->reorder()
            ->toBase()
            ->selectRaw('master_id, service_id, duration_minutes, COUNT(*) as cnt')
            ->groupBy('master_id', 'service_id', 'duration_minutes')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $masters = Master::query()->whereIn('id', $rows->pluck('master_id'))->get()->keyBy('id');
        $serviceNames = Service::query()->whereIn('id', $rows->pluck('service_id'))->pluck('name', 'id');

        $byMaster = [];
        foreach ($rows as $row) {
            $mid = (int) $row->master_id;
            $count = (int) $row->cnt;
            $minutes = (int) ($row->duration_minutes ?? 0) * $count;

            $byMaster[$mid] ??= ['items' => [], 'visits' => 0, 'massage' => 0];
            $byMaster[$mid]['items'][] = [
                'service' => $serviceNames[$row->service_id] ?? 'Услуга',
                'duration' => $row->duration_minutes ? $row->duration_minutes.' мин' : '—',
                'count' => $count,
                'minutes' => $minutes,
            ];
            $byMaster[$mid]['visits'] += $count;
            $byMaster[$mid]['massage'] += $minutes;
        }

        return collect($byMaster)
            ->map(function (array $data, int $mid) use ($masters): array {
                $master = $masters->get($mid);
                $prep = $data['visits'] * self::PREP_MINUTES;

                return [
                    'name' => $master?->name ?? 'Мастер',
                    'sort' => $master?->sort_order ?? 999,
                    'visits' => $data['visits'],
                    'items' => collect($data['items'])->sortByDesc('count')->values()->all(),
                    'massage_minutes' => $data['massage'],
                    'prep_minutes' => $prep,
                    'total_minutes' => $data['massage'] + $prep,
                ];
            })
            ->sortBy('sort')
            ->values()
            ->all();
    }

    public function prepMinutes(): int
    {
        return self::PREP_MINUTES;
    }

    /**
     * Минуты в «5 ч 45 мин».
     */
    public function hm(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        if ($h > 0 && $m > 0) {
            return $h.' ч '.$m.' мин';
        }

        if ($h > 0) {
            return $h.' ч';
        }

        return $m.' мин';
    }
}
