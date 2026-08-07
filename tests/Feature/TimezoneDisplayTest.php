<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\Services\CertificateServiceInterface;
use App\Data\CertificateData;
use App\Enums\CertificateType;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Filament\Resources\Certificates\Pages\EditCertificate;
use App\Filament\Resources\Visits\Pages\ListVisits;
use App\Models\Master;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TimezoneDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_filament_display_timezone_is_minsk(): void
    {
        $this->assertSame('Europe/Minsk', FilamentTimezone::get());
    }

    public function test_visit_time_stored_utc_but_shown_in_minsk(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $master = Master::create([
            'slug' => 'm-tz', 'name' => 'Анна', 'name_dative' => 'Анне', 'role' => 'Массажист',
            'yclients_url' => 'https://e.com', 'bio1' => 'a', 'bio2' => 'b', 'salary_rate' => 35,
        ]);
        $service = Service::create([
            'slug' => 's-tz', 'name' => 'Массаж', 'level' => 4, 'base_price' => 50, 'lead' => 'l',
        ]);

        // Сегодня 10:00 по UTC (как хранится в БД).
        $utc = now()->startOfDay()->addHours(10);
        $visit = Visit::create([
            'master_id' => $master->id, 'service_id' => $service->id,
            'base_price' => 50, 'service_price' => 50, 'paid_amount' => 50,
            'payment_type' => PaymentType::Cash, 'performed_at' => $utc,
        ]);

        // В БД — по-прежнему UTC 10:00.
        $this->assertSame('10:00', $visit->fresh()->performed_at->format('H:i'));

        // В таблице админки — местное (Могилёв, UTC+3) 13:00.
        Livewire::test(ListVisits::class)->assertSee('13:00');
    }

    /**
     * Важно: часовой пояс отображения НЕ должен сдвигать поля-даты (без времени)
     * на день. Ставим срок действия 31.12.2026 через форму — сохраниться должно
     * ровно 2026-12-31, а не 2026-12-30.
     */
    public function test_date_only_field_not_shifted_by_display_timezone(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $cert = app(CertificateServiceInterface::class)->issue(CertificateData::from([
            'type' => CertificateType::Money, 'initial_amount' => 100,
        ]));

        Livewire::test(EditCertificate::class, ['record' => $cert->id])
            ->fillForm(['expires_at' => '2026-12-31'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('2026-12-31', $cert->fresh()->expires_at->toDateString());
    }
}
