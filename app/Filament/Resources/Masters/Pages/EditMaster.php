<?php

namespace App\Filament\Resources\Masters\Pages;

use App\Filament\Resources\Masters\MasterResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditMaster extends EditRecord
{
    protected static string $resource = MasterResource::class;

    public function getTitle(): string
    {
        return (string) $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
