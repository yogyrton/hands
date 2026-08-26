<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Глобальные настройки студии (ссылки YClients, Instagram, карта и т.д.).
 * Редактируются в админке; на публичных страницах доступны как $studio['key'].
 *
 * @property string $key
 * @property string|null $value
 */
class Setting extends Model
{
    private const CACHE_KEY = 'studio_settings';

    protected $fillable = ['key', 'value'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * Все настройки как ассоциативный массив key => value (кеш).
     *
     * @return array<string, string|null>
     */
    public static function allKeyed(): array
    {
        return Cache::rememberForever(
            self::CACHE_KEY,
            static fn (): array => static::query()->pluck('value', 'key')->all(),
        );
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = self::allKeyed()[$key] ?? null;

        // Пустая строка (поле сохранили пустым) — тоже «нет значения»: отдаём дефолт.
        return ($value === null || $value === '') ? $default : $value;
    }
}
