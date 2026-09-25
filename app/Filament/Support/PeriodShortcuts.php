<?php

namespace App\Filament\Support;

use Filament\Actions\Action;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Carbon;

/**
 * Кнопки-пресеты периода для любых схем с полями «from» / «until»
 * (страницы отчётов, учёт рабочего времени и табличный фильтр посещений):
 *  — «Сегодня» и «Этот месяц» — быстрый переход;
 *  — «Пред./След. день» и «Пред./След. месяц» — шаг назад/вперёд от текущего
 *    выбора (нажал «Пред. день» дважды — ушло на 2 дня назад; «Пред. месяц» —
 *    на полный предыдущий календарный месяц).
 *
 * Работает только через Set/Get (без обращения к компоненту), поэтому годится
 * и для страницы (Livewire-компонент), и для статической конфигурации таблицы.
 */
class PeriodShortcuts
{
    public static function make(): Actions
    {
        return Actions::make([
            Action::make('today')
                ->label('Сегодня')
                ->color('gray')
                ->action(fn (Set $set) => self::apply($set, now(), now())),
            Action::make('prevDay')
                ->label('‹ Пред. день')
                ->color('gray')
                ->action(fn (Get $get, Set $set) => self::shiftDay($get, $set, -1)),
            Action::make('nextDay')
                ->label('След. день ›')
                ->color('gray')
                ->action(fn (Get $get, Set $set) => self::shiftDay($get, $set, 1)),
            Action::make('thisMonth')
                ->label('Этот месяц')
                ->color('gray')
                ->action(fn (Set $set) => self::applyMonth($set, now())),
            Action::make('prevMonth')
                ->label('‹ Пред. месяц')
                ->color('gray')
                ->action(fn (Get $get, Set $set) => self::shiftMonth($get, $set, -1)),
            Action::make('nextMonth')
                ->label('След. месяц ›')
                ->color('gray')
                ->action(fn (Get $get, Set $set) => self::shiftMonth($get, $set, 1)),
        ])->columnSpanFull();
    }

    private static function apply(Set $set, Carbon $from, Carbon $until): void
    {
        $set('from', $from->toDateString());
        $set('until', $until->toDateString());
    }

    /**
     * Полный календарный месяц, в котором лежит $ref.
     */
    private static function applyMonth(Set $set, Carbon $ref): void
    {
        self::apply($set, $ref->copy()->startOfMonth(), $ref->copy()->endOfMonth());
    }

    /**
     * Сдвигает обе границы периода на $days дней относительно текущего выбора.
     */
    private static function shiftDay(Get $get, Set $set, int $days): void
    {
        $from = Carbon::parse($get('from') ?: now()->toDateString());
        $until = Carbon::parse($get('until') ?: now()->toDateString());

        $set('from', $from->addDays($days)->toDateString());
        $set('until', $until->addDays($days)->toDateString());
    }

    /**
     * Переходит на полный календарный месяц на $months от текущего выбора
     * (ориентируясь по дате «С»).
     */
    private static function shiftMonth(Get $get, Set $set, int $months): void
    {
        $ref = Carbon::parse($get('from') ?: now()->toDateString())
            ->startOfMonth()
            ->addMonthsNoOverflow($months);

        self::applyMonth($set, $ref);
    }
}
