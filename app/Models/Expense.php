<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Строка расхода за месяц: аренда, коммуналка, зарплата+взносы, прочее.
 * in_journal — учитывается ли в официальной (налоговой) прибыли.
 *
 * @property int $id
 * @property int $expense_period_id
 * @property string $title
 * @property float $amount
 * @property bool $in_journal
 * @property string|null $details
 * @property-read ExpensePeriod $period
 */
class Expense extends Model
{
    protected $fillable = [
        'expense_period_id',
        'title',
        'amount',
        'in_journal',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'in_journal' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ExpensePeriod, Expense>
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(ExpensePeriod::class, 'expense_period_id');
    }
}
