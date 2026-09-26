<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\Repositories\PromotionRepositoryInterface;
use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use App\Filament\Widgets\MonthlyFinanceOverview;
use App\Models\Certificate;
use App\Models\Promotion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionsAndCertLiabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_hides_internal_promotions(): void
    {
        Promotion::create(['slug' => 'pub', 'title' => 'Публичная', 'discount_percent' => 10, 'is_active' => true, 'show_on_site' => true, 'sort_order' => 1]);
        Promotion::create(['slug' => 'loyal', 'title' => 'Постоянный −15%', 'discount_percent' => 15, 'is_active' => true, 'show_on_site' => false, 'sort_order' => 2]);

        $siteItems = app(PromotionRepositoryInterface::class)->activeOrdered();

        $this->assertSame(['Публичная'], $siteItems->pluck('title')->all());
    }

    public function test_internal_promotion_still_applies_discount(): void
    {
        $loyal = Promotion::create(['slug' => 'loyal', 'title' => 'Постоянный −50%', 'discount_percent' => 50, 'is_active' => true, 'show_on_site' => false]);

        // 65 −50% = 32.5 → floor → 32.
        $this->assertSame(32.0, $loyal->applyTo(65));
    }

    public function test_outstanding_liability_counts_only_active_remaining(): void
    {
        // Денежный активный: остаток 40 из 100.
        Certificate::create([
            'number' => 'M1', 'type' => CertificateType::Money, 'status' => CertificateStatus::Active,
            'initial_amount' => 100, 'remaining_amount' => 40,
            'sold_at' => now(), 'expires_at' => now()->addYear(),
        ]);
        // На посещения активный: 1 из 4 сеансов остался, продан за 200 → доля 50.
        Certificate::create([
            'number' => 'V1', 'type' => CertificateType::Visits, 'status' => CertificateStatus::Active,
            'initial_amount' => 200, 'initial_visits' => 4, 'remaining_visits' => 1,
            'sold_at' => now(), 'expires_at' => now()->addYear(),
        ]);
        // Истёкший — не наше обязательство.
        Certificate::create([
            'number' => 'M2', 'type' => CertificateType::Money, 'status' => CertificateStatus::Expired,
            'initial_amount' => 500, 'remaining_amount' => 500,
            'sold_at' => now()->subYears(2), 'expires_at' => now()->subYear(),
        ]);

        // 40 (денежный) + 50 (1/4 × 200) = 90.
        $this->assertSame(90.0, Certificate::outstandingLiability());
    }

    public function test_delta_helper(): void
    {
        $w = new MonthlyFinanceOverview;

        $this->assertSame(['diff' => 20.0, 'up' => true, 'percent' => 20], $w->delta(120, 100));
        $this->assertSame(['diff' => -50.0, 'up' => false, 'percent' => -50], $w->delta(50, 100));
        // Прошлый месяц 0 → процент не считаем.
        $this->assertNull($w->delta(80, 0)['percent']);
    }
}
