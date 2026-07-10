<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property CertificateType $type
 * @property CertificateStatus $status
 * @property int|null $remaining_visits
 * @property float|null $remaining_amount
 */
class Certificate extends Model
{
    protected $fillable = [
        'number',
        'client_first_name',
        'client_last_name',
        'client_phone',
        'type',
        'initial_visits',
        'initial_amount',
        'remaining_visits',
        'remaining_amount',
        'sold_at',
        'expires_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'type' => CertificateType::class,
            'status' => CertificateStatus::class,
            'initial_visits' => 'integer',
            'remaining_visits' => 'integer',
            'initial_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'sold_at' => 'date',
            'expires_at' => 'date',
        ];
    }

    /**
     * @return HasMany<CertificateOperation>
     */
    public function operations(): HasMany
    {
        return $this->hasMany(CertificateOperation::class)->latest();
    }

    /**
     * @return HasMany<Visit>
     */
    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    /**
     * Сертификаты, которыми можно оплатить сейчас: активны, не истекли, есть остаток.
     *
     * @param  Builder<Certificate>  $query
     */
    public function scopeUsable(Builder $query): void
    {
        $query->where('status', CertificateStatus::Active->value)
            ->whereDate('expires_at', '>=', now()->toDateString())
            ->where(function (Builder $q): void {
                $q->where('remaining_visits', '>', 0)
                    ->orWhere('remaining_amount', '>', 0);
            });
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast() && ! $this->expires_at->isToday();
    }

    /**
     * Пересчитать статус по остатку и сроку.
     */
    public function refreshStatus(): void
    {
        $remaining = $this->type === CertificateType::Visits
            ? (int) $this->remaining_visits
            : (float) $this->remaining_amount;

        if ($remaining <= 0) {
            $this->status = CertificateStatus::Used;
        } elseif ($this->isExpired()) {
            $this->status = CertificateStatus::Expired;
        } else {
            $this->status = CertificateStatus::Active;
        }

        $this->save();
    }

    /**
     * Остаток человекочитаемо.
     */
    public function remainingLabel(): string
    {
        return $this->type === CertificateType::Visits
            ? $this->remaining_visits.' из '.$this->initial_visits.' посещ.'
            : number_format((float) $this->remaining_amount, 2, '.', ' ').' р';
    }

    /**
     * ФИО клиента (если заполнено).
     */
    public function clientLabel(): string
    {
        $name = trim(($this->client_last_name ?? '').' '.($this->client_first_name ?? ''));

        return $name !== '' ? $name : '—';
    }

    /**
     * Короткая подпись для выпадающего списка при оплате (помещается на мобилке).
     */
    public function selectLabel(): string
    {
        $remaining = $this->type === CertificateType::Visits
            ? 'ост. '.$this->remaining_visits.'/'.$this->initial_visits
            : 'ост. '.rtrim(rtrim(number_format((float) $this->remaining_amount, 2, '.', ''), '0'), '.').' р';

        return '№'.$this->number.' · '.$remaining.' · до '.$this->expires_at->format('d.m.y');
    }
}
