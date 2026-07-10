<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Data\CertificateData;
use App\Models\Certificate;

/**
 * @extends BaseQueryServiceInterface<Certificate>
 */
interface CertificateServiceInterface extends BaseQueryServiceInterface
{
    /**
     * Выпустить сертификат: авто-номер, срок +3 мес, статус активен, операция продажи.
     */
    public function issue(CertificateData $data): Certificate;
}
