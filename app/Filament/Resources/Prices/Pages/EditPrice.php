<?php

namespace App\Filament\Resources\Prices\Pages;

use App\Filament\Resources\Prices\PriceResource;
use Filament\Resources\Pages\EditRecord;

class EditPrice extends EditRecord
{
    protected static string $resource = PriceResource::class;

    public function getTitle(): string
    {
        return 'Прайс · '.$this->record->name;
    }

    protected function getHeaderActions(): array
    {
        // Удалять услугу отсюда нельзя — только её строки прайса ниже.
        return [];
    }
}
