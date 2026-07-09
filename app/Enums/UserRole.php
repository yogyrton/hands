<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Master = 'master';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Администратор',
            self::Master => 'Мастер',
        };
    }
}
