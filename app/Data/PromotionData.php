<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

class PromotionData extends Data
{
    public function __construct(
        public string $title,
        public ?string $description = null,
        public int $discount_percent = 0,
        public bool $is_active = true,
        public int $sort_order = 0,
    ) {}
}
