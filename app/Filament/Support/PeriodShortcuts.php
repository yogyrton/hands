<?php

namespace App\Filament\Support;

use Filament\Actions\Action;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Carbon;

/**
 * Кнопки-пресеты периода для любых схем с полями «from» / «until»
 * (страницы отчётов и табличный фильтр посещений):
 *  — быстрые диапазоны (сегодня, вчера, эта неделя, этот/прошлый месяц);
 *  — шаг на день назад/вперёд от текущего выбора (нажал «Вчера», затем
 *    «Пред. день» — ушло на 2 дня назад).
 *
 * Работает только через Set/Get (без обращения к компоненту), поэтому годится
 * и для страницы (Livewire-компонент), и для статической конфигурации таблицы.
 */
class PeriodShortcuts
{
    public static function make(): Actions
    {
        return Actions::make([
            Action::make('prevDay')
                ->label('‹ Пред. день')
                ->color('gray')
                ->action(fn (Get $get, Set $set) => self::shift($get, $set, -1)),
            Action::make('today')
                ->label('Сегодня')
                ->color('gray')
                ->action(fn (Set $set) => self::apply($set, now(), now())),
            Action::make('yesterday')
                ->label('Вчера')
                ->color('gray')
                ->action(fn (Set $set) => self::apply($set, now()->subDay(), now()->subDay())),
            Action::make('week')
                ->label('Эта неделя')
                ->color('gray')
                ->action(fn (Set $set) => self::apply($set, now()->startOfWeek(), now())),
            Action::make('month')
                ->label('Этот месяц')
                ->color('gray')
                ->action(fn (Set $set) => self::apply($set, now()->startOfMonth(), now())),
            Action::make('lastMonth')
                ->label('Прошлый месяц')
                ->color('gray')
                ->action(fn (Set $set) => self::apply(
                    $set,
                    now()->subMonthNoOverflow()->startOfMonth(),
                    now()->subMonthNoOverflow()->endOfMonth(),
                )),
            Action::make('nextDay')
                ->label('След. день ›')
                ->color('gray')
                ->action(fn (Get $get, Set $set) => self::shift($get, $set, 1)),
        ])->columnSpanFull();
    }

    private static function apply(Set $set, Carbon $from, Carbon $until): void
    {
        $set('from', $from->toDateString());
        $set('until', $until->toDateString());
    }

    /**
     * Сдвигает обе границы периода на $days дней относительно текущего выбора.
     */
    private static function shift(Get $get, Set $set, int $days): void
    {
        $from = Carbon::parse($get('from') ?: now()->toDateString());
        $until = Carbon::parse($get('until') ?: now()->toDateString());

        $set('from', $from->addDays($days)->toDateString());
        $set('until', $until->addDays($days)->toDateString());
    }
}
