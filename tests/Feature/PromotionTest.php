<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\Services\CertificateServiceInterface;
use App\Contracts\Services\VisitServiceInterface;
use App\Data\CertificateData;
use App\Data\VisitData;
use App\Enums\CertificateType;
use App\Enums\UserRole;
use App\Filament\Pages\Reports;
use App\Filament\Resources\Visits\Pages\CreateVisit;
use App\Models\Master;
use App\Models\Promotion;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PromotionTest extends TestCase
{
    use RefreshDatabase;

    private function master(float $rate = 35): Master
    {
        return Master::create([
            'slug' => 'm-'.uniqid(), 'name' => 'Мастер', 'name_dative' => 'Мастеру',
            'role' => 'Массажист', 'yclients_url' => 'https://e.com', 'bio1' => 'a', 'bio2' => 'b',
            'salary_rate' => $rate,
        ]);
    }

    private function service(float $price = 100): Service
    {
        return Service::create([
            'slug' => 's-'.uniqid(), 'name' => 'Услуга', 'level' => 4, 'base_price' => $price,
            'lead' => 'lead',
        ]);
    }

    public function test_apply_to_computes_discounted_price(): void
    {
        $promo = Promotion::create(['title' => 'Ранняя пташка', 'discount_percent' => 10]);

        $this->assertEquals(90.0, $promo->applyTo(100));
        $this->assertEquals(54.0, $promo->applyTo(60));
        // Округление ВНИЗ до целых рублей: 65 −10% = 58,50 → 58.
        $this->assertEquals(58.0, $promo->applyTo(65));
    }

    public function test_visit_with_promotion_stores_it_and_salary_from_discounted(): void
    {
        $promo = Promotion::create(['title' => 'Ранняя пташка', 'discount_percent' => 10]);

        // Форма уже подставила итоговую со скидкой (100 → 90).
        $visit = app(VisitServiceInterface::class)->register(VisitData::from([
            'master_id' => $this->master()->id,
            'service_id' => $this->service(100)->id,
            'base_price' => 100, 'service_price' => 90,
            'promotion_id' => $promo->id,
        ]));

        $this->assertSame($promo->id, $visit->promotion_id);
        $this->assertEquals(90, $visit->paid_amount);
        $this->assertEquals(31.5, $visit->salaryAmount());   // 35% от 90 (мастер делит скидку)
        $this->assertEquals(10, $visit->discountAmount());   // 100 − 90
    }

    public function test_promotion_then_certificate_deducts_discounted_amount(): void
    {
        $promo = Promotion::create(['title' => 'Ранняя пташка', 'discount_percent' => 10]);
        $cert = app(CertificateServiceInterface::class)->issue(
            CertificateData::from(['type' => CertificateType::Money, 'initial_amount' => 100]),
        );

        // Скидка 100 → 90, оплата денежным сертификатом: списывается 90.
        $visit = app(VisitServiceInterface::class)->register(VisitData::from([
            'master_id' => $this->master()->id,
            'service_id' => $this->service(100)->id,
            'base_price' => 100, 'service_price' => 90,
            'promotion_id' => $promo->id,
            'certificate_id' => $cert->id,
        ]));

        $cert->refresh();
        $this->assertEquals(10, $cert->remaining_amount);   // 100 − 90
        $this->assertEquals(0, $visit->paid_amount);
        $this->assertSame($promo->id, $visit->promotion_id);
    }

    public function test_create_visit_form_recalculates_price_from_promotion(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $master = $this->master();
        $service = $this->service(100);
        $promo = Promotion::create(['title' => 'Ранняя пташка', 'discount_percent' => 10]);

        Livewire::test(CreateVisit::class)
            ->fillForm([
                'master_id' => $master->id,
                'service_id' => $service->id,
                'use_promotion' => true,
                'promotion_id' => $promo->id,
                'payment_type' => 'cash',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $visit = Visit::query()->firstOrFail();
        $this->assertSame($promo->id, $visit->promotion_id);
        $this->assertEquals(90, $visit->service_price);   // форма пересчитала 100 → 90
        $this->assertEquals(90, $visit->paid_amount);
    }

    public function test_report_by_promotion_aggregates_period(): void
    {
        $promo = Promotion::create(['title' => 'Ранняя пташка', 'discount_percent' => 10]);
        $master = $this->master();
        $service = $this->service(100);

        foreach ([90, 90] as $price) {
            app(VisitServiceInterface::class)->register(VisitData::from([
                'master_id' => $master->id,
                'service_id' => $service->id,
                'base_price' => 100, 'service_price' => $price,
                'promotion_id' => $promo->id,
            ]));
        }

        $report = app(Reports::class);
        $report->data = ['from' => now()->startOfMonth()->toDateString(), 'until' => now()->toDateString(), 'master_id' => null];

        $rows = $report->byPromotion();
        $this->assertCount(1, $rows);
        $this->assertSame('Ранняя пташка', $rows[0]['title']);
        $this->assertSame(2, $rows[0]['count']);
        $this->assertEquals(180, $rows[0]['paid']);      // 90 + 90 деньгами
        $this->assertEquals(20, $rows[0]['discount']);   // (100−90) × 2
    }

    public function test_active_promotion_shown_on_home_inactive_hidden(): void
    {
        Promotion::create(['title' => 'АктивАкция', 'discount_percent' => 10, 'is_active' => true]);
        Promotion::create(['title' => 'СкрытаяАкция', 'discount_percent' => 15, 'is_active' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('АктивАкция')
            ->assertDontSee('СкрытаяАкция');
    }
}
