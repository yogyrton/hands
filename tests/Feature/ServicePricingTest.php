<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MasterTier;
use App\Enums\UserRole;
use App\Filament\Resources\Prices\Pages\EditPrice;
use App\Filament\Resources\Prices\Pages\ListPrices;
use App\Filament\Resources\Prices\RelationManagers\PricesRelationManager;
use App\Filament\Resources\Visits\Pages\CreateVisit;
use App\Models\Master;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServicePricingTest extends TestCase
{
    use RefreshDatabase;

    private function master(MasterTier $tier = MasterTier::Master): Master
    {
        return Master::create([
            'slug' => 'm-'.uniqid(), 'name' => 'Мастер', 'name_dative' => 'Мастеру',
            'role' => 'Массажист', 'tier' => $tier->value, 'yclients_url' => 'https://e.com',
            'bio1' => 'a', 'bio2' => 'b', 'salary_rate' => 35,
        ]);
    }

    private function serviceWithPrices(): Service
    {
        $service = Service::create([
            'slug' => 's-'.uniqid(), 'name' => 'Массаж', 'level' => 4, 'lead' => 'l',
        ]);
        $service->prices()->create(['duration_minutes' => 40, 'price_master' => 45, 'price_pro' => 60]);
        $service->prices()->create(['duration_minutes' => 60, 'price_master' => 55, 'price_pro' => 70]);

        return $service;
    }

    public function test_price_for_resolves_by_duration_and_tier(): void
    {
        $service = $this->serviceWithPrices();

        $this->assertEqualsWithDelta(55.0, $service->priceFor(60, MasterTier::Master), 0.001);
        $this->assertEqualsWithDelta(70.0, $service->priceFor(60, MasterTier::Pro), 0.001);
        $this->assertEqualsWithDelta(45.0, $service->priceFor(40, MasterTier::Master), 0.001);
        $this->assertNull($service->priceFor(90, MasterTier::Master));   // такой длительности нет
        $this->assertEqualsWithDelta(45.0, $service->minMasterPrice(), 0.001);
    }

    public function test_visit_form_autofills_price_for_master_tier(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $service = $this->serviceWithPrices();
        $master = $this->master(MasterTier::Master);

        Livewire::test(CreateVisit::class)
            ->fillForm([
                'master_id' => $master->id,
                'service_id' => $service->id,
                'duration_minutes' => 60,
                'payment_type' => 'cash',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $visit = Visit::query()->firstOrFail();
        $this->assertEqualsWithDelta(55.0, (float) $visit->service_price, 0.001);   // цена мастера
        $this->assertEqualsWithDelta(55.0, (float) $visit->base_price, 0.001);
    }

    public function test_visit_form_uses_pro_price_for_pro_master(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $service = $this->serviceWithPrices();
        $pro = $this->master(MasterTier::Pro);

        Livewire::test(CreateVisit::class)
            ->fillForm([
                'master_id' => $pro->id,
                'service_id' => $service->id,
                'duration_minutes' => 60,
                'payment_type' => 'cash',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertEqualsWithDelta(70.0, (float) Visit::query()->firstOrFail()->service_price, 0.001);   // цена про-мастера
    }

    public function test_visit_form_autofills_regardless_of_selection_order(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $service = $this->serviceWithPrices();
        $master = $this->master(MasterTier::Master);

        // Выбираем в «неправильном» порядке: длительность → услуга → мастер.
        Livewire::test(CreateVisit::class)
            ->set('data.service_id', $service->id)
            ->set('data.duration_minutes', 40)
            ->set('data.master_id', $master->id)
            ->assertFormSet(['base_price' => 45, 'service_price' => 45]);
    }

    public function test_price_admin_pages_load(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $service = $this->serviceWithPrices();

        Livewire::test(ListPrices::class)->assertOk()->assertSee('Массаж');
        Livewire::test(EditPrice::class, ['record' => $service->id])->assertOk();
    }

    public function test_price_row_created_via_relation_manager(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $service = Service::create([
            'slug' => 's-'.uniqid(), 'name' => 'Массаж', 'level' => 4, 'lead' => 'l',
        ]);

        Livewire::test(PricesRelationManager::class, [
            'ownerRecord' => $service,
            'pageClass' => EditPrice::class,
        ])
            ->callTableAction('create', data: [
                'duration_minutes' => 90,
                'price_master' => 85,
                'price_pro' => 110,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('service_prices', [
            'service_id' => $service->id,
            'duration_minutes' => 90,
            'price_master' => 85,
            'price_pro' => 110,
        ]);
    }
}
