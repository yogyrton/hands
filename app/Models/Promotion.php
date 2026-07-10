<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Акция: витрина на сайте + скидка в учёте.
 *
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property int $discount_percent
 * @property bool $is_active
 * @property int $sort_order
 */
class Promotion extends Model
{
    protected $fillable = [
        'title',
        'description',
        'discount_percent',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'discount_percent' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<Visit>
     */
    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    /**
     * Итоговая цена после скидки этой акции.
     */
    public function applyTo(float $basePrice): float
    {
        return round($basePrice * (100 - $this->discount_percent) / 100, 2);
    }

    /**
     * Подпись для выпадающего списка при оформлении посещения.
     */
    public function selectLabel(): string
    {
        return $this->title.' · −'.$this->discount_percent.'%';
    }
}
