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

    public function test_money_certificate_insufficient_requires_surcharge(): void
    {
        $cert = $this->certificates()->issue(CertificateData::from(['type' => CertificateType::Money, 'initial_amount' => 20]));

        $visit = $this->visits()->register(VisitData::from([
            'master_id' => $this->master()->id,
            'service_id' => $this->service(60)->id,
            'base_price' => 60, 'service_price' => 60,
            'certificate_id' => $cert->id,
            'payment_type' => PaymentType::Cash,
        ]));

        $cert->refresh();
        $this->assertEquals(0, $cert->remaining_amount);
        $this->assertSame(CertificateStatus::Used, $cert->status);  // остаток исчерпан
        $this->assertEquals(40, $visit->paid_amount);               // доплата 60-20
        $this->assertSame(PaymentType::Cash, $visit->payment_type);
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
