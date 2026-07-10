<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\VisitRepositoryInterface;
use App\Contracts\Services\VisitServiceInterface;
use App\Data\VisitData;
use App\Enums\CertificateOperationType;
use App\Enums\CertificateType;
use App\Enums\PaymentType;
use App\Models\Certificate;
use App\Models\CertificateOperation;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

/**
 * @extends BaseQueryService<Visit>
 */
class VisitService extends BaseQueryService implements VisitServiceInterface
{
    public function __construct(
        protected VisitRepositoryInterface $visits,
    ) {
        parent::__construct($visits);
    }

    public function register(VisitData $data): Visit
    {
        return DB::transaction(function () use ($data): Visit {
            $servicePrice = round($data->service_price, 2);
            $paid = 0.0;
            $paymentType = $data->payment_type;
            $certificate = null;
            $operationAmount = null;

            if ($data->certificate_id !== null) {
                /** @var Certificate $certificate */
                $certificate = Certificate::query()
                    ->whereKey($data->certificate_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($certificate->type === CertificateType::Visits) {
                    $certificate->remaining_visits = max(0, (int) $certificate->remaining_visits - 1);
                    $operationAmount = -1.0;
                    $paid = 0.0;
                    $paymentType = PaymentType::Certificate;
                } else {
                    $remaining = (float) $certificate->remaining_amount;

                    if ($remaining >= $servicePrice) {
                        $certificate->remaining_amount = round($remaining - $servicePrice, 2);
                        $operationAmount = -$servicePrice;
                        $paid = 0.0;
                        $paymentType = PaymentType::Certificate;
                    } else {
                        // Сертификата не хватает — остаток списываем, разницу доплачивают деньгами.
                        $certificate->remaining_amount = 0;
                        $operationAmount = -$remaining;
                        $paid = round($servicePrice - $remaining, 2);
                        // $paymentType остаётся тем, что выбрал мастер (нал/карта/смешанно)
                    }
                }

                $certificate->save();
                $certificate->refreshStatus();
            } else {
                // Без сертификата — вся стоимость услуги оплачена деньгами.
                $paid = $servicePrice;
            }

            $visit = Visit::create([
                'master_id' => $data->master_id,
                'service_id' => $data->service_id,
                'base_price' => round($data->base_price, 2),
                'service_price' => $servicePrice,
                'paid_amount' => $paid,
                'payment_type' => $paymentType,
                'discount_reason' => $data->discount_reason,
                'certificate_id' => $certificate?->id,
                'comment' => $data->comment,
                'performed_at' => now(),
            ]);

            if ($certificate !== null && $operationAmount !== null) {
                CertificateOperation::create([
                    'certificate_id' => $certificate->id,
                    'visit_id' => $visit->id,
                    'type' => CertificateOperationType::Usage,
                    'amount' => $operationAmount,
                ]);
            }

            return $visit;
        });
    }

    public function deleteWithReversal(Visit $visit): void
    {
        DB::transaction(function () use ($visit): void {
            $operation = $visit->operation()->first();

            if ($operation !== null && $visit->certificate_id !== null) {
                /** @var Certificate|null $certificate */
                $certificate = Certificate::query()
                    ->whereKey($visit->certificate_id)
                    ->lockForUpdate()
                    ->first();

                if ($certificate !== null) {
                    if ($certificate->type === CertificateType::Visits) {
                        $restored = (int) $certificate->remaining_visits + (int) abs((float) $operation->amount);
                        $certificate->remaining_visits = min($restored, (int) $certificate->initial_visits);
                    } else {
                        $restored = round((float) $certificate->remaining_amount + abs((float) $operation->amount), 2);
                        $certificate->remaining_amount = min($restored, (float) $certificate->initial_amount);
                    }

                    $certificate->save();
                    $certificate->refreshStatus();
                }
            }

            // operation удалится каскадом по visit_id, но удалим явно для наглядности.
            $operation?->delete();
            $visit->delete();
        });
    }
}
