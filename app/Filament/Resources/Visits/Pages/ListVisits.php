<?php

namespace App\Filament\Resources\Visits\Pages;

use App\Filament\Resources\Visits\VisitResource;
use App\Filament\Resources\Visits\Widgets\MasterEarningsSummary;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVisits extends ListRecords
{
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
