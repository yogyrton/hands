<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Посещение (оказанная услуга) — центральная сущность учёта.
 *
 * @property float $service_price
 * @property float $paid_amount
 * @property PaymentType $payment_type
 */
class Visit extends Model
{
    protected $fillable = [
        'master_id',
        'service_id',
        'base_price',
        'service_price',
        'paid_amount',
        'payment_type',
        'discount_reason',
        'certificate_id',
        'comment',
        'performed_at',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'service_price' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'payment_type' => PaymentType::class,
            'performed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Master, Visit>
     */
    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class);
    }

    /**
     * @return BelongsTo<Service, Visit>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return BelongsTo<Certificate, Visit>
     */
    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    /**
     * @return HasOne<CertificateOperation>
     */
    public function operation(): HasOne
    {
        return $this->hasOne(CertificateOperation::class);
    }

    /**
     * Зарплата мастера за это посещение (грязными): % ставки от стоимости услуги.
     */
    public function salaryAmount(): float
    {
        $rate = (float) ($this->master?->salary_rate ?? 0);

        return round((float) $this->service_price * $rate / 100, 2);
    }
}
