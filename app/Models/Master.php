<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Мастер: профиль сайта + сотрудник учёта. Мягкое удаление (увольнение).
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string $name_dative
 * @property array $principles
 * @property bool $is_active
 */
class Master extends Model implements HasMedia
{
    use InteractsWithMedia;
    use SoftDeletes;

    protected $fillable = [
        'slug',
        'name',
        'name_dative',
        'role',
        'yclients_url',
        'experience_label',
        'bio1',
        'bio2',
        'principles',
        'salary_rate',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'principles' => 'array',
            'salary_rate' => 'decimal:2',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * На публичных страницах доступны только активные мастера.
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->where($field ?? 'slug', $value)
            ->where('is_active', true)
            ->firstOrFail();
    }

    /**
     * @return BelongsToMany<Service>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)->orderBy('sort_order');
    }

    /**
     * Только активные услуги мастера (для публичных страниц).
     *
     * @return BelongsToMany<Service>
     */
    public function activeServices(): BelongsToMany
    {
        return $this->services()->where('services.is_active', true);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('main')->useDisk('public')->singleFile();   // главное фото (главная + шапка)
        $this->addMediaCollection('gallery')->useDisk('public');              // до 3 фото на странице мастера
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
    }

    public function mainUrl(): string
    {
        $media = $this->getFirstMedia('main');

        if (! $media) {
            return '';
        }

        return $media->hasGeneratedConversion('webp')
            ? $media->getUrl('webp')
            : $media->getUrl();
    }
}
