<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentType: string
{
    case Cash = 'cash';
    case Card = 'card';
    case Mixed = 'mixed';
    case Certificate = 'certificate';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Наличные',
            self::Card => 'Карта',
            self::Mixed => 'Смешанно',
            self::Certificate => 'Сертификат',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }

    /**
     * Способы оплаты живыми деньгами (для доплаты/обычной оплаты).
     *
     * @return array<string, string>
     */
    public static function moneyOptions(): array
    {
        return [
            self::Cash->value => self::Cash->label(),
            self::Card->value => self::Card->label(),
            self::Mixed->value => self::Mixed->label(),
        ];
    }
}
