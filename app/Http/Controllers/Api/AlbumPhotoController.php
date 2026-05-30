<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AlbumPhoto;
use App\Models\Registration;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AlbumPhotoController extends Controller
{
    public function index(Request $request)
    {
        $registration = $this->resolveRegistration($request->string('access_code')->toString());

        $photos = AlbumPhoto::query()
            ->where('event_id', $registration->event_id)
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (AlbumPhoto $photo) => $this->formatPhoto($photo))
            ->values();

        return response()->json([
            'message' => 'Álbum cargado correctamente.',
            'data' => [
                'photos' => $photos,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'access_code' => ['required', 'string'],
            'submitted_by' => ['nullable', 'string', 'max:120'],
            'caption' => ['nullable', 'string', 'max:500'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $registration = $this->resolveRegistration($validated['access_code']);

        $photo = $request->file('photo');
        [$photoPath, $photoName] = $this->storePhoto($photo, 'album-photos/'.$registration->event_id);

        $record = AlbumPhoto::create([
            'event_id' => $registration->event_id,
            'registration_id' => $registration->id,
            'submitted_by' => $validated['submitted_by'] ?: $registration->titular_name,
            'caption' => $validated['caption'] ?: null,
            'photo_name' => $photoName,
            'photo_path' => $photoPath,
        ]);

        return response()->json([
            'message' => 'Foto guardada en el álbum.',
            'data' => $this->formatPhoto($record),
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

    private function formatPhoto(AlbumPhoto $photo): array
    {
        return [
            'id' => $photo->id,
            'submitted_by' => $photo->submitted_by,
            'caption' => $photo->caption,
            'photo_url' => asset('storage/'.$photo->photo_path),
            'created_at' => $photo->created_at?->format('d/m/Y H:i'),
        ];
    }

    private function storePhoto(UploadedFile $photo, string $directory): array
    {
        $realPath = $photo->getRealPath();
        $contents = $realPath ? file_get_contents($realPath) : false;

        if (
            $contents !== false
            && function_exists('imagecreatefromstring')
            && function_exists('imagewebp')
        ) {
            $image = @imagecreatefromstring($contents);

            if ($image !== false) {
                ob_start();
                imagewebp($image, null, 82);
                $webpContents = ob_get_clean();
                imagedestroy($image);

                if ($webpContents !== false) {
                    $photoPath = $directory.'/'.Str::uuid().'.webp';
                    Storage::disk('public')->put($photoPath, $webpContents);

                    return [$photoPath, pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME).'.webp'];
                }
            }
        }

        $photoPath = $photo->store($directory, 'public');

        return [$photoPath, $photo->getClientOriginalName()];
    }
}