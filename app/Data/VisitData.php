<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\PaymentType;
use Spatie\LaravelData\Data;

class VisitData extends Data
{
    public function __construct(
        public int $master_id,
        public int $service_id,
        public float $base_price,
        public float $service_price,
        public PaymentType $payment_type = PaymentType::Cash,
        public ?string $discount_reason = null,
        public ?int $certificate_id = null,
        public ?int $promotion_id = null,
        public float $surcharge_amount = 0.0,
        public ?PaymentType $surcharge_payment_type = null,
        public ?string $comment = null,
        // «Особые условия»: реальная сумма оплаты по кассе, если она отличается
        // от итоговой (напр. владелец платит только долю мастера). Зарплата
        // мастера всё равно считается от service_price. null — оплата = итоговой.
        public ?float $special_paid_amount = null,
    ) {}
}
