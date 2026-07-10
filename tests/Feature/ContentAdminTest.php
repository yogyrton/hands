<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Faqs\Pages\CreateFaq;
use App\Filament\Resources\Faqs\Pages\EditFaq;
use App\Filament\Resources\Masters\Pages\CreateMaster;
use App\Filament\Resources\Masters\Pages\EditMaster;
use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Models\Faq;
use App\Models\Master;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * CRUD в админке: создание с проверкой полей в БД, обновление каждого поля,
 * удаление (soft delete для мастеров, обычное — для услуг и FAQ).
 */
class ContentAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
    }

    // ================================================================
    //  МАСТЕРА
    // ================================================================

    public function test_admin_creates_master_with_all_fields(): void
    {
        $service = Service::create([
            'slug' => 'klassika', 'name' => 'Классический массаж', 'level' => 4,
            'base_price' => 60, 'lead' => 'lead',
        ]);

        Livewire::test(CreateMaster::class)
            ->fillForm([
                'name' => 'Дмитрий',
                'slug' => 'dmitry',
                'name_dative' => 'Дмитрию',
                'role' => 'Массажист',
                'experience_label' => '8 лет',
                'salary_rate' => 40,
                'yclients_url' => 'https://yclients.com/company/1',
                'sort_order' => 5,
                'is_active' => true,
                'bio1' => 'Первый абзац',
                'bio2' => 'Второй абзац',
                'principles' => [
                    ['title' => 'Внимание к телу', 'description' => 'Слушаю запрос'],
                ],
                'services' => [$service->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('masters', [
            'name' => 'Дмитрий',
            'slug' => 'dmitry',
            'name_dative' => 'Дмитрию',
            'role' => 'Массажист',
            'experience_label' => '8 лет',
            'yclients_url' => 'https://yclients.com/company/1',
            'sort_order' => 5,
            'is_active' => true,
        ]);

        $master = Master::query()->where('slug', 'dmitry')->firstOrFail();
        $this->assertEquals(40.0, (float) $master->salary_rate);
        $this->assertSame('Внимание к телу', $master->principles[0]['title']);
        $this->assertSame([$service->id], $master->services->pluck('id')->all());
    }

    public function test_admin_updates_every_master_field(): void
    {
        $master = Master::create([
            'slug' => 'old', 'name' => 'Старое имя', 'name_dative' => 'Старому',
            'role' => 'Старая роль', 'yclients_url' => 'https://old.example',
            'experience_label' => '1 год', 'bio1' => 'old1', 'bio2' => 'old2',
            'salary_rate' => 35, 'sort_order' => 1, 'is_active' => true,
        ]);

        Livewire::test(EditMaster::class, ['record' => $master->getKey()])
            ->fillForm([
                'name' => 'Новое имя',
                'slug' => 'new',
                'name_dative' => 'Новому',
                'role' => 'Новая роль',
                'experience_label' => '10 лет',
                'salary_rate' => 50,
                'yclients_url' => 'https://new.example',
                'sort_order' => 9,
                'is_active' => false,
                'bio1' => 'new1',
                'bio2' => 'new2',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('masters', [
            'id' => $master->id,
            'name' => 'Новое имя',
            'slug' => 'new',
            'name_dative' => 'Новому',
            'role' => 'Новая роль',
            'experience_label' => '10 лет',
            'yclients_url' => 'https://new.example',
            'sort_order' => 9,
            'is_active' => false,
        ]);
        $this->assertEquals(50.0, (float) $master->fresh()->salary_rate);
        $this->assertDatabaseMissing('masters', ['id' => $master->id, 'name' => 'Старое имя']);
    }

    public function test_admin_soft_deletes_master(): void
    {
        $master = Master::create([
            'slug' => 'del', 'name' => 'Удаляемый', 'name_dative' => 'Удаляемому',
            'role' => 'Массажист', 'yclients_url' => 'https://e.com',
            'bio1' => 'a', 'bio2' => 'b', 'salary_rate' => 35,
        ]);

        Livewire::test(EditMaster::class, ['record' => $master->getKey()])
            ->callAction('delete');

        $this->assertSoftDeleted('masters', ['id' => $master->id]);
    }

    // ================================================================
    //  УСЛУГИ
    // ================================================================

    public function test_admin_creates_service_with_all_fields(): void
    {
        Livewire::test(CreateService::class)
            ->fillForm([
                'name' => 'Спортивный массаж',
                'slug' => 'sport',
                'level' => 5,
                'base_price' => 75,
                'duration_label' => 'от 90 мин',
                'price_label' => 'от 75 р',
                'sort_order' => 2,
                'is_active' => true,
                'lead' => 'Вводный абзац',
                'ideal' => 'после тренировок',
                'request_lead' => 'Работаем по запросу',
                'includes' => [
                    ['n' => 1, 'title' => 'Разминка', 'description' => 'подготовка мышц'],
                ],
                'requests' => ['спина', 'ноги'],
                'details' => [
                    ['title' => 'Как проходит', 'body' => 'Описание сеанса'],
                ],
                'seo_title' => 'SEO заголовок',
                'seo_description' => 'SEO описание',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('services', [
            'name' => 'Спортивный массаж',
            'slug' => 'sport',
            'level' => 5,
            'duration_label' => 'от 90 мин',
            'price_label' => 'от 75 р',
            'sort_order' => 2,
            'is_active' => true,
            'seo_title' => 'SEO заголовок',
        ]);

        $service = Service::query()->where('slug', 'sport')->firstOrFail();
        $this->assertEquals(75.0, (float) $service->base_price);
        $this->assertSame(['спина', 'ноги'], $service->requests);
        $this->assertSame('Разминка', $service->includes[0]['title']);
        $this->assertSame('Как проходит', $service->details[0]['title']);
    }

    public function test_admin_updates_every_service_field(): void
    {
        $service = Service::create([
            'slug' => 'old-s', 'name' => 'Старая услуга', 'level' => 2, 'base_price' => 40,
            'duration_label' => 'от 30 мин', 'price_label' => 'от 40 р',
            'lead' => 'old lead', 'sort_order' => 1, 'is_active' => true,
        ]);

        Livewire::test(EditService::class, ['record' => $service->getKey()])
            ->fillForm([
                'name' => 'Новая услуга',
                'slug' => 'new-s',
                'level' => 5,
                'base_price' => 90,
                'duration_label' => 'от 120 мин',
                'price_label' => 'от 90 р',
                'lead' => 'new lead',
                'sort_order' => 7,
                'is_active' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'name' => 'Новая услуга',
            'slug' => 'new-s',
            'level' => 5,
            'duration_label' => 'от 120 мин',
            'price_label' => 'от 90 р',
            'sort_order' => 7,
            'is_active' => false,
        ]);
        $this->assertEquals(90.0, (float) $service->fresh()->base_price);
    }

    public function test_admin_deletes_service(): void
    {
        $service = Service::create([
            'slug' => 'del-s', 'name' => 'Удаляемая', 'level' => 3, 'base_price' => 50,
            'lead' => 'lead',
        ]);

        Livewire::test(EditService::class, ['record' => $service->getKey()])
            ->callAction('delete');

        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    // ================================================================
    //  FAQ
    // ================================================================

    public function test_admin_creates_faq(): void
    {
        Livewire::test(CreateFaq::class)
            ->fillForm([
                'question' => 'Нужна ли предоплата?',
                'answer' => 'Нет, оплата после сеанса.',
                'sort_order' => 3,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('faqs', [
            'question' => 'Нужна ли предоплата?',
            'answer' => 'Нет, оплата после сеанса.',
            'sort_order' => 3,
            'is_active' => true,
        ]);
    }

    public function test_admin_updates_faq(): void
    {
        $faq = Faq::create([
            'question' => 'Старый вопрос?', 'answer' => 'Старый ответ',
            'sort_order' => 1, 'is_active' => true,
        ]);

        Livewire::test(EditFaq::class, ['record' => $faq->getKey()])
            ->fillForm([
                'question' => 'Новый вопрос?',
                'answer' => 'Новый ответ',
                'sort_order' => 8,
                'is_active' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('faqs', [
            'id' => $faq->id,
            'question' => 'Новый вопрос?',
            'answer' => 'Новый ответ',
            'sort_order' => 8,
            'is_active' => false,
        ]);
    }

    public function test_admin_deletes_faq(): void
    {
        $faq = Faq::create([
            'question' => 'Удалить?', 'answer' => 'Да', 'sort_order' => 1,
        ]);

        Livewire::test(EditFaq::class, ['record' => $faq->getKey()])
            ->callAction('delete');

        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
    }
}
