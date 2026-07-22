<?php

namespace App\Filament\Resources\PayrollPeriods\Pages;

use App\Filament\Resources\PayrollPeriods\PayrollPeriodResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPayrollPeriod extends ViewRecord
{
    protected static string $resource = PayrollPeriodResource::class;

    public function getTitle(): string
    {
        return 'Зарплата · '.$this->record->label();
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
