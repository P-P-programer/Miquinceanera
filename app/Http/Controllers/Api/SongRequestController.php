<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Models\SongRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SongRequestController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'access_code' => ['required', 'string'],
            'requester_name' => ['nullable', 'string', 'max:120'],
            'song_title' => ['required', 'string', 'max:180'],
            'artist_name' => ['nullable', 'string', 'max:180'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $registration = $this->resolveRegistration($validated['access_code']);

        $songRequest = SongRequest::create([
            'event_id' => $registration->event_id,
            'registration_id' => $registration->id,
            'requester_name' => $validated['requester_name'] ?: $registration->titular_name,
            'song_title' => $validated['song_title'],
            'artist_name' => $validated['artist_name'] ?: null,
            'note' => $validated['note'] ?: null,
        ]);

        return response()->json([
            'message' => 'Canción guardada para el DJ.',
            'data' => [
                'id' => $songRequest->id,
                'requester_name' => $songRequest->requester_name,
                'song_title' => $songRequest->song_title,
                'artist_name' => $songRequest->artist_name,
                'note' => $songRequest->note,
                'created_at' => $songRequest->created_at?->format('d/m/Y H:i'),
            ],
        ], 201);
    }

    private function resolveRegistration(string $accessCode): Registration
    {
        $registration = Registration::query()
            ->where('access_code', $accessCode)
            ->where('status', '!=', 'cancelled')
            ->first();

        if (! $registration) {
            throw ValidationException::withMessages([
                'access_code' => 'No encontramos un registro activo con ese código.',
            ]);
        }

        return $registration;
    }
}