<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use App\Enums\UserRole;
use App\Filament\Widgets\ExpiringCertificates;
use App\Models\Certificate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExpiringCertificatesWidgetTest extends TestCase
{
    use RefreshDatabase;

    private function cert(string $number, string $expiresAt, float $remaining = 100, string $status = 'active'): Certificate
    {
        return Certificate::create([
            'number' => $number,
            'type' => CertificateType::Money,
            'status' => $status === 'active' ? CertificateStatus::Active : CertificateStatus::Used,
            'initial_amount' => 100,
            'remaining_amount' => $remaining,
            'sold_at' => now()->subMonth(),
            'expires_at' => $expiresAt,
        ]);
    }

    public function test_shows_only_certificates_expiring_within_a_month_with_balance(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $soon = $this->cert('SOON', now()->addDays(10)->toDateString());      // попадает
        $far = $this->cert('FAR', now()->addMonths(3)->toDateString());       // слишком далеко
        $spent = $this->cert('SPENT', now()->addDays(5)->toDateString(), 0);  // без остатка
        $expired = $this->cert('OLD', now()->subDay()->toDateString());       // уже истёк

        Livewire::test(ExpiringCertificates::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$soon])
            ->assertCanNotSeeTableRecords([$far, $spent, $expired]);
    }

    public function test_heading_counts_expiring_certificates(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->cert('A', now()->addDays(3)->toDateString());
        $this->cert('B', now()->addDays(20)->toDateString());

        Livewire::test(ExpiringCertificates::class)
            ->assertOk()
            ->assertSee('Истекают в течение месяца · 2');
    }

    public function test_hidden_from_master(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Master]));
        $this->assertFalse(ExpiringCertificates::canView());

        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $this->assertTrue(ExpiringCertificates::canView());
    }
}
