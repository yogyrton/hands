<?php

declare(strict_types=1);

namespace App\Enums;

enum CertificateOperationType: string
{
    case Sale = 'sale';
    case Usage = 'usage';
    case Correction = 'correction';

    public function label(): string
    {
        return match ($this) {
            self::Sale => 'Продажа',
            self::Usage => 'Списание',
            self::Correction => 'Корректировка',
        };
    }
}
