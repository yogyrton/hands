<?php

namespace App\Filament\Resources\Cabinets\Pages;

use App\Filament\Resources\Cabinets\CabinetResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCabinet extends EditRecord
{
    protected static string $resource = CabinetResource::class;

    public function getTitle(): string
    {
        return (string) $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
