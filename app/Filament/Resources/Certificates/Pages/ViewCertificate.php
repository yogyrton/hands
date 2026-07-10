<?php

namespace App\Filament\Resources\Certificates\Pages;

use App\Filament\Resources\Certificates\CertificateResource;
use App\Models\Certificate;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCertificate extends ViewRecord
{
    protected static string $resource = CertificateResource::class;

    public function getTitle(): string
    {
        return 'Сертификат №'.$this->record->number;
    }

    protected function getHeaderActions(): array
    {
        return [
            // Изменять сертификат нельзя. Удалить можно, только если по нему ещё не было посещений.
            DeleteAction::make()
                ->label('Удалить')
                ->visible(fn (Certificate $record): bool => (bool) auth()->user()?->isAdmin()
                    && ! $record->visits()->exists())
                ->modalDescription('Сертификат ещё не использован — его можно удалить.'),
        ];
    }
}
