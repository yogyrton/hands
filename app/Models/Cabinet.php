<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Кабинет студии: название, описание и до 3 фото (карусель на главной).
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property int $sort_order
 */
class Cabinet extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        // До 3 фото кабинета — карусель на главной.
        $this->addMediaCollection('photos')->useDisk('public');
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

    /**
     * URL фото (webp-конверсия, если готова) — до 3 штук по порядку загрузки.
     *
     * @return list<string>
     */
    public function photoUrls(): array
    {
        return $this->getMedia('photos')
            ->take(3)
            ->map(fn (Media $media): string => $media->hasGeneratedConversion('webp')
                ? $media->getUrl('webp')
                : $media->getUrl())
            ->values()
            ->all();
    }
}
