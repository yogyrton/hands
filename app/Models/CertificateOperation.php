<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CertificateOperationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * История сертификата: продажа / списание / корректировка.
 *
 * @property CertificateOperationType $type
 * @property float $amount
 */
class CertificateOperation extends Model
{
    protected $fillable = [
        'certificate_id',
        'visit_id',
        'type',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'type' => CertificateOperationType::class,
            'amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Certificate, CertificateOperation>
     */
    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    /**
     * @return BelongsTo<Visit, CertificateOperation>
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }
}
