<?php

namespace App\Filament\Resources\ExpensePeriods\Pages;

use App\Filament\Resources\ExpensePeriods\ExpensePeriodResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewExpensePeriod extends ViewRecord
{
    protected static string $resource = ExpensePeriodResource::class;

    public function getTitle(): string
    {
        return 'Расходы · '.$this->record->label();
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Удалить месяц')
                ->visible(fn (): bool => (bool) auth()->user()?->isAdmin()),
        ];
    }
}
