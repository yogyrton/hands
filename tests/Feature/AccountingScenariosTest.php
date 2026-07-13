<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\Services\CertificateServiceInterface;
use App\Contracts\Services\VisitServiceInterface;
use App\Data\CertificateData;
use App\Data\VisitData;
use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Filament\Pages\Reports;
use App\Filament\Resources\Visits\Pages\CreateVisit;
use App\Models\Certificate;
use App\Models\Master;
use App\Models\Promotion;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Полная проверка учёта: посещения (нал/карта/акции), отчётность по мастерам
 * за период, оплата сертификатами (списание по сумме и по количеству, доплаты,
 * закрытие сертификата, скидки поверх сертификата) и защитные проверки формы.
 *
 * Перед каждым тестом — что проверяем и какой результат ожидаем.
 */
class AccountingScenariosTest extends TestCase
{
    use RefreshDatabase;

    private function master(float $rate = 35, string $name = 'Анна'): Master
    {
        return Master::create([
            'slug' => 'm-'.uniqid(), 'name' => $name, 'name_dative' => 'Анне',
            'role' => 'Массажист', 'yclients_url' => 'https://e.com', 'bio1' => 'a', 'bio2' => 'b',
            'salary_rate' => $rate,
        ]);
    }

    private function service(float $price = 50): Service
    {
        return Service::create([
            'slug' => 's-'.uniqid(), 'name' => 'Классический массаж', 'level' => 4,
            'base_price' => $price, 'lead' => 'lead',
        ]);
    }

    private function certificates(): CertificateServiceInterface
    {
        return app(CertificateServiceInterface::class);
    }

    private function visits(): VisitServiceInterface
    {
        return app(VisitServiceInterface::class);
    }

    /**
     * Отчёт за период с опциональным фильтром по мастеру.
     */
    private function report(string $from, string $until, ?int $masterId = null): Reports
    {
        $report = app(Reports::class);
        $report->data = ['from' => $from, 'until' => $until, 'master_id' => $masterId];

        return $report;
    }

    // ───────────────────────── Прямая оплата (нал / карта) ─────────────────────────

    /**
     * Проверяем: посещение без сертификата, оплата НАЛИЧНЫМИ.
     * Ожидаем: оплачена полная стоимость, тип оплаты — наличные,
     * зарплата = 35% от стоимости (50 → 17.5).
     */
    public function test_visit_paid_cash_full_amount(): void
    {
        $visit = $this->visits()->register(VisitData::from([
            'master_id' => $this->master()->id,
            'service_id' => $this->service(50)->id,
            'base_price' => 50, 'service_price' => 50,
            'payment_type' => PaymentType::Cash,
        ]));

        $this->assertEquals(50, $visit->paid_amount);
        $this->assertSame(PaymentType::Cash, $visit->payment_type);
        $this->assertEquals(17.5, $visit->salaryAmount());
    }

    /**
     * Проверяем: посещение без сертификата, оплата КАРТОЙ.
     * Ожидаем: оплачена полная стоимость, тип оплаты — карта, зарплата 35% (60 → 21).
     */
    public function test_visit_paid_card_full_amount(): void
    {
        $visit = $this->visits()->register(VisitData::from([
            'master_id' => $this->master()->id,
            'service_id' => $this->service(60)->id,
            'base_price' => 60, 'service_price' => 60,
            'payment_type' => PaymentType::Card,
        ]));

        $this->assertEquals(60, $visit->paid_amount);
        $this->assertSame(PaymentType::Card, $visit->payment_type);
        $this->assertEquals(21, $visit->salaryAmount());
    }

    // ───────────────────────── Отчёт по мастеру за период ─────────────────────────

