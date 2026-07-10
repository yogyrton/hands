<?php

namespace App\Filament\Resources\Visits\Pages;

use App\Contracts\Services\VisitServiceInterface;
use App\Data\VisitData;
use App\Filament\Resources\Visits\VisitResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateVisit extends CreateRecord
{
    protected static string $resource = VisitResource::class;

    /**
     * Создание идёт через доменный сервис (транзакция + списание сертификата).
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(VisitServiceInterface::class)->register(VisitData::from([
            'master_id' => $data['master_id'],
            'service_id' => $data['service_id'],
            'base_price' => $data['base_price'] ?? 0,
            'service_price' => $data['service_price'] ?? 0,
            'payment_type' => $data['payment_type'] ?? 'cash',
            'discount_reason' => $data['discount_reason'] ?? null,
            'certificate_id' => $data['certificate_id'] ?? null,
            'comment' => $data['comment'] ?? null,
        ]));
    }
}
