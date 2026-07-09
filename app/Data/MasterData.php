<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

class MasterData extends Data
{
    /**
     * @param  array<int, array{title: string, description: string}>  $principles
     */
    public function __construct(
        public string $slug,
        public string $name,
        public string $name_dative,
        public string $role,
        public string $yclients_url,
        public string $experience_label,
        public string $bio1,
        public string $bio2,
        public array $principles = [],
        public int $sort_order = 0,
        public bool $is_active = true,
    ) {}
}
