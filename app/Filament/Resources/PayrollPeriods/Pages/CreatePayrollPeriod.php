<?php

namespace App\Filament\Resources\PayrollPeriods\Pages;

use App\Filament\Resources\PayrollPeriods\PayrollPeriodResource;
use App\Models\Master;
use App\Models\PayrollPeriod;
use Filament\Resources\Pages\CreateRecord;

class CreatePayrollPeriod extends CreateRecord
{
    protected static string $resource = PayrollPeriodResource::class;

    /**
     * После создания месяца сразу заводим строки на всех активных мастеров:
     * «заработано» и «начислено» подтянутся из посещений, дата зарплаты — 10-е
     * число следующего месяца. Админу остаётся вбить только суммы/даты выплат.
     */
    protected function afterCreate(): void
    {
        /** @var PayrollPeriod $period */
        $period = $this->record;
        $salaryDate = $period->defaultSalaryDate();

        Master::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->each(fn (Master $master) => $period->payouts()->create([
                'master_id' => $master->id,
                'salary_date' => $salaryDate,
            ]));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
