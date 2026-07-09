<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

class ServiceData extends Data
{
    /**
     * @param  array<int, array{n?: int, title: string, description: string}>  $includes
     * @param  array<int, string>  $requests
     * @param  array<int, array{title: string, body: string}>  $details
     */
    public function __construct(
        public string $slug,
        public string $name,
        public int $level,
        public float $base_price,
        public string $duration_label,
        public string $price_label,
        public string $lead,
        public string $ideal,
        public string $request_lead,
        public array $includes = [],
        public array $requests = [],
        public array $details = [],
        public ?string $seo_title = null,
        public ?string $seo_description = null,
        public int $sort_order = 0,
        public bool $is_active = true,
    ) {}
}
