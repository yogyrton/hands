<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\Services\CertificateServiceInterface;
use App\Data\CertificateData;
use App\Enums\CertificateType;
use App\Enums\UserRole;
use App\Filament\Resources\Visits\Pages\CreateVisit;
use App\Models\Faq;
use App\Models\Master;
use App\Models\Service;
use App\Models\SiteContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin(): void
    {
        $this->get('/admin')->assertRedirect();
    }

    public function test_admin_can_open_all_admin_pages(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $service = Service::create([
            'slug' => 'test', 'name' => 'Тест', 'level' => 4, 'base_price' => 50,
            'lead' => 'Вводный текст', 'sort_order' => 1, 'is_active' => true,
            'includes' => [['n' => 1, 'title' => 'A', 'description' => 'b']],
            'requests' => ['x', 'y'],
            'details' => [['title' => 'T', 'body' => 'B']],
        ]);

        $master = Master::create([
            'slug' => 'ivan', 'name' => 'Иван', 'name_dative' => 'Ивану',
            'role' => 'Массажист', 'yclients_url' => 'https://example.com',
            'bio1' => 'Био 1', 'bio2' => 'Био 2', 'sort_order' => 1, 'is_active' => true,
            'principles' => [['title' => 'P', 'description' => 'd']],
        ]);
        $master->services()->attach($service->id);

        Faq::create(['question' => 'Вопрос?', 'answer' => 'Ответ', 'sort_order' => 1]);

        $certificate = app(CertificateServiceInterface::class)->issue(
            CertificateData::from(['type' => CertificateType::Money, 'initial_amount' => 100]),
        );

        $this->actingAs($admin);

        // контент сайта
        $this->get('/admin')->assertOk();
        $this->get('/admin/services')->assertOk();
        $this->get('/admin/services/create')->assertOk();
        $this->get("/admin/services/{$service->id}/edit")->assertOk();
        $this->get('/admin/masters')->assertOk();
        $this->get("/admin/masters/{$master->id}/edit")->assertOk();
        $this->get('/admin/faqs')->assertOk();
        $this->get('/admin/manage-studio-settings')->assertOk();

        $site = SiteContent::current();
        $this->get('/admin/site-contents')->assertRedirect();
        $this->get("/admin/site-contents/{$site->getKey()}/edit")->assertOk();

        // учёт
        $this->get('/admin/visits')->assertOk();
        $this->get('/admin/visits/create')->assertOk();
        $this->get('/admin/certificates')->assertOk();
        $this->get('/admin/certificates/create')->assertOk();
        $this->get("/admin/certificates/{$certificate->getKey()}")->assertOk();
        $this->get('/admin/reports')->assertOk();
    }

    public function test_master_sees_accounting_but_not_site_content(): void
    {
        $master = User::factory()->create(['role' => UserRole::Master]);

        $this->actingAs($master);

        // доступ в панель есть
        $this->get('/admin')->assertOk();

        // учёт доступен
        $this->get('/admin/visits')->assertOk();
        $this->get('/admin/certificates')->assertOk();
        $this->get('/admin/reports')->assertOk();

        // контент сайта — запрещён
        $this->get('/admin/services')->assertForbidden();
        $this->get('/admin/masters')->assertForbidden();
        $this->get('/admin/manage-studio-settings')->assertForbidden();
    }

    public function test_visit_created_via_filament_form(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $service = Service::create([
            'slug' => 's1', 'name' => 'Услуга', 'level' => 4, 'base_price' => 60, 'lead' => 'l',
        ]);
        $master = Master::create([
            'slug' => 'm1', 'name' => 'Мастер', 'name_dative' => 'Мастеру', 'role' => 'r',
            'yclients_url' => 'https://e.com', 'bio1' => 'a', 'bio2' => 'b', 'salary_rate' => 35,
        ]);

        $this->actingAs($admin);

        Livewire::test(CreateVisit::class)
            ->fillForm([
                'master_id' => $master->id,
                'service_id' => $service->id,
                'base_price' => 60,
                'service_price' => 60,
                'payment_type' => 'cash',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('visits', [
            'master_id' => $master->id,
            'service_id' => $service->id,
            'service_price' => 60,
            'paid_amount' => 60,
        ]);
    }
}