    /**
     * Проверяем: у Анны за ИЮНЬ 10 посещений по 60 р — 5 картой и 5 наличными.
     * Ожидаем в отчёте за 01–30 июня:
     *   • по мастеру: 10 посещений, сумма услуг 600, зарплата 35% = 210;
     *   • выручка: наличные 300, карта 300, итого 600.
     */
    public function test_master_report_june_five_card_five_cash(): void
    {
        $anna = $this->master(35, 'Анна');
        $service = $this->service(60);

        // 5 картой + 5 наличными, все в июне.
        $this->makeVisitOn('2026-06-05', $anna, $service, 60, PaymentType::Card);
        $this->makeVisitOn('2026-06-08', $anna, $service, 60, PaymentType::Card);
        $this->makeVisitOn('2026-06-12', $anna, $service, 60, PaymentType::Card);
        $this->makeVisitOn('2026-06-18', $anna, $service, 60, PaymentType::Card);
        $this->makeVisitOn('2026-06-25', $anna, $service, 60, PaymentType::Card);
        $this->makeVisitOn('2026-06-03', $anna, $service, 60, PaymentType::Cash);
        $this->makeVisitOn('2026-06-09', $anna, $service, 60, PaymentType::Cash);
        $this->makeVisitOn('2026-06-15', $anna, $service, 60, PaymentType::Cash);
        $this->makeVisitOn('2026-06-22', $anna, $service, 60, PaymentType::Cash);
        $this->makeVisitOn('2026-06-30', $anna, $service, 60, PaymentType::Cash);

        $report = $this->report('2026-06-01', '2026-06-30');

        $byMaster = collect($report->byMaster())->firstWhere('name', 'Анна');
        $this->assertSame(10, $byMaster['count']);
        $this->assertEquals(600, $byMaster['sum']);
        $this->assertEquals(210, $byMaster['salary']);      // 600 × 35%

        $rev = $report->revenue();
        $this->assertEquals(300, $rev['cash']);
        $this->assertEquals(300, $rev['card']);
        $this->assertEquals(600, $rev['total']);
        $this->assertSame(10, $rev['visits']);
    }

    /**
     * Проверяем: посещения в мае и июле НЕ попадают в июньский отчёт (границы периода).
     * Ожидаем: в июне учтено только июньское посещение — 1 шт, сумма 60.
     */
    public function test_report_period_excludes_out_of_range_visits(): void
    {
        $anna = $this->master(35, 'Анна');
        $service = $this->service(60);

        $this->makeVisitOn('2026-05-31', $anna, $service, 60, PaymentType::Cash); // до периода
        $this->makeVisitOn('2026-06-15', $anna, $service, 60, PaymentType::Cash); // в периоде
        $this->makeVisitOn('2026-07-01', $anna, $service, 60, PaymentType::Cash); // после периода

        $report = $this->report('2026-06-01', '2026-06-30');

        $rev = $report->revenue();
        $this->assertSame(1, $rev['visits']);
        $this->assertEquals(60, $rev['total']);
    }

    /**
     * Проверяем: фильтр отчёта по конкретному мастеру.
     * Ожидаем: при выборе Анны в byMaster только её строка, выручка — только её посещения.
     */
    public function test_report_filtered_by_master(): void
    {
        $anna = $this->master(35, 'Анна');
        $dmitry = $this->master(40, 'Дмитрий');
        $service = $this->service(50);

        $this->makeVisitOn('2026-06-10', $anna, $service, 50, PaymentType::Cash);
        $this->makeVisitOn('2026-06-11', $dmitry, $service, 50, PaymentType::Card);

        $report = $this->report('2026-06-01', '2026-06-30', $anna->id);

        $byMaster = $report->byMaster();
        $this->assertCount(1, $byMaster);
        $this->assertSame('Анна', $byMaster[0]['name']);

        $rev = $report->revenue();
        $this->assertEquals(50, $rev['cash']);   // только посещение Анны
        $this->assertEquals(0, $rev['card']);
    }

    // ───────────────────────── Акции ─────────────────────────

    /**
     * Проверяем: посещение по акции −10% (базовая 100 → итоговая 90), оплата наличными.
     * Ожидаем: списано 90, зарплата 35% от 90 = 31.5 (мастер делит скидку),
     * сумма скидки = 10, акция привязана к посещению.
     */
    public function test_visit_with_promotion_discount_and_shared_salary(): void
    {
        $promo = Promotion::create(['title' => 'Ранняя пташка', 'discount_percent' => 10]);

        $visit = $this->visits()->register(VisitData::from([
            'master_id' => $this->master()->id,
            'service_id' => $this->service(100)->id,
            'base_price' => 100, 'service_price' => 90,
            'promotion_id' => $promo->id,
            'payment_type' => PaymentType::Cash,
        ]));

        $this->assertSame($promo->id, $visit->promotion_id);
        $this->assertEquals(90, $visit->paid_amount);
        $this->assertEquals(31.5, $visit->salaryAmount());
        $this->assertEquals(10, $visit->discountAmount());
    }

