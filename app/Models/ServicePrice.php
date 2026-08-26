<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MasterTier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Строка прайса услуги: длительность сеанса и цены по должностям
 * (мастер / про мастер). Цена посещения подставляется отсюда.
 *
 * @property int $duration_minutes
 * @property float $price_master
 * @property float $price_pro
 */
class ServicePrice extends Model
{
    protected $fillable = [
        'service_id',
        'duration_minutes',
        'price_master',
        'price_pro',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'price_master' => 'decimal:2',
            'price_pro' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Service, ServicePrice>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Цена по должности мастера.
     */
    public function priceForTier(MasterTier $tier): float
    {
        return (float) ($tier === MasterTier::Pro ? $this->price_pro : $this->price_master);
    }

    public function durationLabel(): string
    {
        return $this->duration_minutes.' мин';
    }
}
