<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Filament\Widgets\RevenueWithCertsByMonthChart;
use App\Models\Certificate;
use App\Models\Master;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class RevenueWithCertsByMonthChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_revenue_includes_certificate_sales(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        Carbon::setTestNow('2026-09-15');

        $master = Master::create([
            'slug' => 'm-1', 'name' => 'Анна', 'name_dative' => 'Анне', 'role' => 'Массажист',
            'yclients_url' => 'https://e.com', 'bio1' => 'a', 'bio2' => 'b', 'salary_rate' => 35, 'is_active' => true,
        ]);
        $service = Service::create(['slug' => 's-1', 'name' => 'Услуга', 'level' => 4, 'lead' => 'l']);

        // Массаж за деньги 200 (касса) + продан сертификат на 300 в этом же месяце.
        Visit::create([
            'master_id' => $master->id, 'service_id' => $service->id,
            'base_price' => 200, 'service_price' => 200, 'paid_amount' => 200,
            'payment_type' => PaymentType::Cash, 'performed_at' => Carbon::create(2026, 9, 10, 12),
        ]);
        Certificate::create([
            'number' => '900', 'type' => CertificateType::Money, 'status' => CertificateStatus::Active,
            'initial_amount' => 300, 'remaining_amount' => 300,
            'sold_at' => Carbon::create(2026, 9, 5), 'expires_at' => Carbon::create(2027, 9, 5),
        ]);

        $method = new ReflectionMethod(RevenueWithCertsByMonthChart::class, 'getData');
        $method->setAccessible(true);
        $data = $method->invoke(app(RevenueWithCertsByMonthChart::class));

        // Выручка сентября = 200 (касса) + 300 (серты) = 500.
        $this->assertSame('сен 26', $data['labels'][0]);
        $this->assertSame(500.0, $data['datasets'][0]['data'][0]);
    }

    public function test_renders_for_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        Livewire::test(RevenueWithCertsByMonthChart::class)
            ->assertOk()
            ->assertSee('с учётом сертификатов');
    }
}
