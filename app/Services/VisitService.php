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
        protected VisitRepositoryInterface $visitRepository,
    ) {
        parent::__construct($visitRepository);
    }

    public function register(VisitData $data): Visit
    {
        return DB::transaction(function () use ($data): Visit {
            $servicePrice = round($data->service_price, 2);
            $paid = 0.0;
            $paymentType = $data->payment_type;
            $surchargeMethod = null;
            $certificate = null;
            $operationAmount = null;

            if ($data->certificate_id !== null) {
                /** @var Certificate $certificate */
                $certificate = Certificate::query()
                    ->whereKey($data->certificate_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($certificate->type === CertificateType::Visits) {
                    // Списываем 1 посещение. Итоговую (цену этого сеанса по серту) вводит оператор —
                    // от неё и считается зарплата, а не от суммы всего сертификата.
                    $certificate->remaining_visits = max(0, (int) $certificate->remaining_visits - 1);
                    $operationAmount = -1.0;
                    $paid = 0.0;
                    $paymentType = PaymentType::Certificate;
                } else {
                    $remaining = (float) $certificate->remaining_amount;
                    $surcharge = round(max(0.0, $data->surcharge_amount), 2);

                    // Сертификатом покрываем итоговую за вычетом доплаты, но не больше остатка.
                    $cover = round($servicePrice - $surcharge, 2);
                    $cover = max(0.0, min($cover, $remaining));

                    $certificate->remaining_amount = round($remaining - $cover, 2);
                    $operationAmount = -$cover;

                    if ($surcharge > 0) {
                        $paid = $surcharge;
                        $paymentType = PaymentType::CertificateSurcharge;
                        $surchargeMethod = $data->surcharge_payment_type ?? PaymentType::Cash;
                    } else {
                        $paid = 0.0;
                        $paymentType = PaymentType::Certificate;
                    }
                }

                $certificate->save();
                $certificate->refreshStatus();
            } elseif ($data->external_certificate_number !== null) {
                // Оплата «старым» сертификатом (из Excel) — записи серта в БД нет,
                // списывать нечего. В выручку тело серта не идёт (деньги получены при
                // внешней продаже). Живыми деньгами — только доплата, если есть.
                $surcharge = round(max(0.0, $data->surcharge_amount), 2);
                $paymentType = PaymentType::CertificateExternal;
                $paid = $surcharge;
                $surchargeMethod = $surcharge > 0 ? ($data->surcharge_payment_type ?? PaymentType::Cash) : null;
            } else {
                // Без сертификата — обычно оплачена вся стоимость услуги.
                // При «особых условиях» оператор может пробить по кассе иную сумму
                // (напр. владелец платит только долю мастера): в выручку/налог идёт
                // именно она, а зарплата мастера считается от полной итоговой.
                $paid = $data->special_paid_amount ?? $servicePrice;
            }

            $visit = Visit::create([
                'master_id' => $data->master_id,
                'service_id' => $data->service_id,
                'duration_minutes' => $data->duration_minutes,
                'base_price' => round($data->base_price, 2),
                'service_price' => $servicePrice,
                'paid_amount' => $paid,
                'payment_type' => $paymentType,
                'surcharge_payment_type' => $surchargeMethod,
                'discount_reason' => $data->discount_reason,
                'certificate_id' => $certificate?->id,
                'external_certificate_number' => $data->external_certificate_number,
                'promotion_id' => $data->promotion_id,
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

    /**
     * Редактирование посещения без сертификата (исправление опечатки оператора).
     * Меняются суммы/оплата/акция/особые условия; зарплата и выручка пересчитываются
     * от новых значений автоматически (считаются от service_price / paid_amount).
     *
     * Визиты по сертификату сюда не пускаем: изменение суммы затронуло бы списание
     * с сертификата — такие правим через удаление (с откатом) и создание заново.
     */
    public function edit(Visit $visit, VisitData $data): Visit
    {
        if ($visit->certificate_id !== null || $visit->external_certificate_number !== null) {
            throw new \RuntimeException('Посещение по сертификату не редактируется — удалите его (списание вернётся на сертификат) и создайте заново.');
        }

        $servicePrice = round($data->service_price, 2);

        $visit->update([
            'master_id' => $data->master_id,
            'service_id' => $data->service_id,
            'duration_minutes' => $data->duration_minutes,
            'base_price' => round($data->base_price, 2),
            'service_price' => $servicePrice,
            // Как и при создании: обычно оплачена вся стоимость; при «особых условиях»
            // по кассе идёт заданная сумма, а зарплата — всё равно от service_price.
            'paid_amount' => $data->special_paid_amount ?? $servicePrice,
            'payment_type' => $data->payment_type,
            'discount_reason' => $data->discount_reason,
            'promotion_id' => $data->promotion_id,
            'comment' => $data->comment,
            // performed_at не трогаем — сохраняем исходную дату/время посещения.
        ]);

        return $visit->fresh();
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
