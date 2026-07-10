<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\Services\CertificateServiceInterface;
use App\Contracts\Services\VisitServiceInterface;
use App\Data\CertificateData;
use App\Data\VisitData;
use App\Enums\CertificateOperationType;
use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use App\Enums\PaymentType;
use App\Filament\Pages\Reports;
use App\Models\Master;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingTest extends TestCase
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

    private function service(float $price = 60): Service
    {
        return Service::create([
            'slug' => 's-'.uniqid(), 'name' => 'Услуга', 'level' => 4, 'base_price' => $price,
            'lead' => 'lead',
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

    public function test_issue_visits_certificate(): void
    {
        $cert = $this->certificates()->issue(CertificateData::from([
            'type' => CertificateType::Visits, 'initial_visits' => 5,
        ]));

        $this->assertSame('1', $cert->number);
        $this->assertSame(5, $cert->remaining_visits);
        $this->assertEquals(now()->startOfDay()->addMonths(3)->toDateString(), $cert->expires_at->toDateString());
        $this->assertSame(CertificateStatus::Active, $cert->status);
        $this->assertSame(1, $cert->operations()->count()); // операция продажи
    }

    public function test_visit_without_certificate_is_paid_in_full(): void
    {
        $visit = $this->visits()->register(VisitData::from([
            'master_id' => $this->master()->id,
            'service_id' => $this->service(60)->id,
            'base_price' => 60, 'service_price' => 60,
            'payment_type' => PaymentType::Card,
        ]));

        $this->assertEquals(60, $visit->paid_amount);
        $this->assertSame(PaymentType::Card, $visit->payment_type);
        $this->assertEquals(21, $visit->salaryAmount()); // 35% от 60
    }

    public function test_visit_with_visits_certificate_writes_off_one(): void
    {
        $cert = $this->certificates()->issue(CertificateData::from(['type' => CertificateType::Visits, 'initial_visits' => 2]));

        $visit = $this->visits()->register(VisitData::from([
            'master_id' => $this->master()->id,
            'service_id' => $this->service(60)->id,
            'base_price' => 60, 'service_price' => 60,
            'certificate_id' => $cert->id,
        ]));

        $cert->refresh();
        $this->assertSame(1, $cert->remaining_visits);
        $this->assertEquals(0, $visit->paid_amount);
        $this->assertSame(PaymentType::Certificate, $visit->payment_type);
        $this->assertEquals(21, $visit->salaryAmount());   // зарплата от service_price даже при 0 оплаты
        $this->assertEquals(-1, $visit->operation->amount);

        // В историю сертификата добавилась запись списания на −1 посещение.
        $this->assertDatabaseHas('certificate_operations', [
            'certificate_id' => $cert->id,
            'visit_id' => $visit->id,
            'type' => CertificateOperationType::Usage->value,
        ]);
        $this->assertSame(2, $cert->operations()->count()); // продажа + списание
    }

    public function test_money_certificate_sufficient(): void
    {
        $cert = $this->certificates()->issue(CertificateData::from(['type' => CertificateType::Money, 'initial_amount' => 100]));

        $visit = $this->visits()->register(VisitData::from([
            'master_id' => $this->master()->id,
            'service_id' => $this->service(60)->id,
            'base_price' => 60, 'service_price' => 60,
            'certificate_id' => $cert->id,
        ]));

        $cert->refresh();
        $this->assertEquals(40, $cert->remaining_amount);
        $this->assertEquals(0, $visit->paid_amount);
        $this->assertSame(PaymentType::Certificate, $visit->payment_type);
        $this->assertEquals(-60, $visit->operation->amount);    // списано 60 р с сертификата

        // В историю сертификата добавилась запись списания.
        $this->assertDatabaseHas('certificate_operations', [
            'certificate_id' => $cert->id,
            'visit_id' => $visit->id,
            'type' => CertificateOperationType::Usage->value,
        ]);
        $this->assertSame(2, $cert->operations()->count()); // продажа + списание
    }

    public function test_money_certificate_with_explicit_surcharge(): void
    {
        // Остаток 20, услуга 60, доплата 40 налом.
        $cert = $this->certificates()->issue(CertificateData::from(['type' => CertificateType::Money, 'initial_amount' => 20]));

        $visit = $this->visits()->register(VisitData::from([
            'master_id' => $this->master()->id,
            'service_id' => $this->service(60)->id,
            'base_price' => 60, 'service_price' => 60,
            'certificate_id' => $cert->id,
            'surcharge_amount' => 40,
            'surcharge_payment_type' => PaymentType::Cash,
        ]));

        $cert->refresh();
        $this->assertEquals(0, $cert->remaining_amount);                       // сертификат покрыл 20
        $this->assertSame(CertificateStatus::Used, $cert->status);
        $this->assertEquals(40, $visit->paid_amount);                          // доплата
        $this->assertSame(PaymentType::CertificateSurcharge, $visit->payment_type);
        $this->assertSame(PaymentType::Cash, $visit->surcharge_payment_type);  // метод доплаты
        $this->assertEquals(-20, $visit->operation->amount);                   // с серта списано 20
    }

    public function test_visits_certificate_salary_from_entered_price(): void
    {
        // Серт на 10 посещений за 450 (по 45). При посещении оператор вводит итоговую 45.
        $cert = $this->certificates()->issue(CertificateData::from([
            'type' => CertificateType::Visits, 'initial_visits' => 10, 'initial_amount' => 450,
            'comment' => '10 классика по 45',
        ]));

        $visit = $this->visits()->register(VisitData::from([
            'master_id' => $this->master()->id,
            'service_id' => $this->service(50)->id,
            'base_price' => 50, 'service_price' => 45,   // оператор вписал цену сеанса по серту
            'certificate_id' => $cert->id,
        ]));

        $cert->refresh();
        $this->assertSame(9, $cert->remaining_visits);
        $this->assertEquals(0, $visit->paid_amount);
        $this->assertSame(PaymentType::Certificate, $visit->payment_type);
        $this->assertEquals(45, $visit->service_price);           // а не сумма всего сертификата
        $this->assertEquals(15.75, $visit->salaryAmount());       // 35% от 45, не от 450
    }

    public function test_surcharge_counts_into_cash_card_revenue(): void
    {
        $master = $this->master();
        $cert = $this->certificates()->issue(CertificateData::from(['type' => CertificateType::Money, 'initial_amount' => 20]));

        $this->visits()->register(VisitData::from([
            'master_id' => $master->id,
            'service_id' => $this->service(60)->id,
            'base_price' => 60, 'service_price' => 60,
            'certificate_id' => $cert->id,
            'surcharge_amount' => 40,
            'surcharge_payment_type' => PaymentType::Card,
        ]));

        $report = app(Reports::class);
        $report->data = ['from' => now()->startOfMonth()->toDateString(), 'until' => now()->toDateString(), 'master_id' => null];

        $rev = $report->revenue();
        $this->assertEquals(40, $rev['card']);   // доплата 40 упала в «Карта»
        $this->assertEquals(0, $rev['cash']);
        $this->assertEquals(40, $rev['total']);
    }

    public function test_certificate_delete_blocked_when_used(): void
    {
        $cert = $this->certificates()->issue(CertificateData::from(['type' => CertificateType::Money, 'initial_amount' => 100]));

        // Пока не использован — удалять можно.
        $this->assertFalse($cert->visits()->exists());

        $this->visits()->register(VisitData::from([
            'master_id' => $this->master()->id,
            'service_id' => $this->service(60)->id,
            'base_price' => 60, 'service_price' => 60,
            'certificate_id' => $cert->id,
        ]));

        // После посещения — уже нельзя (условие видимости кнопки удаления).
        $this->assertTrue($cert->visits()->exists());
    }

    public function test_delete_with_reversal_restores_certificate(): void
    {
        $cert = $this->certificates()->issue(CertificateData::from(['type' => CertificateType::Money, 'initial_amount' => 100]));

        $visit = $this->visits()->register(VisitData::from([
            'master_id' => $this->master()->id,
            'service_id' => $this->service(60)->id,
            'base_price' => 60, 'service_price' => 60,
            'certificate_id' => $cert->id,
        ]));

        $this->assertEquals(40, $cert->refresh()->remaining_amount);

        $this->visits()->deleteWithReversal($visit);

        $cert->refresh();
        $this->assertEquals(100, $cert->remaining_amount);           // остаток вернулся
        $this->assertSame(CertificateStatus::Active, $cert->status);
        $this->assertDatabaseMissing('visits', ['id' => $visit->id]);
        $this->assertSame(1, $cert->operations()->count());          // осталась только продажа
    }
}
