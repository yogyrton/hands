<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_dashboard(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Прибыль за месяц');
    }

    public function test_master_is_redirected_to_accounting(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Master]));

        $this->get('/admin')->assertRedirect('/admin/visits');
    }
}
