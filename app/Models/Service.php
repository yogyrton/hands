<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MasterTier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Услуга: витрина сайта + учёт. Ценами управляет прайс (service_prices).
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property int $level
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
     * Прайс услуги: строки по длительностям с ценами для мастера и про-мастера.
     *
     * @return HasMany<ServicePrice>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(ServicePrice::class)->orderBy('duration_minutes');
    }

    /**
     * Строка прайса для заданной длительности (или null).
     */
    public function priceRow(int $durationMinutes): ?ServicePrice
    {
        return $this->prices->firstWhere('duration_minutes', $durationMinutes);
    }

    /**
     * Цена услуги для длительности и должности мастера (или null, если строки нет).
     */
    public function priceFor(int $durationMinutes, MasterTier $tier): ?float
    {
        return $this->priceRow($durationMinutes)?->priceForTier($tier);
    }

    /**
     * Минимальная цена мастера по прайсу — для витрины/SEO («от …»).
     */
    public function minMasterPrice(): ?float
    {
        $min = $this->prices->min('price_master');

        return $min !== null ? (float) $min : null;
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
