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
     * Фильтр «Состояние» = Заканчивается: только серты со сроком в пределах месяца
     * (и ещё не истёкшие). Остаток на состояние не влияет.
     */
    public function test_condition_filter_ending_shows_only_soon_to_expire(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $soon = $this->cert('SOON', now()->addDays(10), CertificateStatus::Active);
        $far = $this->cert('FAR', now()->addMonths(2), CertificateStatus::Active);
        $expired = $this->cert('EXPIRED', now()->subDay(), CertificateStatus::Active);
        $usedSoon = $this->cert('USED', now()->addDays(10), CertificateStatus::Used);

        Livewire::test(ListCertificates::class)
            ->filterTable('condition', 'ending')
            ->assertCanSeeTableRecords([$soon, $usedSoon])  // состояние — по сроку, не по остатку
            ->assertCanNotSeeTableRecords([$far, $expired]);
    }

    /**
     * Фильтр «Состояние» = Истёк: только серты, срок которых уже прошёл.
     */
    public function test_condition_filter_expired_shows_only_expired(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $soon = $this->cert('SOON', now()->addDays(10), CertificateStatus::Active);
        $far = $this->cert('FAR', now()->addMonths(2), CertificateStatus::Active);
        $expired = $this->cert('EXPIRED', now()->subDay(), CertificateStatus::Active);

        Livewire::test(ListCertificates::class)
            ->filterTable('condition', 'expired')
            ->assertCanSeeTableRecords([$expired])
            ->assertCanNotSeeTableRecords([$soon, $far]);
    }

    /**
     * Фильтр «Состояние» = Активен: серты со сроком дальше месяца.
     */
    public function test_condition_filter_active_shows_only_far_dated(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $soon = $this->cert('SOON', now()->addDays(10), CertificateStatus::Active);
        $far = $this->cert('FAR', now()->addMonths(2), CertificateStatus::Active);
        $expired = $this->cert('EXPIRED', now()->subDay(), CertificateStatus::Active);

        Livewire::test(ListCertificates::class)
            ->filterTable('condition', 'active')
            ->assertCanSeeTableRecords([$far])
            ->assertCanNotSeeTableRecords([$soon, $expired]);
    }

    /**
     * Фильтр «Статус» — по остатку: Использован (остаток обнулён) / Неиспользован.
     */
    public function test_status_filter_splits_by_remaining(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $unused = $this->cert('UNUSED', now()->addDays(10), CertificateStatus::Active);
        $used = $this->cert('SPENT', now()->addDays(10), CertificateStatus::Used);

        Livewire::test(ListCertificates::class)
            ->filterTable('status', 'used')
            ->assertCanSeeTableRecords([$used])
            ->assertCanNotSeeTableRecords([$unused]);

        Livewire::test(ListCertificates::class)
            ->filterTable('status', 'unused')
            ->assertCanSeeTableRecords([$unused])
            ->assertCanNotSeeTableRecords([$used]);
    }
}
