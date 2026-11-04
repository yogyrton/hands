<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Характер работы мастера — для строки состава в табеле учёта рабочего времени
 * («Петрова А.А. — массажист, основная работа»).
 */
enum EmploymentType: string
{
    case Main = 'main';
    case PartTime = 'part_time';

    public function label(): string
    {
        return match ($this) {
            self::Main => 'основная работа',
            self::PartTime => 'совместительство',
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
