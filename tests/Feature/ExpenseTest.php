<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Filament\Resources\ExpensePeriods\ExpensePeriodResource;
use App\Filament\Resources\ExpensePeriods\Pages\CreateExpensePeriod;
use App\Filament\Resources\ExpensePeriods\Pages\ViewExpensePeriod;
use App\Filament\Resources\ExpensePeriods\RelationManagers\ExpensesRelationManager;
use App\Models\Certificate;
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

    public function test_pnl_profit_uses_journal_expenses_only_and_tax(): void
    {
        Setting::create(['key' => 'income_tax_percent', 'value' => '20']);

        $master = $this->master(35);
        $this->visit($master, 9000, Carbon::create(2026, 7, 10, 12));   // деньги 9000

        $period = ExpensePeriod::create(['year' => 2026, 'month' => 7]);
        $period->expenses()->create(['title' => 'Расходы', 'amount' => 6700, 'in_journal' => true]);
        $period->expenses()->create(['title' => 'Расходники', 'amount' => 300, 'in_journal' => false]);

        $pnl = $period->pnl();
        $this->assertEquals(9000, $pnl['revenue']);
        $this->assertEquals(6700, $pnl['expenses_journal']);
        $this->assertEquals(300, $pnl['expenses_non_journal']);   // только видно, в расчёт не идёт
        $this->assertEquals(2300, $pnl['profit']);                // 9000 − 6700
        $this->assertEquals(460, $pnl['tax']);                    // 20% от 2300
        $this->assertEquals(1840, $pnl['profit_after_tax']);      // 2300 − 460
    }

    public function test_revenue_counts_paid_visits_plus_certificate_sales(): void
    {
        $master = $this->master(35);
        // Наличными на 100 (деньги пришли) + визит по сертификату на 200 (денег
        // в этот визит нет — оплачен при продаже). В выручку из визитов = 100.
        $this->visit($master, 100, Carbon::create(2026, 7, 5, 12), PaymentType::Cash);
        $certVisit = $this->visit($master, 200, Carbon::create(2026, 7, 6, 12), PaymentType::Certificate);
        $certVisit->update(['paid_amount' => 0]);

        // Продан сертификат на 300 в этом же месяце → +300 в выручку.
        Certificate::create([
            'number' => 'C-1', 'type' => CertificateType::Money,
            'initial_amount' => 300, 'remaining_amount' => 300,
            'sold_at' => Carbon::create(2026, 7, 3)->toDateString(),
            'expires_at' => Carbon::create(2026, 10, 3)->toDateString(),
            'status' => CertificateStatus::Active,
        ]);

        $period = ExpensePeriod::create(['year' => 2026, 'month' => 7]);
        $pnl = $period->pnl();

        $this->assertEquals(100, $pnl['revenue_visits']);   // только оплаченный визит
        $this->assertEquals(300, $pnl['revenue_certs']);    // продажа сертификата
        $this->assertEquals(400, $pnl['revenue']);          // 100 + 300
    }

    public function test_tax_not_negative_when_profit_negative(): void
    {
        $period = ExpensePeriod::create(['year' => 2026, 'month' => 7]);
        $period->expenses()->create(['title' => 'Аренда', 'amount' => 1000, 'in_journal' => true]);

        // Выручки нет → прибыль −1000, налог не берётся.
        $pnl = $period->pnl();
        $this->assertEquals(-1000, $pnl['profit']);
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
