<?php

namespace App\Filament\Resources\Visits\Widgets;

use App\Filament\Resources\Visits\Pages\ListVisits;
use App\Support\WorktimeCalculator;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\Widget;

/**
 * Сводка рабочего времени над списком посещений за выбранный период (по умолчанию —
 * сегодня): время массажей + подготовка (15 мин на каждый) = итого. Реагирует на
 * фильтр списка.
 */
class VisitsWorktimeSummary extends Widget
{
    use InteractsWithPageTable;

    protected string $view = 'filament.resources.visits.widgets.visits-worktime-summary';

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;

    protected function getTablePage(): string
    {
        return ListVisits::class;
    }

    public function summary(): object
    {
        return WorktimeCalculator::forQuery($this->getPageTableQuery());
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
