<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

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
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'principles' => 'array',
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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('main')->singleFile();   // главное фото (главная + шапка)
        $this->addMediaCollection('gallery');              // до 3 фото на странице мастера
    }

    public function mainUrl(): string
    {
        return $this->getFirstMediaUrl('main');
    }
}
