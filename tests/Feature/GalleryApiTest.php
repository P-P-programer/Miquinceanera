<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GalleryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_public_fotos_for_the_final_carousel(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('fotos/a.webp', 'one');
        Storage::disk('public')->put('fotos/b.webp', 'two');

        $this->getJson('/api/gallery/photos')
            ->assertOk()
            ->assertJsonPath('data.count', 2)
            ->assertJsonPath('data.photos.0.name', 'a.webp');
    }
}