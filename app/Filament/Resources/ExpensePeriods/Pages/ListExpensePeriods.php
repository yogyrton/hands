<?php

namespace App\Filament\Resources\ExpensePeriods\Pages;

use App\Filament\Resources\ExpensePeriods\ExpensePeriodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExpensePeriods extends ListRecords
{
    protected static string $resource = ExpensePeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Создать месяц')
                ->visible(fn (): bool => (bool) auth()->user()?->isAdmin()),
        ];
    }
}
