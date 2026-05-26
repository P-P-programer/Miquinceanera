<?php

namespace Tests\Feature;

use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_group_registration_and_returns_qr(): void
    {
        $response = $this->postJson('/api/registrations', [
            'titular_name' => 'Juan Pérez',
            'titular_email' => 'juan@example.com',
            'titular_phone' => '3001234567',
            'guests' => [
                ['name' => 'Ana Pérez', 'email' => 'ana@example.com'],
                ['name' => 'Luis Pérez'],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.registration.total_people', 3)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'registration' => ['id', 'event_id', 'titular_name', 'qr_code', 'people'],
                    'qr_value',
                    'qr_image_url',
                    'qr_download_url',
                    'available_seats',
                ],
            ]);

        $this->assertDatabaseHas('registrations', [
            'titular_name' => 'Juan Pérez',
            'total_people' => 3,
            'status' => 'confirmed',
        ]);

        $this->assertDatabaseCount('registration_people', 3);

        $qrCode = $response->json('data.qr_value');

        $this->getJson('/api/registrations/'.$qrCode)
            ->assertOk()
            ->assertJsonPath('data.qr_code', $qrCode);

        $this->get('/api/registrations/'.$qrCode.'/qr')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml; charset=UTF-8');

        $this->get('/api/registrations/'.$qrCode.'/qr/download')
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="qr-'.$qrCode.'.svg"');

        $scanResponse = $this->postJson('/api/registrations/'.$qrCode.'/scan', [
            'scanned_by' => 'Admin Local',
            'note' => 'Validado en puerta principal',
        ]);

        $scanResponse->assertCreated()
            ->assertJsonPath('data.qr_code', $qrCode)
            ->assertJsonPath('data.already_scanned', false);

        $this->assertDatabaseHas('attendance_logs', [
            'registration_id' => $response->json('data.registration.id'),
            'scanned_by' => 'Admin Local',
            'note' => 'Validado en puerta principal',
        ]);

        $secondScan = $this->postJson('/api/registrations/'.$qrCode.'/scan');

        $secondScan->assertOk()
            ->assertJsonPath('data.already_scanned', true)
            ->assertJsonPath('data.qr_code', $qrCode);
    }

    public function test_it_rejects_registrations_over_five_people(): void
    {
        $response = $this->postJson('/api/registrations', [
            'titular_name' => 'Grupo Grande',
            'guests' => [
                ['name' => 'A'],
                ['name' => 'B'],
                ['name' => 'C'],
                ['name' => 'D'],
                ['name' => 'E'],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['guests']);
    }

    public function test_it_returns_404_for_invalid_qr_scan(): void
    {
        $this->postJson('/api/registrations/invalid-token/scan')
            ->assertNotFound();
    }
}
