<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'admin',
                'password' => 'admin@1234',
                'role' => 'admin',
            ]
        );

        Event::updateOrCreate(
            ['title' => 'Quinceañera'],
            [
                'description' => 'Evento principal de la celebración.',
                'starts_at' => '2026-07-04 19:00:00',
                'capacity' => 100,
                'max_guests_per_registration' => 4,
            ]
        );
    }
}
