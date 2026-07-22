<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Запись о зарплате мастера за период. «Заработано» и «начислено (%)» —
 * вычисляются из посещений; аванс/зп/даты вносит админ вручную.
 *
 * @property int $id
 * @property int $payroll_period_id
 * @property int $master_id
 * @property Carbon|null $advance_date
 * @property float|null $advance_amount
 * @property Carbon|null $salary_date
 * @property float|null $salary_amount
 * @property string|null $comment
 * @property-read PayrollPeriod $period
 * @property-read Master $master
 */
class MasterPayout extends Model
{
    protected $fillable = [
        'payroll_period_id',
        'master_id',
        'advance_date',
        'advance_amount',
        'salary_date',
        'salary_amount',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'advance_date' => 'date',
            'advance_amount' => 'decimal:2',
            'salary_date' => 'date',
            'salary_amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<PayrollPeriod, MasterPayout>
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    /**
     * @return BelongsTo<Master, MasterPayout>
     */
    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class);
    }

    /**
     * Заработано мастером за месяц периода (сумма услуг по посещениям).
     */
    public function earned(): float
    {
        if ($this->master === null || $this->period === null) {
            return 0.0;
        }

        return $this->master->earnedInMonth($this->period->year, $this->period->month);
    }

    /**
     * Начислено к выплате: заработано × ставка мастера.
     */
    public function accrued(): float
    {
        if ($this->master === null) {
            return 0.0;
        }

        return round($this->earned() * (float) $this->master->salary_rate / 100, 2);
    }

    /**
     * Выплачено: аванс + окончательная зарплата.
     */
    public function paid(): float
    {
        return (float) $this->advance_amount + (float) $this->salary_amount;
    }

    /**
     * Долг по зарплате: начислено − выплачено.
     */
    public function debt(): float
    {
        return round($this->accrued() - $this->paid(), 2);
    }
}
