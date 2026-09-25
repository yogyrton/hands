<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Filament\Pages\Reports;
use App\Models\Master;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportsCertificateCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_cert_covered_reflects_service_paid_by_certificate(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $master = Master::create([
            'slug' => 'm-1', 'name' => 'Анна', 'name_dative' => 'Анне', 'role' => 'Массажист',
            'yclients_url' => 'https://e.com', 'bio1' => 'a', 'bio2' => 'b', 'salary_rate' => 35, 'is_active' => true,
        ]);
        $service = Service::create(['slug' => 's-1', 'name' => 'Услуга', 'level' => 4, 'lead' => 'l']);
        $when = Carbon::create(2026, 9, 10, 12);

        // Визит за наличные 100.
        Visit::create([
            'master_id' => $master->id, 'service_id' => $service->id,
            'base_price' => 100, 'service_price' => 100, 'paid_amount' => 100,
            'payment_type' => PaymentType::Cash, 'performed_at' => $when,
        ]);
        // Визит полностью по сертификату (услуга 65, деньгами 0).
        Visit::create([
            'master_id' => $master->id, 'service_id' => $service->id,
            'base_price' => 65, 'service_price' => 65, 'paid_amount' => 0,
            'payment_type' => PaymentType::Certificate, 'performed_at' => $when,
        ]);

        $report = app(Reports::class);
        $report->data = ['from' => '2026-09-01', 'until' => '2026-09-30', 'master_id' => null];
        $rev = $report->revenue();

        $this->assertSame(100.0, $rev['total']);          // деньгами — только наличный визит
        $this->assertSame(65.0, $rev['cert_covered']);    // отхожено по серту на 65
        $this->assertSame(2, $rev['visits']);
    }
}
