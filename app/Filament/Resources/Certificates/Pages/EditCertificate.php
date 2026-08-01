<?php

namespace App\Filament\Resources\Certificates\Pages;

use App\Enums\CertificateOperationType;
use App\Enums\CertificateType;
use App\Filament\Resources\Certificates\CertificateResource;
use App\Models\Certificate;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditCertificate extends EditRecord
{
    protected static string $resource = CertificateResource::class;

    /**
     * Метаданные (номер, описание, клиент, срок) меняются всегда. Номинал (сумма/
     * посещения) — только пока сертификат не начали использовать (в форме поля
     * иначе заблокированы). Если номинал изменили у неиспользованного серта —
     * тянем за ним остаток и сумму в записи о продаже, чтобы учёт остался цельным.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {
            /** @var Certificate $record */
            $wasUsed = $record->wasUsed();

            $record->update($data);

            if (! $wasUsed) {
                // Списаний не было — остаток равен номиналу.
                if ($record->type === CertificateType::Visits) {
                    $record->remaining_visits = $record->initial_visits;
                } else {
                    $record->remaining_amount = $record->initial_amount;
                }
                $record->save();

                // Запись о продаже в истории — на актуальную сумму сертификата.
                $record->operations()
                    ->where('type', CertificateOperationType::Sale)
                    ->update(['amount' => (float) ($record->initial_amount ?? 0)]);
            }

            $record->refreshStatus();

            return $record;
        });
    }
}
