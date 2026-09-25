<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use App\Enums\UserRole;
use App\Filament\Resources\Certificates\Pages\ListCertificates;
use App\Filament\Resources\Certificates\Widgets\CertificateStats;
use App\Models\Certificate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class CertificateCategoryFilterTest extends TestCase
{
    use RefreshDatabase;

    private function cert(string $number, string $status, string $expires, float $remaining): Certificate
    {
        return Certificate::create([
            'number' => $number,
            'type' => CertificateType::Money,
            'status' => $status,
            'initial_amount' => 100,
            'remaining_amount' => $remaining,
            'sold_at' => now()->subMonths(2),
            'expires_at' => $expires,
        ]);
    }

    public function test_category_filter_splits_certificates(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $active = $this->cert('A', CertificateStatus::Active->value, now()->addMonth()->toDateString(), 100);
        $realized = $this->cert('R', CertificateStatus::Used->value, now()->addMonth()->toDateString(), 0);
        $burned = $this->cert('B', CertificateStatus::Active->value, now()->subDay()->toDateString(), 50);

        Livewire::test(ListCertificates::class)
            ->filterTable('category', 'active')
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$realized, $burned]);

        Livewire::test(ListCertificates::class)
            ->filterTable('category', 'realized')
            ->assertCanSeeTableRecords([$realized])
            ->assertCanNotSeeTableRecords([$active, $burned]);

        Livewire::test(ListCertificates::class)
            ->filterTable('category', 'burned')
            ->assertCanSeeTableRecords([$burned])
            ->assertCanNotSeeTableRecords([$active, $realized]);
    }

    public function test_stats_widget_renders_clickable_categories(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        Carbon::setTestNow('2026-09-15');

        $this->cert('B', CertificateStatus::Active->value, now()->subDay()->toDateString(), 50);

        Livewire::test(CertificateStats::class)
            ->assertOk()
            ->assertSee('Сгорели неиспользованными')
            ->assertSee('Реализованные');

        Carbon::setTestNow();
    }
}
