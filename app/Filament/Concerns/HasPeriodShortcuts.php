<?php

namespace App\Filament\Concerns;

use App\Filament\Support\PeriodShortcuts;
use Filament\Schemas\Components\Actions;

/**
 * Кнопки-пресеты периода для страниц с полями «С … По …»
 * (statePath data.from / data.until). Логика — в PeriodShortcuts, трейт лишь
 * даёт удобный доступ из формы страницы.
 */
trait HasPeriodShortcuts
{
    protected function periodShortcuts(): Actions
    {
        return PeriodShortcuts::make();
    }
}
