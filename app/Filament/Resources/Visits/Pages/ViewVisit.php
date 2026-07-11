<?php

namespace App\Filament\Resources\Visits\Pages;

use App\Filament\Resources\Visits\VisitResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewVisit extends ViewRecord
{
    protected static string $resource = VisitResource::class;

    public function getTitle(): string
    {
        return 'Посещение — '.$this->record->master?->name.' · '.$this->record->performed_at->format('d.m.Y');
    }

    protected function getHeaderActions(): array
    {
        return [
            // Возврат туда, откуда пришли (например, к сертификату из истории операций).
            Action::make('back')
                ->label('Назад')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('gray')
                ->url(fn (): string => url()->previous()),
        ];
    }
}
