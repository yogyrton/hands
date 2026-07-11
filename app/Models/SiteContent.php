<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Singleton-запись с медиа для главной страницы:
 * большое фото в шапке (hero) и фото в блоке «О студии».
 */
class SiteContent extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'seo_title',
        'seo_description',
    ];

    /**
     * Единственная запись контента главной.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('home_hero')->useDisk('public')->singleFile();   // большое фото в шапке
        $this->addMediaCollection('home_about')->useDisk('public')->singleFile();  // фото в блоке «О студии»
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('webp')
            ->fit(Fit::Max, 1920, 1920)
            ->format('webp')
            ->quality(82)
            ->nonQueued();
    }

    public function heroUrl(): string
    {
        return $this->mediaUrl('home_hero');
    }

    public function aboutUrl(): string
    {
        return $this->mediaUrl('home_about');
    }

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
}
