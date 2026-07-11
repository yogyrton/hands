<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalInfoTest extends TestCase
{
    use RefreshDatabase;

    public function test_footer_shows_legal_requisites_and_receipt_link(): void
    {
        $settings = [
            'legal_name' => 'ИП Парусов Егор Васильевич',
            'legal_unp' => '392038435',
            'legal_reg_authority' => 'Оршанский райисполком',
            'legal_reg_date' => '17.06.2026',
            'legal_address' => 'Витебская обл., г. Орша, ул. 1 Красная, д. 3',
            'work_hours' => 'Ежедневно с 9:00 до 21:00',
            'payment_receipt' => 'legal/receipt.pdf',
        ];
        foreach ($settings as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->get('/')
            ->assertOk()
            ->assertSee('ИП Парусов Егор Васильевич')
            ->assertSee('УНП 392038435')
            ->assertSee('выдано Оршанский райисполком от 17.06.2026')
            ->assertSee('Витебская обл., г. Орша, ул. 1 Красная, д. 3')
            ->assertSee('Режим работы: Ежедневно с 9:00 до 21:00')
            // ссылка на образец чека открывает файл в новой вкладке
            ->assertSee('Образец документа об оплате')
            ->assertSee('/storage/legal/receipt.pdf', false);
    }

    public function test_receipt_link_hidden_when_not_uploaded(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('Образец документа об оплате');
    }
}
