<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_redirects_guests_to_the_admin_login_page(): void
    {
        $this->get('/admin')
            ->assertRedirect('/login');
    }

    public function test_it_allows_admin_users_to_access_the_dashboard(): void
    {
        $admin = User::factory()->create([
            'name' => 'admin',
            'email' => 'admin-test@example.com',
            'password' => 'admin@1234',
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Control de invitaciones y asistencia');
    }

    public function test_it_blocks_non_admin_users_from_the_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_it_authenticates_admin_credentials(): void
    {
        User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@example.com',
            'password' => 'admin@1234',
            'role' => 'admin',
        ]);

        $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'admin@1234',
        ])->assertRedirect(route('admin.dashboard'));
    }
}
