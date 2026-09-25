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

    public function test_category_filter_splits_certificates(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $c = $this->sample();

        $cases = [
            'active' => 'active',
            'ending' => 'ending',
            'used' => 'used',
            'burned' => 'burned',
        ];

        foreach ($cases as $category => $expectedKey) {
            $others = array_values(array_diff_key($c, [$expectedKey => null]));

            Livewire::test(ListCertificates::class)
                ->filterTable('category', $category)
                ->assertCanSeeTableRecords([$c[$expectedKey]])
                ->assertCanNotSeeTableRecords($others);
        }
    }

    public function test_category_filter_defaults_from_url_param(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $c = $this->sample();

        // Ссылка с плашки статистики: ?category=burned
        Livewire::withQueryParams(['category' => 'burned'])
            ->test(ListCertificates::class)
            ->assertCanSeeTableRecords([$c['burned']])
            ->assertCanNotSeeTableRecords([$c['active'], $c['ending'], $c['used']]);
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
