<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Должность мастера — определяет, какая цена из прайса применяется к посещению.
 */
enum MasterTier: string
{
    case Master = 'master';
    case Pro = 'pro';

    public function label(): string
    {
        return match ($this) {
            self::Master => 'Мастер',
            self::Pro => 'Про мастер',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
