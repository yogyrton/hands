<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
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

    /**
     * @return HasMany<Visit>
     */
    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    /**
     * Заработано мастером за календарный месяц — сумма услуг по посещениям.
     */
    public function earnedInMonth(int $year, int $month): float
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();

        return (float) $this->visits()
            ->whereBetween('performed_at', [$start, (clone $start)->endOfMonth()])
            ->sum('service_price');
    }

    /**
     * Начислено мастеру за месяц: заработано × ставка.
     */
    public function accruedInMonth(int $year, int $month): float
    {
        return round($this->earnedInMonth($year, $month) * (float) $this->salary_rate / 100, 2);
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

        // Уменьшенный вариант для мобильных (адаптивный srcset).
        $this->addMediaConversion('webp_sm')
            ->fit(Fit::Max, 700, 700)
            ->format('webp')
            ->quality(80)
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

    /**
     * srcset (мелкий + крупный webp) для главного фото. Пусто, если конверсий нет.
     */
    public function mainSrcset(): string
    {
        $media = $this->getFirstMedia('main');

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