    /**
     * Проверяем: отчёт по акциям агрегирует за период (2 посещения по акции −10%).
     * Ожидаем: 1 строка акции, 2 посещения, деньгами 180, сумма скидки 20.
     */
    public function test_report_by_promotion_aggregates(): void
    {
        $promo = Promotion::create(['title' => 'Ранняя пташка', 'discount_percent' => 10]);
        $anna = $this->master();
        $service = $this->service(100);

        foreach (['2026-06-05', '2026-06-06'] as $date) {
            $this->makeVisitOn($date, $anna, $service, 90, PaymentType::Cash, basePrice: 100, promotionId: $promo->id);
        }

        $rows = $this->report('2026-06-01', '2026-06-30')->byPromotion();

        $this->assertCount(1, $rows);
        $this->assertSame(2, $rows[0]['count']);
        $this->assertEquals(180, $rows[0]['paid']);
        $this->assertEquals(20, $rows[0]['discount']);
    }

    // ───────────────────────── Денежный сертификат ─────────────────────────

    /**
     * Проверяем: денежный сертификат на 500 р, три посещения подряд по 100/120/80.
     * Ожидаем: остаток уменьшается 500 → 400 → 280 → 200; каждое посещение
     * оплачено «сертификатом» (деньгами 0), зарплата мастера считается от стоимости.
     */
    public function test_money_certificate_sequential_deductions(): void
    {
        $cert = $this->certificates()->issue(CertificateData::from([
            'type' => CertificateType::Money, 'initial_amount' => 500,
        ]));
        $anna = $this->master();
        $service = $this->service(120);

        foreach ([100, 120, 80] as $price) {
            $visit = $this->visits()->register(VisitData::from([
                'master_id' => $anna->id, 'service_id' => $service->id,
                'base_price' => $price, 'service_price' => $price,
                'certificate_id' => $cert->id,
            ]));
            $this->assertEquals(0, $visit->paid_amount);
            $this->assertSame(PaymentType::Certificate, $visit->payment_type);
        }

        $cert->refresh();
        $this->assertEquals(200, $cert->remaining_amount);       // 500 − (100+120+80)
        $this->assertSame(CertificateStatus::Active, $cert->status);
    }

    /**
     * Проверяем: денежный сертификат ровно закрывается (остаток 60, услуга 60).
     * Ожидаем: остаток 0, статус «Использован».
     */
    public function test_money_certificate_exact_closes(): void
    {
        $cert = $this->certificates()->issue(CertificateData::from([
            'type' => CertificateType::Money, 'initial_amount' => 60,
        ]));

        $this->visits()->register(VisitData::from([
            'master_id' => $this->master()->id, 'service_id' => $this->service(60)->id,
            'base_price' => 60, 'service_price' => 60,
            'certificate_id' => $cert->id,
        ]));

        $cert->refresh();
        $this->assertEquals(0, $cert->remaining_amount);
        $this->assertSame(CertificateStatus::Used, $cert->status);
    }

    /**
     * Ключевой сценарий доплаты. Остаток сертификата 20, массаж у Анны стоит 50,
     * клиент доплачивает 30 наличными.
     * Ожидаем: сертификатом списано 20 → остаток 0 → статус «Использован»;
     * доплата 30, тип оплаты «Сертификат с доплатой», метод доплаты — наличные;
     * зарплата Анны — с ПОЛНОЙ стоимости 50 (35% = 17.5), доплата 30 падает в наличную выручку.
     */
    public function test_money_certificate_with_surcharge_closes_and_full_salary(): void
    {
        $cert = $this->certificates()->issue(CertificateData::from([
            'type' => CertificateType::Money, 'initial_amount' => 20,
        ]));
        $anna = $this->master();

        $visit = $this->visits()->register(VisitData::from([
            'master_id' => $anna->id, 'service_id' => $this->service(50)->id,
            'base_price' => 50, 'service_price' => 50,
            'certificate_id' => $cert->id,
            'surcharge_amount' => 30,
            'surcharge_payment_type' => PaymentType::Cash,
        ]));

        $cert->refresh();
        $this->assertEquals(0, $cert->remaining_amount);
        $this->assertSame(CertificateStatus::Used, $cert->status);
        $this->assertEquals(30, $visit->paid_amount);
        $this->assertSame(PaymentType::CertificateSurcharge, $visit->payment_type);
        $this->assertSame(PaymentType::Cash, $visit->surcharge_payment_type);
        $this->assertEquals(-20, $visit->operation->amount);       // с серта списано 20
        $this->assertEquals(17.5, $visit->salaryAmount());         // 35% от полной 50

        // Доплата 30 попала в наличную выручку.
        $rev = $this->report(now()->startOfMonth()->toDateString(), now()->toDateString())->revenue();
        $this->assertEquals(30, $rev['cash']);
        $this->assertEquals(0, $rev['card']);
    }

