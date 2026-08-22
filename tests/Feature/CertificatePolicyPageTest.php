<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class CertificatePolicyPageTest extends TestCase
{
    public function test_certificate_policy_page_opens(): void
    {
        $this->get(route('certificate'))
            ->assertOk()
            ->assertSee('Положение о подарочных сертификатах')
            ->assertSee('Положение № 935');
    }

    public function test_footer_links_to_certificate_policy(): void
    {
        // Ссылка в футере ведёт на страницу положения о сертификатах.
        $this->get(route('certificate'))
            ->assertOk()
            ->assertSee(route('certificate'));
    }
}
