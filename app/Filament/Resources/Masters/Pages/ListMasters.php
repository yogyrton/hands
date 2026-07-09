<?php

namespace App\Filament\Resources\Masters\Pages;

use App\Filament\Resources\Masters\MasterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMasters extends ListRecords
{
    protected static string $resource = MasterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
