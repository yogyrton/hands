<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Master;
use App\Models\Service;
use App\Models\Setting;
use App\Models\SiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_has_meta_canonical_og_and_jsonld(): void
    {
        Setting::query()->updateOrCreate(['key' => 'google_verification'], ['value' => 'GVERIFY123']);
        Setting::query()->updateOrCreate(['key' => 'legal_name'], ['value' => 'ИП Тестовый']);
        Setting::query()->updateOrCreate(['key' => 'instagram_url'], ['value' => 'https://instagram.com/hands.mg/']);

        SiteContent::current()->update([
            'seo_title' => 'ЗаголовокSEOГлавной',
            'seo_description' => 'ОписаниеSEOГлавной',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('<title>ЗаголовокSEOГлавной</title>', false)
            ->assertSee('ОписаниеSEOГлавной', false)
            ->assertSee('<link rel="canonical"', false)
            ->assertSee('property="og:image"', false)
            ->assertSee('name="google-site-verification" content="GVERIFY123"', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('HealthAndBeautyBusiness', false);
    }

    public function test_service_page_h1_contains_city(): void
    {
        Service::create([
            'slug' => 'classic', 'name' => 'Классический массаж', 'level' => 4,
            'base_price' => 65, 'lead' => 'lead', 'is_active' => true,
        ]);

        $this->get('/services/classic')
            ->assertOk()
            ->assertSee('Классический массаж в Могилёве', false);
    }

    public function test_sitemap_command_generates_file_with_pages(): void
    {
        Service::create([
            'slug' => 'classic', 'name' => 'Классический массаж', 'level' => 4,
            'base_price' => 65, 'lead' => 'lead', 'is_active' => true,
        ]);
        Master::create([
            'slug' => 'anna', 'name' => 'Анна', 'name_dative' => 'Анне', 'role' => 'Массажист',
            'yclients_url' => 'https://e.com', 'bio1' => 'a', 'bio2' => 'b', 'is_active' => true,
        ]);

        $this->artisan('sitemap:generate')->assertSuccessful();

        $path = public_path('sitemap.xml');
        $this->assertFileExists($path);
        $xml = file_get_contents($path);
        $this->assertStringContainsString('/services/classic', $xml);
        $this->assertStringContainsString('/masters/anna', $xml);
    }
}