    /**
     * Проверяем: денежный сертификат 100 + акция −10% на услугу 50.
     * Ожидаем: итоговая со скидкой = 45, сертификатом списано 45, остаток 55
     * (а НЕ полные 50).
     */
    public function test_money_certificate_with_promotion_deducts_discounted(): void
    {
        $promo = Promotion::create(['title' => 'Ранняя пташка', 'discount_percent' => 10]);
        $cert = $this->certificates()->issue(CertificateData::from([
            'type' => CertificateType::Money, 'initial_amount' => 100,
        ]));

        $visit = $this->visits()->register(VisitData::from([
            'master_id' => $this->master()->id, 'service_id' => $this->service(50)->id,
            'base_price' => 50, 'service_price' => 45,   // 50 − 10%
            'promotion_id' => $promo->id,
            'certificate_id' => $cert->id,
        ]));

        $cert->refresh();
        $this->assertEquals(55, $cert->remaining_amount);          // 100 − 45
        $this->assertEquals(-45, $visit->operation->amount);
        $this->assertSame($promo->id, $visit->promotion_id);
    }

    // ───────────────────────── Сертификат на посещения ─────────────────────────

    /**
     * Проверяем: сертификат на 5 посещений, списание одного.
     * Оператор вводит цену конкретного сеанса (45) — от неё считается зарплата.
     * Ожидаем: остаток 4 посещения, оплачено деньгами 0, зарплата 35% от 45 = 15.75.
     */
    public function test_visits_certificate_decrements_and_salary_from_entered_price(): void
    {
        $cert = $this->certificates()->issue(CertificateData::from([
            'type' => CertificateType::Visits, 'initial_visits' => 5,
        ]));

        $visit = $this->visits()->register(VisitData::from([
            'master_id' => $this->master()->id, 'service_id' => $this->service(50)->id,
            'base_price' => 50, 'service_price' => 45,
            'certificate_id' => $cert->id,
        ]));

        $cert->refresh();
        $this->assertSame(4, $cert->remaining_visits);
        $this->assertEquals(0, $visit->paid_amount);
        $this->assertEquals(45, $visit->service_price);
        $this->assertEquals(15.75, $visit->salaryAmount());
    }

    /**
     * Проверяем закрытие сертификата на посещения: 5 посещений, использованы все 5.
     * Ожидаем: после 5-го остаток 0, статус «Использован», и сертификат больше
     * НЕ доступен для оплаты (не входит в scopeUsable → в форме не выбрать).
     */
    public function test_visits_certificate_closes_after_last_visit(): void
    {
        $cert = $this->certificates()->issue(CertificateData::from([
            'type' => CertificateType::Visits, 'initial_visits' => 5,
        ]));
        $anna = $this->master();
        $service = $this->service(50);

        for ($i = 1; $i <= 5; $i++) {
            $this->visits()->register(VisitData::from([
                'master_id' => $anna->id, 'service_id' => $service->id,
                'base_price' => 50, 'service_price' => 50,
                'certificate_id' => $cert->id,
            ]));
        }

        $cert->refresh();
        $this->assertSame(0, $cert->remaining_visits);
        $this->assertSame(CertificateStatus::Used, $cert->status);

        // Исчерпанный сертификат недоступен для новой оплаты.
        $usableIds = Certificate::usable()->pluck('id')->all();
        $this->assertNotContains($cert->id, $usableIds);
    }

    /**
     * Проверяем: истёкший по сроку сертификат недействителен даже при наличии остатка.
     * Ожидаем: не входит в scopeUsable.
     */
    public function test_expired_certificate_is_not_usable(): void
    {
        $cert = $this->certificates()->issue(CertificateData::from([
            'type' => CertificateType::Money, 'initial_amount' => 100,
        ]));
        // Просрочиваем задним числом.
        $cert->forceFill(['expires_at' => now()->subDay()->toDateString()])->save();
        $cert->refreshStatus();

        $this->assertSame(CertificateStatus::Expired, $cert->fresh()->status);
        $this->assertNotContains($cert->id, Certificate::usable()->pluck('id')->all());
    }

