<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Visit;
use App\Support\WorktimeCalculator;
use Illuminate\Console\Command;

/**
 * Дозаполняет длительность (duration_minutes) у старых посещений, где она не
 * указана: определяет её из прайса по услуге, базовой цене и должности мастера
 * (та же логика, что и в отчётах). С --dry только показывает, ничего не меняя.
 */
class BackfillVisitDurations extends Command
{
    protected $signature = 'visits:backfill-durations {--dry : только показать результат, ничего не менять}';

    protected $description = 'Заполнить длительность у старых посещений по цене из прайса';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');

        $visits = Visit::query()
            ->whereNull('duration_minutes')
            ->with(['service.prices', 'master'])
            ->get();

        if ($visits->isEmpty()) {
            $this->info('Посещений без длительности нет — заполнять нечего.');

            return self::SUCCESS;
        }

        $filled = 0;
        $skippedRows = [];

        foreach ($visits as $visit) {
            $duration = WorktimeCalculator::inferDuration(
                $visit->service,
                (float) $visit->base_price,
                $visit->master?->tier,
            );

            if ($duration === null) {
                $skippedRows[] = [
                    $visit->id,
                    $visit->performed_at?->format('d.m.Y H:i') ?? '—',
                    $visit->master?->name ?? '—',
                    $visit->service?->name ?? '—',
                    number_format((float) $visit->base_price, 2, '.', ' ').' р',
                    WorktimeCalculator::inferFailReason($visit->service, (float) $visit->base_price),
                ];

                continue;
            }

            if (! $dry) {
                $visit->update(['duration_minutes' => $duration]);
            }

            $filled++;
        }

        $prefix = $dry ? '[сухой прогон] ' : '';
        $this->info($prefix."Определено по цене: {$filled}".($dry ? ' (будет заполнено)' : ' (заполнено)'));

        if ($skippedRows !== []) {
            $this->warn('Не удалось определить: '.count($skippedRows).'. Их можно проставить вручную:');
            $this->table(
                ['ID', 'Дата', 'Мастер', 'Услуга', 'Базовая цена', 'Причина'],
                $skippedRows,
            );
        }

        return self::SUCCESS;
    }
}
