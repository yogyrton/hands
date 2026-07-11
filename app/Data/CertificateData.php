<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\CertificateType;
use Spatie\LaravelData\Data;

class CertificateData extends Data
{
    public function __construct(
        public CertificateType $type,
        public ?int $initial_visits = null,
        public ?float $initial_amount = null,
        public ?string $comment = null,
        public ?string $client_first_name = null,
        public ?string $client_last_name = null,
        public ?string $client_phone = null,
    ) {}
}
