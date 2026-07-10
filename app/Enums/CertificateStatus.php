<?php

declare(strict_types=1);

namespace App\Enums;

enum CertificateStatus: string
{
    case Active = 'active';
    case Used = 'used';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Активен',
            self::Used => 'Использован',
            self::Expired => 'Истёк',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Used => 'gray',
            self::Expired => 'danger',
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
