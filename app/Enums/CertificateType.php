<?php

declare(strict_types=1);

namespace App\Enums;

enum CertificateType: string
{
    case Visits = 'visits';
    case Money = 'money';

    public function label(): string
    {
        return match ($this) {
            self::Visits => 'На посещения',
            self::Money => 'На сумму',
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
