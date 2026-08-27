<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Filament\Resources\PayrollPeriods\Pages\CreatePayrollPeriod;
use App\Filament\Resources\PayrollPeriods\Pages\ListPayrollPeriods;
use App\Filament\Resources\PayrollPeriods\Pages\ViewPayrollPeriod;
use App\Filament\Resources\PayrollPeriods\PayrollPeriodResource;
use App\Filament\Resources\PayrollPeriods\RelationManagers\PayoutsRelationManager;
use App\Models\Master;
use App\Models\PayrollPeriod;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class PayrollTest extends TestCase
{
    use RefreshDatabase;

    private function master(float $rate = 35, bool $active = true): Master
    {
        return Master::create([
            'slug' => 'm-'.uniqid(), 'name' => 'Мастер', 'name_dative' => 'Мастеру',
            'role' => 'Массажист', 'yclients_url' => 'https://e.com', 'bio1' => 'a', 'bio2' => 'b',
            'salary_rate' => $rate, 'is_active' => $active,
        ]);
    }

    private function service(float $price = 100): Service
    {
        return Service::create([
            'slug' => 's-'.uniqid(), 'name' => 'Услуга', 'level' => 4, 'base_price' => $price,
            'lead' => 'lead',
        ]);
    }

    private function visit(Master $master, float $price, Carbon $when): Visit
    {
        return Visit::create([
            'master_id' => $master->id,
            'service_id' => $this->service($price)->id,
            'base_price' => $price,
            'service_price' => $price,
            'paid_amount' => $price,
            'payment_type' => PaymentType::Cash,
            'performed_at' => $when,
        ]);
    }

    public function test_earned_in_month_sums_only_that_months_visits(): void
    {
        $master = $this->master();

        $this->visit($master, 100, Carbon::create(2026, 7, 5, 12));
        $this->visit($master, 200, Carbon::create(2026, 7, 25, 12));
        $this->visit($master, 999, Carbon::create(2026, 8, 1, 12));   // другой месяц — не считается

        $this->assertEquals(300, $master->earnedInMonth(2026, 7));
    }

    public function test_payout_accrued_and_debt(): void
    {
        $master = $this->master(35);
        $this->visit($master, 1000, Carbon::create(2026, 7, 10, 12));   // заработано 1000

        $period = PayrollPeriod::create(['year' => 2026, 'month' => 7]);
        $payout = $period->payouts()->create([
            'master_id' => $master->id,
            'advance_amount' => 200,
            'salary_amount' => 100,
        ]);

        $payout->refresh()->load('master', 'period');

        $this->assertEquals(1000, $payout->earned());
        $this->assertEquals(350, $payout->accrued());   // 35% от 1000
        $this->assertEquals(300, $payout->paid());      // 200 + 100
        $this->assertEquals(50, $payout->debt());        // 350 − 300
    }

    public function test_creating_period_autofills_rows_for_active_masters(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $a = $this->master(35, active: true);
        $b = $this->master(40, active: true);
        $this->master(30, active: false);   // уволенный — строку не заводим

        Livewire::test(CreatePayrollPeriod::class)
            ->fillForm(['month' => 7, 'year' => 2026])
            ->call('create')
            ->assertHasNoFormErrors();

        $period = PayrollPeriod::query()->firstOrFail();
        $this->assertEqualsCanonicalizing(
            [$a->id, $b->id],
            $period->payouts()->pluck('master_id')->all(),
        );

        // Дата зарплаты по умолчанию — 10-е число следующего месяца.
        $this->assertSame('2026-08-10', $period->payouts()->first()->salary_date->toDateString());
    }

    public function test_period_totals_aggregate_masters(): void
    {
        $a = $this->master(35);
        $b = $this->master(50);
        $this->visit($a, 1000, Carbon::create(2026, 7, 10, 12));   // начислено 350
        $this->visit($b, 1000, Carbon::create(2026, 7, 10, 12));   // начислено 500

        $period = PayrollPeriod::create(['year' => 2026, 'month' => 7]);
        $period->payouts()->create(['master_id' => $a->id, 'salary_amount' => 350]);   // выплачено 350
        $period->payouts()->create(['master_id' => $b->id, 'advance_amount' => 200]);  // выплачено 200

        $totals = $period->totals();
        $this->assertEquals(850, $totals['accrued']);   // 350 + 500
        $this->assertEquals(550, $totals['paid']);       // 350 + 200
        $this->assertEquals(300, $totals['debt']);       // 850 − 550
    }

    public function test_dismissed_master_kept_in_past_period_excluded_from_new_one(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $podov = $this->master(35);
        $podov->update(['name' => 'Подов']);
        $this->visit($podov, 1000, Carbon::create(2026, 7, 15, 12));   // отработал июль

        // Июль создан, пока Подов активен — строка заводится.
        Livewire::test(CreatePayrollPeriod::class)
            ->fillForm(['month' => 7, 'year' => 2026])
            ->call('create')
            ->assertHasNoFormErrors();
        $july = PayrollPeriod::where('month', 7)->firstOrFail();

        // Подова увольняем (мягкое удаление), выходит Александр.
        $podov->delete();
        $alex = $this->master(35);
        $alex->update(['name' => 'Александр']);

        // Июль по-прежнему видит Подова и его заработок.
        $julyPayout = $july->payouts()->firstOrFail()->load('master', 'period');
        $this->assertSame('Подов', $julyPayout->master->name);
        $this->assertEquals(1000, $julyPayout->earned());

        // Август создаём в сентябре: Подова нет, есть только Александр.
        Livewire::test(CreatePayrollPeriod::class)
            ->fillForm(['month' => 8, 'year' => 2026])
            ->call('create')
            ->assertHasNoFormErrors();
        $august = PayrollPeriod::where('month', 8)->firstOrFail();

        $this->assertSame(
            ['Александр'],
            $august->payouts()->with('master')->get()->map(fn ($p) => $p->master->name)->all(),
        );
    }

    public function test_duplicate_period_is_rejected(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        PayrollPeriod::create(['year' => 2026, 'month' => 7]);

        Livewire::test(CreatePayrollPeriod::class)
            ->fillForm(['month' => 7, 'year' => 2026])
            ->call('create')
            ->assertHasFormErrors(['month']);
    }

    public function test_resource_pages_and_relation_manager_render(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $master = $this->master(35);
        $this->visit($master, 1000, Carbon::create(2026, 7, 10, 12));
        $period = PayrollPeriod::create(['year' => 2026, 'month' => 7]);
        $payout = $period->payouts()->create(['master_id' => $master->id, 'salary_amount' => 350]);

        Livewire::test(ListPayrollPeriods::class)
            ->assertOk()
            ->assertSee('Июль 2026');

        Livewire::test(ViewPayrollPeriod::class, ['record' => $period->id])
            ->assertOk();

        Livewire::test(PayoutsRelationManager::class, [
            'ownerRecord' => $period,
            'pageClass' => ViewPayrollPeriod::class,
        ])
            ->assertOk()
            ->assertCanSeeTableRecords([$payout])
            // Админ может вносить суммы аванса/зп — кнопка редактирования доступна.
            ->assertTableActionVisible('edit', $payout);
    }

    public function test_admin_edits_advance_and_salary_via_relation_manager(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $master = $this->master(35);
        $this->visit($master, 1000, Carbon::create(2026, 7, 10, 12));
        $period = PayrollPeriod::create(['year' => 2026, 'month' => 7]);
        $payout = $period->payouts()->create(['master_id' => $master->id]);

        Livewire::test(PayoutsRelationManager::class, [
            'ownerRecord' => $period,
            'pageClass' => ViewPayrollPeriod::class,
        ])
            ->callTableAction('edit', $payout, data: [
                'advance_date' => '2026-07-25',
                'advance_amount' => 200,
                'salary_date' => '2026-08-10',
                'salary_amount' => 150,
            ])
            ->assertHasNoTableActionErrors();

        $payout->refresh();
        $this->assertEquals(200, $payout->advance_amount);
        $this->assertEquals(150, $payout->salary_amount);
        $this->assertEquals(0, $payout->debt());   // 350 начислено − 200 − 150
    }

    public function test_payroll_hidden_from_master_shown_to_admin(): void
    {
        // Зарплата — только для админа: мастер не имеет доступа к разделу.
        $this->actingAs(User::factory()->create(['role' => UserRole::Master]));
        $this->assertFalse(PayrollPeriodResource::canAccess());

        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $this->assertTrue(PayrollPeriodResource::canAccess());
        Livewire::test(ListPayrollPeriods::class)
            ->assertOk()
            ->assertSee('Создать месяц');
    }

    public function test_master_cannot_create_periods_admin_can(): void
    {
        $this->assertTrue(
            (function () {
                $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

                return PayrollPeriodResource::canCreate();
            })(),
        );

        $this->actingAs(User::factory()->create(['role' => UserRole::Master]));
        $this->assertFalse(PayrollPeriodResource::canCreate());
    }
}
