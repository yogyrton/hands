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

    /**
     * @return array{active: Certificate, ending: Certificate, used: Certificate, burned: Certificate}
     */
    private function sample(): array
    {
        return [
            'active' => $this->cert('ACT', CertificateStatus::Active->value, now()->addMonths(3)->toDateString(), 100),
            'ending' => $this->cert('END', CertificateStatus::Active->value, now()->addDays(10)->toDateString(), 100),
            'used' => $this->cert('USE', CertificateStatus::Used->value, now()->addMonths(3)->toDateString(), 0),
            'burned' => $this->cert('BRN', CertificateStatus::Active->value, now()->subDay()->toDateString(), 50),
        ];
    }

    public function test_status_and_condition_filters_match_stat_categories(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $c = $this->sample();

        // Активные: непользованные + состояние active.
        Livewire::test(ListCertificates::class)
            ->filterTable('status', 'unused')
            ->filterTable('condition', 'active')
            ->assertCanSeeTableRecords([$c['active']])
            ->assertCanNotSeeTableRecords([$c['ending'], $c['used'], $c['burned']]);

        // Заканчиваются: непользованные + состояние ending.
        Livewire::test(ListCertificates::class)
            ->filterTable('status', 'unused')
            ->filterTable('condition', 'ending')
            ->assertCanSeeTableRecords([$c['ending']])
            ->assertCanNotSeeTableRecords([$c['active'], $c['used'], $c['burned']]);

        // Использованные: статус used.
        Livewire::test(ListCertificates::class)
            ->filterTable('status', 'used')
            ->assertCanSeeTableRecords([$c['used']])
            ->assertCanNotSeeTableRecords([$c['active'], $c['ending'], $c['burned']]);

        // Сгорели: непользованные + состояние expired.
        Livewire::test(ListCertificates::class)
            ->filterTable('status', 'unused')
            ->filterTable('condition', 'expired')
            ->assertCanSeeTableRecords([$c['burned']])
            ->assertCanNotSeeTableRecords([$c['active'], $c['ending'], $c['used']]);
    }

    public function test_filters_hydrate_from_url_like_stat_links(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $c = $this->sample();

        // Ровно та ссылка, что формирует плашка «Заканчиваются».
        Livewire::withQueryParams(['filters' => [
            'status' => ['value' => 'unused'],
            'condition' => ['value' => 'ending'],
        ]])
            ->test(ListCertificates::class)
            ->assertCanSeeTableRecords([$c['ending']])
            ->assertCanNotSeeTableRecords([$c['active'], $c['used'], $c['burned']]);
    }

    public function test_stats_widget_renders_all_categories(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        Carbon::setTestNow('2026-09-15');
        $this->sample();

        Livewire::test(CertificateStats::class)
            ->assertOk()
            ->assertSee('Активные')
            ->assertSee('Заканчиваются')
            ->assertSee('Использованные')
            ->assertSee('Сгорели неиспользованными');

        Carbon::setTestNow();
    }
}
