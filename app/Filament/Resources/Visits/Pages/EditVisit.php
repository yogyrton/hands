<?php

namespace App\Filament\Resources\Visits\Pages;

use App\Contracts\Services\VisitServiceInterface;
use App\Data\VisitData;
use App\Filament\Resources\Visits\VisitResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditVisit extends EditRecord
{
    protected static string $resource = VisitResource::class;

    /**
     * Восстанавливаем UI-тумблеры формы из сохранённого посещения
     * (они не хранятся в БД — выводим из данных визита).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['use_promotion'] = ! empty($data['promotion_id']);
        $data['use_special'] = ! empty($data['discount_reason']);

        if ($data['use_special']) {
            // При особых условиях по кассе шла заданная сумма (paid_amount).
            $data['special_paid_amount'] = $data['paid_amount'];
        }

        return $data;
    }

    /**
     * Сохранение — через доменный сервис (правка только визитов без сертификата).
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(VisitServiceInterface::class)->edit($record, VisitData::from([
            'master_id' => $data['master_id'],
            'service_id' => $data['service_id'],
            'base_price' => $data['base_price'] ?? 0,
            'service_price' => $data['service_price'] ?? 0,
            'payment_type' => $data['payment_type'] ?? 'cash',
            'discount_reason' => $data['discount_reason'] ?? null,
            'promotion_id' => $data['promotion_id'] ?? null,
            'comment' => $data['comment'] ?? null,
            'special_paid_amount' => $data['special_paid_amount'] ?? null,
        ]));
    }
}
