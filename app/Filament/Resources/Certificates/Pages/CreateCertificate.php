<?php

namespace App\Filament\Resources\Certificates\Pages;

use App\Contracts\Services\CertificateServiceInterface;
use App\Data\CertificateData;
use App\Filament\Resources\Certificates\CertificateResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCertificate extends CreateRecord
{
    protected static string $resource = CertificateResource::class;

    /**
     * Выпуск идёт через доменный сервис: авто-номер, срок +3 мес, операция продажи.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(CertificateServiceInterface::class)->issue(CertificateData::from([
            'type' => $data['type'],
            'initial_visits' => $data['initial_visits'] ?? null,
            'initial_amount' => $data['initial_amount'] ?? null,
        ]));
    }
}
