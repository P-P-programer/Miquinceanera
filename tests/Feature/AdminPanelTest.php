<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Registration;
use App\Models\RegistrationPerson;
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

    public function test_it_shows_registration_data_in_the_dashboard(): void
    {
        $admin = User::factory()->create([
            'name' => 'admin',
            'email' => 'admin-dashboard@example.com',
            'password' => 'admin@1234',
            'role' => 'admin',
        ]);

        $event = Event::create([
            'title' => 'Quinceañera',
            'description' => 'Evento de prueba',
            'starts_at' => now()->addDays(10),
            'capacity' => 100,
            'max_guests_per_registration' => 4,
        ]);

        $registration = Registration::create([
            'event_id' => $event->id,
            'titular_name' => 'Laura Gómez',
            'titular_email' => 'laura@example.com',
            'titular_phone' => '3001234567',
            'total_people' => 3,
            'qr_code' => 'token-test-123',
            'access_code' => 'ABCD-EFGH',
            'status' => 'confirmed',
        ]);

        RegistrationPerson::create([
            'registration_id' => $registration->id,
            'name' => 'Laura Gómez',
            'email' => 'laura@example.com',
            'is_titular' => true,
        ]);

        RegistrationPerson::create([
            'registration_id' => $registration->id,
            'name' => 'Ana Gómez',
            'is_titular' => false,
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Laura Gómez')
            ->assertSee('Ana Gómez')
            ->assertSee('ABCD-EFGH')
            ->assertSee('token-test-123')
            ->assertSee('confirmed');
    }

    public function test_it_can_cancel_a_registration_without_deleting_it(): void
    {
        $admin = User::factory()->create([
            'name' => 'admin',
            'email' => 'admin-cancel@example.com',
            'password' => 'admin@1234',
            'role' => 'admin',
        ]);

        $event = Event::create([
            'title' => 'Quinceañera',
            'description' => 'Evento de prueba',
            'starts_at' => now()->addDays(10),
            'capacity' => 100,
            'max_guests_per_registration' => 4,
        ]);

        $registration = Registration::create([
            'event_id' => $event->id,
            'titular_name' => 'Laura Gómez',
            'titular_email' => 'laura@example.com',
            'titular_phone' => '3001234567',
            'total_people' => 3,
            'qr_code' => 'token-test-456',
            'access_code' => 'WXYZ-1234',
            'status' => 'confirmed',
        ]);

        RegistrationPerson::create([
            'registration_id' => $registration->id,
            'name' => 'Laura Gómez',
            'email' => 'laura@example.com',
            'is_titular' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.registrations.cancel', $registration->id))
            ->assertRedirect();

        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'status' => 'cancelled',
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Cancelado');
    }
}
