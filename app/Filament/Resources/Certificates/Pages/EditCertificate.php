<?php

namespace App\Filament\Resources\Certificates\Pages;

use App\Filament\Resources\Certificates\CertificateResource;
use Filament\Resources\Pages\EditRecord;

class EditCertificate extends EditRecord
{
    protected static string $resource = CertificateResource::class;

    /**
     * Меняем только метаданные (номер, описание, клиент, срок). Суммы/тип
     * заблокированы в форме (disabledOn edit), остаток/списания не трогаются.
     * После правки срока — пересчитываем статус/состояние.
     */
    protected function afterSave(): void
    {
        $this->record->refreshStatus();
    }
}
