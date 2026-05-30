<?php

namespace Tests\Feature;

use App\Models\AlbumPhoto;
use App\Models\Event;
use App\Models\Registration;
use App\Models\SongRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AlbumAndSongsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_album_photos_for_a_valid_access_code(): void
    {
        Storage::fake('public');

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
            'qr_code' => 'token-test-album',
            'access_code' => 'ALBM-1234',
            'status' => 'confirmed',
        ]);

        $response = $this->postJson('/api/album/photos', [
            'access_code' => $registration->access_code,
            'submitted_by' => 'Laura Gómez',
            'caption' => 'Una foto linda',
            'photo' => UploadedFile::fake()->createWithContent(
                'momento.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO6Xh4sAAAAASUVORK5CYII='),
            ),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.submitted_by', 'Laura Gómez')
            ->assertJsonPath('data.caption', 'Una foto linda');

        $this->assertDatabaseHas('album_photos', [
            'event_id' => $event->id,
            'registration_id' => $registration->id,
            'submitted_by' => 'Laura Gómez',
            'caption' => 'Una foto linda',
        ]);
    }

    public function test_it_deletes_album_photos_from_admin_with_confirmation_flow(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'name' => 'admin',
            'email' => 'admin-album@example.com',
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
            'qr_code' => 'token-test-album-delete',
            'access_code' => 'ALBM-5678',
            'status' => 'confirmed',
        ]);

        Storage::disk('public')->put('album-photos/'.$event->id.'/photo.jpg', 'fake-image');

        $photo = AlbumPhoto::create([
            'event_id' => $event->id,
            'registration_id' => $registration->id,
            'submitted_by' => 'Laura Gómez',
            'caption' => 'Foto para eliminar',
            'photo_path' => 'album-photos/'.$event->id.'/photo.jpg',
            'photo_name' => 'photo.jpg',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.album-photos.destroy', $photo->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('album_photos', [
            'id' => $photo->id,
        ]);

        Storage::disk('public')->assertMissing('album-photos/'.$event->id.'/photo.jpg');
    }

    public function test_it_exports_song_requests_as_a_pdf_for_admin(): void
    {
        $admin = User::factory()->create([
            'name' => 'admin',
            'email' => 'admin-songs@example.com',
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
            'qr_code' => 'token-test-song',
            'access_code' => 'SONG-1234',
            'status' => 'confirmed',
        ]);

        SongRequest::create([
            'event_id' => $event->id,
            'registration_id' => $registration->id,
            'requester_name' => 'Laura Gómez',
            'song_title' => 'La vida es un carnaval',
            'artist_name' => 'Celia Cruz',
            'note' => 'Para abrir la pista',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.song-requests.export'))
            ->assertOk()
            ->assertDownload('canciones-dj.pdf');
    }
}