<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentType;
use App\Models\Master;
use App\Models\Service;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillVisitDurationsTest extends TestCase
{
    use RefreshDatabase;

    private function master(): Master
    {
        return Master::create([
            'slug' => 'm-'.uniqid(), 'name' => 'Мастер', 'name_dative' => 'Мастеру',
            'role' => 'Массажист', 'yclients_url' => 'https://e.com', 'bio1' => 'a', 'bio2' => 'b', 'salary_rate' => 35,
        ]);
    }

    private function service(): Service
    {
        $service = Service::create(['slug' => 's-'.uniqid(), 'name' => 'Массаж', 'level' => 4, 'lead' => 'l']);
        $service->prices()->create(['duration_minutes' => 60, 'price_master' => 60, 'price_pro' => 80]);
        $service->prices()->create(['duration_minutes' => 90, 'price_master' => 85, 'price_pro' => 110]);

        return $service;
    }

    private function visit(Master $m, Service $s, ?int $duration, float $base): Visit
    {
        return Visit::create([
            'master_id' => $m->id, 'service_id' => $s->id, 'duration_minutes' => $duration,
            'base_price' => $base, 'service_price' => $base, 'paid_amount' => $base,
            'payment_type' => PaymentType::Cash, 'performed_at' => now(),
        ]);
    }

    public function test_backfills_duration_from_price(): void
    {
        $m = $this->master();
        $s = $this->service();

        $known = $this->visit($m, $s, 60, 60);      // уже есть — не трогаем
        $old90 = $this->visit($m, $s, null, 85);    // определится в 90 мин по цене
        $noMatch = $this->visit($m, $s, null, 33);  // такой цены в прайсе нет — останется null

        $this->artisan('visits:backfill-durations')
            ->expectsOutputToContain('нет цены')   // причина для непроставленного
            ->assertSuccessful();

        $this->assertSame(60, $known->fresh()->duration_minutes);   // не изменилось
        $this->assertSame(90, $old90->fresh()->duration_minutes);   // проставлено по цене
        $this->assertNull($noMatch->fresh()->duration_minutes);     // без совпадения — не трогаем
    }

    public function test_dry_run_changes_nothing(): void
    {
        $m = $this->master();
        $s = $this->service();
        $old = $this->visit($m, $s, null, 85);

        $this->artisan('visits:backfill-durations --dry')->assertSuccessful();

        $this->assertNull($old->fresh()->duration_minutes);   // сухой прогон ничего не меняет
    }
}
