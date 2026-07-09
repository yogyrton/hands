<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

class FaqData extends Data
{
    public function __construct(
        public string $question,
        public string $answer,
        public int $sort_order = 0,
        public bool $is_active = true,
    ) {}
}
