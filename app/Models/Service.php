<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Услуга: витрина сайта + учёт (base_price).
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property int $level
 * @property float $base_price
 * @property array $includes
 * @property array $requests
 * @property array $details
 * @property bool $is_active
 */
class Service extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'slug',
        'name',
        'level',
        'base_price',
        'duration_label',
        'price_label',
        'lead',
        'ideal',
        'request_lead',
        'includes',
        'requests',
        'details',
        'seo_title',
        'seo_description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'base_price' => 'decimal:2',
            'includes' => 'array',
            'requests' => 'array',
            'details' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * На публичных страницах доступны только активные услуги.
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->where($field ?? 'slug', $value)
            ->where('is_active', true)
            ->firstOrFail();
    }

    /**
     * @return BelongsToMany<Master>
     */
    public function masters(): BelongsToMany
    {
        return $this->belongsToMany(Master::class)->orderBy('sort_order');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('card')->singleFile();   // карточка на главной
        $this->addMediaCollection('hero')->singleFile();   // шапка страницы услуги
    }

    public function cardUrl(): string
    {
        return $this->getFirstMediaUrl('card');
    }

    public function heroUrl(): string
    {
        return $this->getFirstMediaUrl('hero');
    }
}
