<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Master;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private Service $activeService;

    private Service $inactiveService;

    private Master $activeMaster;

    private Master $inactiveMaster;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activeService = Service::create([
            'slug' => 'aktiv-usluga', 'name' => 'АктивУслуга', 'level' => 4, 'base_price' => 50,
            'lead' => 'lead', 'sort_order' => 1, 'is_active' => true,
        ]);
        $this->inactiveService = Service::create([
            'slug' => 'skrytaya-usluga', 'name' => 'СкрытаяУслуга', 'level' => 3, 'base_price' => 50,
            'lead' => 'lead', 'sort_order' => 2, 'is_active' => false,
        ]);

        $this->activeMaster = Master::create([
            'slug' => 'aktiv-master', 'name' => 'АктивМастер', 'name_dative' => 'АктивМастеру',
            'role' => 'Массажист', 'yclients_url' => 'https://e.com', 'bio1' => 'b1', 'bio2' => 'b2',
            'sort_order' => 1, 'is_active' => true,
        ]);
        $this->inactiveMaster = Master::create([
            'slug' => 'skrytyy-master', 'name' => 'СкрытыйМастер', 'name_dative' => 'СкрытомуМастеру',
            'role' => 'Массажист', 'yclients_url' => 'https://e.com', 'bio1' => 'b1', 'bio2' => 'b2',
            'sort_order' => 2, 'is_active' => false,
        ]);

        // активный мастер оказывает активную И скрытую услугу
        $this->activeMaster->services()->attach([$this->activeService->id, $this->inactiveService->id]);
        // активная услуга дополнительно привязана к скрытому мастеру
        $this->activeService->masters()->attach($this->inactiveMaster->id);
    }

    public function test_home_shows_only_active(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('АктивУслуга')
            ->assertDontSee('СкрытаяУслуга')
            ->assertSee('АктивМастер')
            ->assertDontSee('СкрытыйМастер');
    }

    public function test_inactive_pages_return_404(): void
    {
        $this->get('/services/'.$this->inactiveService->slug)->assertNotFound();
        $this->get('/masters/'.$this->inactiveMaster->slug)->assertNotFound();
    }

    public function test_service_page_lists_only_active_masters(): void
    {
        $this->get('/services/'.$this->activeService->slug)
            ->assertOk()
            ->assertSee('АктивМастер')
            ->assertDontSee('СкрытыйМастер');
    }

    public function test_service_page_choose_master_scrolls_to_its_own_masters(): void
    {
        $response = $this->get('/services/'.$this->activeService->slug)->assertOk();

        // Кнопка «Выбрать мастера» ведёт к блоку мастеров ЭТОЙ услуги на этой же
        // странице (относительный #masters), а не на главную ко всем мастерам.
        $response->assertSee('<a href="#masters" class="btn btn-outline">Выбрать мастера</a>', false);
        $response->assertSee('id="masters"', false);
    }

    public function test_master_page_lists_only_active_services(): void
    {
        $this->get('/masters/'.$this->activeMaster->slug)
            ->assertOk()
            ->assertSee('АктивУслуга')
            ->assertDontSee('СкрытаяУслуга');
    }

    public function test_active_services_relation_returns_only_active(): void
    {
        // Мастер оказывает активную И скрытую услугу — связь отдаёт только активную.
        $ids = $this->activeMaster->activeServices->pluck('id')->all();

        $this->assertSame([$this->activeService->id], $ids);
    }

    public function test_active_masters_relation_returns_only_active(): void
    {
        // Услуга привязана к активному И скрытому мастеру — связь отдаёт только активного.
        $ids = $this->activeService->activeMasters->pluck('id')->all();

        $this->assertSame([$this->activeMaster->id], $ids);
    }
}