    // ───────────────────────── Защита формы (недоплата / переплата) ─────────────────────────

    /**
     * Проверяем защиту: денежный сертификат (остаток 20), услуга 50, но галочку
     * «Доплатить» НЕ включили. Так сохранять нельзя — иначе услуга уйдёт недоплаченной.
     * Ожидаем: ошибка валидации на поле сертификата, посещение не создано.
     */
    public function test_form_blocks_money_cert_without_surcharge_when_insufficient(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $cert = $this->certificates()->issue(CertificateData::from([
            'type' => CertificateType::Money, 'initial_amount' => 20,
        ]));

        Livewire::test(CreateVisit::class)
            ->fillForm([
                'master_id' => $this->master()->id,
                'service_id' => $this->service(50)->id,   // service_price станет 50
                'use_certificate' => true,
                'certificate_id' => $cert->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['certificate_id']);

        $this->assertSame(0, Visit::query()->count());
    }

    /**
     * Проверяем защиту: доплата слишком мала. Остаток 20, услуга 50, доплатили только 10
     * (сертификат смог бы покрыть максимум 20, не хватает ещё 20).
     * Ожидаем: ошибка на поле суммы доплаты, посещение не создано.
     */
    public function test_form_blocks_surcharge_too_small(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $cert = $this->certificates()->issue(CertificateData::from([
            'type' => CertificateType::Money, 'initial_amount' => 20,
        ]));

        Livewire::test(CreateVisit::class)
            ->fillForm([
                'master_id' => $this->master()->id,
                'service_id' => $this->service(50)->id,
                'use_certificate' => true,
                'certificate_id' => $cert->id,
                'use_surcharge' => true,
                'surcharge_amount' => 10,
                'surcharge_payment_type' => 'cash',
            ])
            ->call('create')
            ->assertHasFormErrors(['surcharge_amount']);

        $this->assertSame(0, Visit::query()->count());
    }

    /**
     * Проверяем защиту: доплата больше стоимости услуги (услуга 50, доплата 60).
     * Ожидаем: ошибка на поле суммы доплаты.
     */
    public function test_form_blocks_surcharge_larger_than_price(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $cert = $this->certificates()->issue(CertificateData::from([
            'type' => CertificateType::Money, 'initial_amount' => 20,
        ]));

        Livewire::test(CreateVisit::class)
            ->fillForm([
                'master_id' => $this->master()->id,
                'service_id' => $this->service(50)->id,
                'use_certificate' => true,
                'certificate_id' => $cert->id,
                'use_surcharge' => true,
                'surcharge_amount' => 60,
                'surcharge_payment_type' => 'cash',
            ])
            ->call('create')
            ->assertHasFormErrors(['surcharge_amount']);
    }

    /**
     * Проверяем «счастливый путь» доплаты через форму: остаток 20, услуга 50, доплата 30.
     * Ожидаем: посещение создано без ошибок; сертификат закрыт (остаток 0);
     * доплата 30 наличными; зарплата — с полной стоимости.
     */
    public function test_form_accepts_correct_surcharge(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $anna = $this->master();
        $cert = $this->certificates()->issue(CertificateData::from([
            'type' => CertificateType::Money, 'initial_amount' => 20,
        ]));

        Livewire::test(CreateVisit::class)
            ->fillForm([
                'master_id' => $anna->id,
                'service_id' => $this->service(50)->id,
                'use_certificate' => true,
                'certificate_id' => $cert->id,
                'use_surcharge' => true,
                'surcharge_amount' => 30,
                'surcharge_payment_type' => 'cash',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $visit = Visit::query()->firstOrFail();
        $this->assertEquals(30, $visit->paid_amount);
        $this->assertSame(PaymentType::CertificateSurcharge, $visit->payment_type);
        $this->assertEquals(0, $cert->refresh()->remaining_amount);
        $this->assertSame(CertificateStatus::Used, $cert->status);
    }

    // ───────────────────────── Выручка не двоится ─────────────────────────

    /**
     * Проверяем: посещение, полностью оплаченное денежным сертификатом, НЕ добавляет
     * ничего в выручку нал/карта (деньги были получены при продаже сертификата).
     * Ожидаем: revenue cash = 0, card = 0, но зарплата мастеру начислена (сумма услуг в отчёте есть).
     */
    public function test_pure_certificate_visit_adds_zero_to_cash_card_revenue(): void
    {
        $cert = $this->certificates()->issue(CertificateData::from([
            'type' => CertificateType::Money, 'initial_amount' => 100,
        ]));
        $anna = $this->master();

        $this->visits()->register(VisitData::from([
            'master_id' => $anna->id, 'service_id' => $this->service(60)->id,
            'base_price' => 60, 'service_price' => 60,
            'certificate_id' => $cert->id,
        ]));

        $report = $this->report(now()->startOfMonth()->toDateString(), now()->toDateString());
        $rev = $report->revenue();
        $this->assertEquals(0, $rev['cash']);
        $this->assertEquals(0, $rev['card']);
        $this->assertSame(1, $rev['cert_visits']);          // но как посещение по серту — учтено

        $byMaster = collect($report->byMaster())->firstWhere('name', 'Анна');
        $this->assertEquals(60, $byMaster['sum']);          // услуга в зарплатной базе есть
        $this->assertEquals(21, $byMaster['salary']);       // 35% от 60
    }

    // ───────────────────────── Реверс при удалении ─────────────────────────

    /**
     * Проверяем откат при удалении посещения по денежному сертификату.
     * Ожидаем: остаток вернулся к 100, статус снова «Активен», посещение и
     * операция списания удалены (осталась только продажа).
     */
    public function test_delete_reverses_money_certificate(): void
    {
        $cert = $this->certificates()->issue(CertificateData::from([
            'type' => CertificateType::Money, 'initial_amount' => 100,
        ]));

        $visit = $this->visits()->register(VisitData::from([
            'master_id' => $this->master()->id, 'service_id' => $this->service(60)->id,
            'base_price' => 60, 'service_price' => 60,
            'certificate_id' => $cert->id,
        ]));
        $this->assertEquals(40, $cert->refresh()->remaining_amount);

        $this->visits()->deleteWithReversal($visit);

        $cert->refresh();
        $this->assertEquals(100, $cert->remaining_amount);
        $this->assertSame(CertificateStatus::Active, $cert->status);
        $this->assertDatabaseMissing('visits', ['id' => $visit->id]);
        $this->assertSame(1, $cert->operations()->count());
    }

    /**
     * Проверяем откат для сертификата на посещения: закрыли последним 5-м визитом,
     * затем удалили — посещение должно вернуться и сертификат снова стать активным.
     * Ожидаем: остаток 1 посещение, статус «Активен».
     */
    public function test_delete_reverses_visits_certificate_and_reactivates(): void
    {
        $cert = $this->certificates()->issue(CertificateData::from([
            'type' => CertificateType::Visits, 'initial_visits' => 5,
        ]));
        $anna = $this->master();
        $service = $this->service(50);

        $last = null;
        for ($i = 1; $i <= 5; $i++) {
            $last = $this->visits()->register(VisitData::from([
                'master_id' => $anna->id, 'service_id' => $service->id,
                'base_price' => 50, 'service_price' => 50,
                'certificate_id' => $cert->id,
            ]));
        }
        $this->assertSame(CertificateStatus::Used, $cert->refresh()->status);

        $this->visits()->deleteWithReversal($last);

        $cert->refresh();
        $this->assertSame(1, $cert->remaining_visits);
        $this->assertSame(CertificateStatus::Active, $cert->status);
    }

    /**
     * Хелпер: создаёт посещение на конкретную дату с заданной оплатой (для отчётов
     * за период). Прямые нал/карта посещения без сертификата пишем напрямую,
     * т.к. register() всегда ставит performed_at = now().
     */
    private function makeVisitOn(
        string $date,
        Master $master,
        Service $service,
        float $price,
        PaymentType $type,
        ?float $basePrice = null,
        ?int $promotionId = null,
    ): Visit {
        return Visit::create([
            'master_id' => $master->id,
            'service_id' => $service->id,
            'base_price' => $basePrice ?? $price,
            'service_price' => $price,
            'paid_amount' => $price,
            'payment_type' => $type,
            'promotion_id' => $promotionId,
            'performed_at' => Carbon::parse($date.' 12:00:00'),
        ]);
    }
}
