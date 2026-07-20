<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use App\Enums\UserRole;
use App\Filament\Resources\Certificates\Pages\ListCertificates;
use App\Models\Certificate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class CertificateFilterTest extends TestCase
{
    use RefreshDatabase;

    private function cert(string $number, Carbon $expiresAt, CertificateStatus $status): Certificate
    {
        return Certificate::create([
            'number' => $number,
            'type' => CertificateType::Money,
            'initial_amount' => 100,
            'remaining_amount' => $status === CertificateStatus::Used ? 0 : 100,
            'sold_at' => now()->subMonth()->toDateString(),
            'expires_at' => $expiresAt->toDateString(),
            'status' => $status,
        ]);
    }

    /**
     * Фильтр «Истекающие»: показывает только активные сертификаты, срок которых
     * заканчивается в течение месяца и ещё не истёк.
     */
    public function test_expiring_filter_shows_only_active_soon_to_expire(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $soon = $this->cert('SOON', now()->addDays(10), CertificateStatus::Active);       // попадает
        $far = $this->cert('FAR', now()->addMonths(2), CertificateStatus::Active);        // ещё рано
        $expired = $this->cert('EXPIRED', now()->subDay(), CertificateStatus::Expired);   // уже истёк
        $usedSoon = $this->cert('USED', now()->addDays(10), CertificateStatus::Used);     // использован

        Livewire::test(ListCertificates::class)
            ->filterTable('expiring', true)
            ->assertCanSeeTableRecords([$soon])
            ->assertCanNotSeeTableRecords([$far, $expired, $usedSoon]);
    }
}
