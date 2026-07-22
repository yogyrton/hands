<?php

namespace App\Filament\Resources\ExpensePeriods\Pages;

use App\Filament\Resources\ExpensePeriods\ExpensePeriodResource;
use App\Models\ExpensePeriod;
use App\Models\Master;
use App\Models\Setting;
use Filament\Resources\Pages\CreateRecord;

class CreateExpensePeriod extends CreateRecord
{
    protected static string $resource = ExpensePeriodResource::class;

    /**
     * После создания месяца заводим типовые расходы из настроек и запись
     * «Зарплата мастерам + взносы» (грязная зп по посещениям + взносы ИП).
     */
    protected function afterCreate(): void
    {
        /** @var ExpensePeriod $period */
        $period = $this->record;

        $period->expenses()->createMany([
            ['title' => 'Аренда', 'amount' => (float) Setting::get('expense_rent', '1880')],
            ['title' => 'Квартплата', 'amount' => (float) Setting::get('expense_utilities', '200')],
            ['title' => 'Услуги бухгалтера', 'amount' => (float) Setting::get('expense_accountant', '250')],
        ]);

        $period->expenses()->create($this->salaryExpense($period));
    }

    /**
     * Строка зарплаты мастерам с взносами нанимателя и расшифровкой.
     *
     * @return array<string, mixed>
     */
    private function salaryExpense(ExpensePeriod $period): array
    {
        $contribRate = (float) Setting::get('contrib_fszn_percent', '34')
            + (float) Setting::get('contrib_belgosstrakh_percent', '0.6');

        $grossTotal = 0.0;
        $lines = [];

        foreach (Master::query()->where('is_active', true)->orderBy('sort_order')->get() as $master) {
            $earned = $master->earnedInMonth($period->year, $period->month);
            $accrued = $master->accruedInMonth($period->year, $period->month);

            if ($earned <= 0) {
                continue;
            }

            $contrib = round($accrued * $contribRate / 100, 2);
            $grossTotal += $accrued;
            $lines[] = sprintf(
                '%s: заработано %s, начислено (%s%%) = %s, взносы = %s',
                $master->name,
                $this->money($earned),
                rtrim(rtrim(number_format((float) $master->salary_rate, 2, '.', ''), '0'), '.'),
                $this->money($accrued),
                $this->money($contrib),
            );
        }

        $contribTotal = round($grossTotal * $contribRate / 100, 2);

        return [
            'title' => 'Зарплата мастерам + взносы',
            'amount' => round($grossTotal + $contribTotal, 2),
            'details' => $lines === []
                ? 'Нет посещений за месяц.'
                : implode("\n", $lines)
                    ."\n—\nИтого грязными: ".$this->money($grossTotal)
                    .', взносы ('.rtrim(rtrim(number_format($contribRate, 2, '.', ''), '0'), '.').'%): '.$this->money($contribTotal),
        ];
    }

    private function money(float $value): string
    {
        return number_format($value, 2, '.', ' ').' р';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
