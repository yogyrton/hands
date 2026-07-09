<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Faq;
use App\Models\Master;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin(): void
    {
        $this->get('/admin')->assertRedirect();
    }

    public function test_master_role_cannot_access_panel(): void
    {
        $master = User::factory()->create(['role' => UserRole::Master]);

        $this->actingAs($master)->get('/admin')->assertForbidden();
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

        $this->actingAs($admin);

        $this->get('/admin')->assertOk();
        $this->get('/admin/services')->assertOk();
        $this->get('/admin/services/create')->assertOk();
        $this->get("/admin/services/{$service->id}/edit")->assertOk();
        $this->get('/admin/masters')->assertOk();
        $this->get('/admin/masters/create')->assertOk();
        $this->get("/admin/masters/{$master->id}/edit")->assertOk();
        $this->get('/admin/faqs')->assertOk();
        $this->get('/admin/faqs/create')->assertOk();
        $this->get('/admin/manage-studio-settings')->assertOk();
    }
}
