<?php

namespace App\Filament\Resources\Certificates\Pages;

use App\Filament\Resources\Certificates\CertificateResource;
use App\Models\Certificate;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
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
            // Изменить (метаданные всегда, суммы — пока серт не использован). Только админ.
            EditAction::make()
                ->label('Изменить')
                ->visible(fn (Certificate $record): bool => CertificateResource::canEdit($record)),
            // Удалить можно, только если по сертификату ещё не было посещений.
            DeleteAction::make()
                ->label('Удалить')
                ->visible(fn (Certificate $record): bool => (bool) auth()->user()?->isAdmin()
                    && ! $record->visits()->exists())
                ->modalDescription('Сертификат ещё не использован — его можно удалить.'),
        ];
    }
}
