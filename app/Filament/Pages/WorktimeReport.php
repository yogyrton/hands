<?php

namespace App\Filament\Pages;

use App\Models\Master;
use App\Models\Visit;
use App\Support\WorktimeCalculator;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Учёт рабочего времени: карточки мастеров с суммарным временем за текущий месяц.
 * Клик по мастеру открывает подробную страницу с выбором периода и таблицей по дням.
 * Только для администратора.
 */
class WorktimeReport extends Page
{
    protected string $view = 'filament.pages.worktime-report';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?int $navigationSort = 6;

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
     * Мастера с посещениями за текущий месяц и их суммарное время.
     *
     * @return array<int, array<string, mixed>>
     */
    public function mastersSummary(): array
    {
        $query = Visit::query()->whereBetween('performed_at', [now()->startOfMonth(), now()->endOfMonth()]);
        $worktime = WorktimeCalculator::perMaster($query);

        if ($worktime === []) {
            return [];
        }

        $masters = Master::query()->whereIn('id', array_keys($worktime))->get()->keyBy('id');

        $rows = [];
        foreach ($worktime as $mid => $t) {
            $master = $masters->get($mid);
            $rows[] = [
                'id' => $mid,
                'name' => $master?->name ?? 'Мастер',
                'sort' => $master?->sort_order ?? 999,
                'visits' => $t->visits,
                'massage_minutes' => $t->massage_minutes,
                'prep_minutes' => $t->prep_minutes,
                'total_minutes' => $t->total_minutes,
            ];
        }

        usort($rows, fn (array $a, array $b): int => $a['sort'] <=> $b['sort']);

        return $rows;
    }

    public function detailUrl(int $masterId): string
    {
        return MasterWorktime::getUrl(['master' => $masterId]);
    }

    public function hm(int $minutes): string
    {
        return WorktimeCalculator::hm($minutes);
    }
}
