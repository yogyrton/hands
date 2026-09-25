<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Carbon;

/**
 * Кнопки-пресеты периода для страниц с полями «С … По …»
 * (statePath data.from / data.until):
 *  — быстрые диапазоны (сегодня, вчера, эта неделя, этот/прошлый месяц);
 *  — шаг на день назад/вперёд от текущего выбора (нажал «Вчера», затем
 *    «Пред. день» — ушло на 2 дня назад).
 */
trait HasPeriodShortcuts
{
    protected function periodShortcuts(): Actions
    {
        return Actions::make([
            Action::make('prevDay')
                ->label('‹ Пред. день')
                ->color('gray')
                ->action(fn (Get $get, Set $set) => $this->shiftPeriod($get, $set, -1)),
            Action::make('today')
                ->label('Сегодня')
                ->color('gray')
                ->action(fn (Set $set) => $this->applyPeriod($set, now(), now())),
            Action::make('yesterday')
                ->label('Вчера')
                ->color('gray')
                ->action(fn (Set $set) => $this->applyPeriod($set, now()->subDay(), now()->subDay())),
            Action::make('week')
                ->label('Эта неделя')
                ->color('gray')
                ->action(fn (Set $set) => $this->applyPeriod($set, now()->startOfWeek(), now())),
            Action::make('month')
                ->label('Этот месяц')
                ->color('gray')
                ->action(fn (Set $set) => $this->applyPeriod($set, now()->startOfMonth(), now())),
            Action::make('lastMonth')
                ->label('Прошлый месяц')
                ->color('gray')
                ->action(fn (Set $set) => $this->applyPeriod(
                    $set,
                    now()->subMonthNoOverflow()->startOfMonth(),
                    now()->subMonthNoOverflow()->endOfMonth(),
                )),
            Action::make('nextDay')
                ->label('След. день ›')
                ->color('gray')
                ->action(fn (Get $get, Set $set) => $this->shiftPeriod($get, $set, 1)),
        ])->columnSpanFull();
    }

    private function applyPeriod(Set $set, Carbon $from, Carbon $until): void
    {
        $set('from', $from->toDateString());
        $set('until', $until->toDateString());
    }

    /**
     * Сдвигает обе границы периода на $days дней относительно текущего выбора.
     */
    private function shiftPeriod(Get $get, Set $set, int $days): void
    {
        $from = Carbon::parse($get('from') ?: now()->toDateString());
        $until = Carbon::parse($get('until') ?: now()->toDateString());

        $set('from', $from->addDays($days)->toDateString());
        $set('until', $until->addDays($days)->toDateString());
    }
}
