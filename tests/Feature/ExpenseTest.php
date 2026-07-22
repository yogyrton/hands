<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Filament\Resources\ExpensePeriods\ExpensePeriodResource;
use App\Filament\Resources\ExpensePeriods\Pages\CreateExpensePeriod;
use App\Filament\Resources\ExpensePeriods\Pages\ViewExpensePeriod;
use App\Filament\Resources\ExpensePeriods\RelationManagers\ExpensesRelationManager;
use App\Models\ExpensePeriod;
use App\Models\Master;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    private function master(float $rate = 35): Master
    {
        return Master::create([
            'slug' => 'm-'.uniqid(), 'name' => 'Анна', 'name_dative' => 'Анне',
            'role' => 'Массажист', 'yclients_url' => 'https://e.com', 'bio1' => 'a', 'bio2' => 'b',
            'salary_rate' => $rate, 'is_active' => true,
        ]);
    }

    private function visit(Master $master, float $price, Carbon $when, PaymentType $type = PaymentType::Cash): Visit
    {
        $service = Service::create([
            'slug' => 's-'.uniqid(), 'name' => 'Услуга', 'level' => 4, 'base_price' => $price, 'lead' => 'l',
        ]);

        return Visit::create([
            'master_id' => $master->id, 'service_id' => $service->id,
            'base_price' => $price, 'service_price' => $price, 'paid_amount' => $price,
            'payment_type' => $type, 'performed_at' => $when,
        ]);
    }

    public function test_creating_month_autofills_expenses_from_settings_and_salary(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        Setting::create(['key' => 'expense_rent', 'value' => '1880']);
        Setting::create(['key' => 'expense_utilities', 'value' => '200']);
        Setting::create(['key' => 'expense_accountant', 'value' => '250']);
        Setting::create(['key' => 'contrib_fszn_percent', 'value' => '34']);
        Setting::create(['key' => 'contrib_belgosstrakh_percent', 'value' => '0.6']);

        $master = $this->master(35);
        $this->visit($master, 3000, Carbon::create(2026, 7, 10, 12));   // грязными 1050

        Livewire::test(CreateExpensePeriod::class)
            ->fillForm(['month' => 7, 'year' => 2026])
            ->call('create')
            ->assertHasNoFormErrors();

        $period = ExpensePeriod::query()->firstOrFail();
        $titles = $period->expenses()->pluck('title')->all();
        $this->assertContains('Аренда', $titles);
        $this->assertContains('Квартплата', $titles);
        $this->assertContains('Услуги бухгалтера', $titles);
        $this->assertContains('Зарплата мастерам + взносы', $titles);

        // Зарплатная строка: 1050 грязными + взносы 34.6% (≈363.30) = 1413.30.
        $salary = $period->expenses()->where('title', 'Зарплата мастерам + взносы')->firstOrFail();
        $this->assertEquals(1413.30, (float) $salary->amount);
        $this->assertStringContainsString('Анна', $salary->details);
    }

    public function test_pnl_two_profits_and_tax(): void
    {
        Setting::create(['key' => 'income_tax_percent', 'value' => '20']);

        $master = $this->master(35);
        $this->visit($master, 5000, Carbon::create(2026, 7, 10, 12));   // выручка 5000

        $period = ExpensePeriod::create(['year' => 2026, 'month' => 7]);
        $period->expenses()->create(['title' => 'Аренда', 'amount' => 1000, 'in_journal' => true]);
        $period->expenses()->create(['title' => 'Нал без чека', 'amount' => 500, 'in_journal' => false]);

        $pnl = $period->pnl();
        $this->assertEquals(5000, $pnl['revenue']);
        $this->assertEquals(1000, $pnl['expenses_journal']);
        $this->assertEquals(1500, $pnl['expenses_all']);
        $this->assertEquals(4000, $pnl['profit_journal']);   // 5000 − 1000
        $this->assertEquals(3500, $pnl['profit_full']);      // 5000 − 1500
        $this->assertEquals(800, $pnl['tax']);               // 20% от 4000 (по журналу)
        $this->assertEquals(2700, $pnl['profit_after_tax']); // 3500 − 800
    }

    public function test_revenue_counts_services_including_certificate_visits(): void
    {
        $master = $this->master(35);
        // Наличными на 100 (деньги пришли) + по сертификату на 200 (услуга оказана,
        // денег в этот визит нет). Выручка для прибыли = 100 + 200 = 300.
        $this->visit($master, 100, Carbon::create(2026, 7, 5, 12), PaymentType::Cash);
        $certVisit = $this->visit($master, 200, Carbon::create(2026, 7, 6, 12), PaymentType::Certificate);
        $certVisit->update(['paid_amount' => 0]);

        $period = ExpensePeriod::create(['year' => 2026, 'month' => 7]);
        $pnl = $period->pnl();

        $this->assertEquals(300, $pnl['revenue']);   // сумма услуг (обе)
        $this->assertEquals(100, $pnl['cash']);      // деньгами — только наличные
        $this->assertEquals(300, $pnl['profit_full']); // расходов пока нет
    }

    public function test_tax_not_negative_when_journal_profit_negative(): void
    {
        $period = ExpensePeriod::create(['year' => 2026, 'month' => 7]);
        $period->expenses()->create(['title' => 'Аренда', 'amount' => 1000, 'in_journal' => true]);

        // Выручки нет → прибыль по журналу −1000, налог не берётся.
        $pnl = $period->pnl();
        $this->assertEquals(-1000, $pnl['profit_journal']);
        $this->assertEquals(0, $pnl['tax']);
    }

    public function test_expenses_hidden_from_master(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Master]));
        $this->assertFalse(ExpensePeriodResource::canAccess());

        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $this->assertTrue(ExpensePeriodResource::canAccess());
    }

    public function test_pages_and_relation_manager_render(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $master = $this->master(35);
        $this->visit($master, 3000, Carbon::create(2026, 7, 10, 12));
        $period = ExpensePeriod::create(['year' => 2026, 'month' => 7]);
        $expense = $period->expenses()->create(['title' => 'Аренда', 'amount' => 1880, 'in_journal' => true]);

        Livewire::test(ViewExpensePeriod::class, ['record' => $period->id])
            ->assertOk();

        Livewire::test(ExpensesRelationManager::class, [
            'ownerRecord' => $period,
            'pageClass' => ViewExpensePeriod::class,
        ])
            ->assertOk()
            ->assertCanSeeTableRecords([$expense])
            ->assertTableActionVisible('edit', $expense);
    }
}
