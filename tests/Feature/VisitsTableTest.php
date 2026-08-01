<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Filament\Resources\Visits\Pages\ListVisits;
use App\Models\Master;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class VisitsTableTest extends TestCase
{
    use RefreshDatabase;

    private function visitOn(Carbon $when): Visit
    {
        $master = Master::create([
            'slug' => 'm-'.uniqid(), 'name' => 'Анна', 'name_dative' => 'Анне',
            'role' => 'Массажист', 'yclients_url' => 'https://e.com', 'bio1' => 'a', 'bio2' => 'b',
            'salary_rate' => 35,
        ]);
        $service = Service::create([
            'slug' => 's-'.uniqid(), 'name' => 'Массаж', 'level' => 4, 'base_price' => 65, 'lead' => 'l',
        ]);

        return Visit::create([
            'master_id' => $master->id, 'service_id' => $service->id,
            'base_price' => 65, 'service_price' => 65, 'paid_amount' => 65,
            'payment_type' => PaymentType::Cash, 'performed_at' => $when,
        ]);
    }

    public function test_visits_table_defaults_to_today_only(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $today = $this->visitOn(now());
        $yesterday = $this->visitOn(now()->subDay());

        Livewire::test(ListVisits::class)
            ->assertCanSeeTableRecords([$today])
            ->assertCanNotSeeTableRecords([$yesterday]);
    }
}
