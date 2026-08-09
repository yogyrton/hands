<?php

namespace App\Filament\Resources\Visits\Pages;

use App\Filament\Resources\Visits\VisitResource;
use App\Filament\Resources\Visits\Widgets\MasterEarningsSummary;
use Filament\Actions\CreateAction;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListVisits extends ListRecords
{
    // Прокидываем состояние таблицы (фильтры/поиск/период) в виджеты-шапки,
    // иначе InteractsWithPageTable получает null в реактивных свойствах
    // (падение на сбросе фильтра) и виджет не реагирует на смену периода.
    use ExposesTableToWidgets;

    protected static string $resource = VisitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * Сводка заработка мастеров за выбранный период — над списком.
     */
    protected function getHeaderWidgets(): array
    {
        return [
            MasterEarningsSummary::class,
        ];
    }
}
