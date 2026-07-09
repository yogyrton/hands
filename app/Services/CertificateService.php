<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\CertificateRepositoryInterface;
use App\Contracts\Services\CertificateServiceInterface;
use App\Data\CertificateData;
use App\Enums\CertificateOperationType;
use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use App\Models\Certificate;
use App\Models\CertificateOperation;
use Illuminate\Support\Facades\DB;

/**
 * @extends BaseQueryService<Certificate>
 */
class CertificateService extends BaseQueryService implements CertificateServiceInterface
{
    public function __construct(
        protected CertificateRepositoryInterface $certificates,
    ) {
        parent::__construct($certificates);
    }

    public function issue(CertificateData $data): Certificate
    {
        return DB::transaction(function () use ($data): Certificate {
            $soldAt = now()->startOfDay();

            $certificate = new Certificate;
            $certificate->type = $data->type;

            if ($data->type === CertificateType::Visits) {
                $certificate->initial_visits = $data->initial_visits;
                $certificate->remaining_visits = $data->initial_visits;
            } else {
                $certificate->initial_amount = $data->initial_amount;
                $certificate->remaining_amount = $data->initial_amount;
            }

            $certificate->sold_at = $soldAt;
            $certificate->expires_at = $soldAt->copy()->addMonths(3); // срок ставит система
            $certificate->status = CertificateStatus::Active;
            $certificate->save();

            // Авто-номер: начинается с 1 (id новой записи).
            $certificate->number = (string) $certificate->id;
            $certificate->save();

            CertificateOperation::create([
                'certificate_id' => $certificate->id,
                'visit_id' => null,
                'type' => CertificateOperationType::Sale,
                'amount' => $data->type === CertificateType::Visits
                    ? (float) $data->initial_visits
                    : (float) $data->initial_amount,
            ]);

            return $certificate;
        });
    }
}
