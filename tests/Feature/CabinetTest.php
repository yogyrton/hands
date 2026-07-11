<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\Services\CabinetServiceInterface;
use App\Enums\UserRole;
use App\Models\Cabinet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CabinetTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_cabinet_shows_on_home_inactive_hidden(): void
    {
        Cabinet::create(['slug' => 'forest', 'name' => 'Лесной', 'description' => 'Тихий лес', 'sort_order' => 1, 'is_active' => true]);
        Cabinet::create(['slug' => 'hidden', 'name' => 'Черновик', 'description' => 'Скрытый', 'sort_order' => 2, 'is_active' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('id="cabinets"', false)
            ->assertSee('Наши кабинеты')
            ->assertSee('Лесной')
            ->assertDontSee('Черновик');
    }

    public function test_section_hidden_when_no_active_cabinets(): void
    {
        // Ни одного активного кабинета — секции на главной нет вовсе.
        $this->get('/')
            ->assertOk()
            ->assertDontSee('id="cabinets"', false);
    }

    public function test_active_ordered_returns_only_active_in_order(): void
    {
        Cabinet::create(['slug' => 'b', 'name' => 'B', 'sort_order' => 2, 'is_active' => true]);
        Cabinet::create(['slug' => 'a', 'name' => 'A', 'sort_order' => 1, 'is_active' => true]);
        Cabinet::create(['slug' => 'x', 'name' => 'X', 'sort_order' => 0, 'is_active' => false]);

        $names = app(CabinetServiceInterface::class)->activeOrdered()->pluck('name')->all();

        $this->assertSame(['A', 'B'], $names);
    }

    public function test_cabinet_photos_render_as_lazy_carousel(): void
    {
        Storage::fake('public');

        $cabinet = Cabinet::create(['slug' => 'marine', 'name' => 'Морской', 'sort_order' => 1, 'is_active' => true]);
        $cabinet->addMedia(UploadedFile::fake()->image('one.jpg', 40, 50))->toMediaCollection('photos');
        $cabinet->addMedia(UploadedFile::fake()->image('two.jpg', 40, 50))->toMediaCollection('photos');

        $this->get('/')
            ->assertOk()
            // изображения ниже первого экрана — ленивая загрузка (метрики не страдают)
            ->assertSee('data-cab-slide loading="lazy"', false)
            // отдаётся webp-конверсия
            ->assertSee('conversions/one-webp.webp', false)
            // два фото → включается карусель с точками
            ->assertSee('data-cab-dots', false)
            ->assertSee('Показать фото 2', false);
    }

    public function test_only_admin_can_manage_cabinets(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $master = User::factory()->create(['role' => UserRole::Master]);
        $cabinet = Cabinet::create(['slug' => 'forest', 'name' => 'Лесной', 'sort_order' => 1, 'is_active' => true]);

        $this->actingAs($admin)->get('/admin/cabinets')->assertOk();
        $this->actingAs($admin)->get('/admin/cabinets/create')->assertOk();
        $this->actingAs($admin)->get("/admin/cabinets/{$cabinet->id}/edit")->assertOk();

        $this->actingAs($master)->get('/admin/cabinets')->assertForbidden();
    }
}
