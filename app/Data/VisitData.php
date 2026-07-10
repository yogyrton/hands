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
        public ?string $comment = null,
    ) {}
}
