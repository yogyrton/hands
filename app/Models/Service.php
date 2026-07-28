<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

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

    /**
     * Только активные мастера, оказывающие услугу (для публичных страниц).
     *
     * @return BelongsToMany<Master>
     */
    public function activeMasters(): BelongsToMany
    {
        return $this->masters()->where('masters.is_active', true);
    }

    public function registerMediaCollections(): void
    {
        // Одно фото услуги: и карточка на главной, и шапка страницы услуги.
        $this->addMediaCollection('card')->useDisk('public')->singleFile();
    }

    /**
     * Конверсия в webp — генерируется при загрузке фото в админке.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('webp')
            ->fit(Fit::Max, 1600, 1600)
            ->format('webp')
            ->quality(82)
            ->nonQueued();

        // Уменьшенный вариант для мобильных (адаптивный srcset).
        $this->addMediaConversion('webp_sm')
            ->fit(Fit::Max, 700, 700)
            ->format('webp')
            ->quality(80)
            ->nonQueued();
    }

    public function cardUrl(): string
    {
        return $this->mediaUrl('card');
    }

    public function cardSrcset(): string
    {
        return $this->mediaSrcset('card');
    }

    public function heroUrl(): string
    {
        // Шапка страницы услуги использует то же фото, что и карточка.
        return $this->mediaUrl('card');
    }

    public function heroSrcset(): string
    {
        return $this->mediaSrcset('card');
    }

    /**
     * URL фото: отдаём webp-конверсию, если она сгенерирована, иначе оригинал.
     */
    private function mediaUrl(string $collection): string
    {
        $media = $this->getFirstMedia($collection);

        if (! $media) {
            return '';
        }

        return $media->hasGeneratedConversion('webp')
            ? $media->getUrl('webp')
            : $media->getUrl();
    }

    /**
     * srcset (мелкий + крупный webp) для адаптивной загрузки. Пусто, если конверсий нет.
     */
    private function mediaSrcset(string $collection): string
    {
        $media = $this->getFirstMedia($collection);

        if (! $media) {
            return '';
        }

        $set = [];

        if ($media->hasGeneratedConversion('webp_sm')) {
            $set[] = $media->getUrl('webp_sm').' 700w';
        }

        if ($media->hasGeneratedConversion('webp')) {
            $set[] = $media->getUrl('webp').' 1600w';
        }

        return implode(', ', $set);
    }
}
