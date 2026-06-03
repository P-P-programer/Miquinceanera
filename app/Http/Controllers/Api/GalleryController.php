<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GalleryController extends Controller
{
    public function index(): JsonResponse
    {
        $files = collect(Storage::disk('public')->files('fotos'))
            ->filter(fn (string $file) => in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['webp', 'jpg', 'jpeg', 'png'], true))
            ->sortBy(fn (string $file) => basename($file), SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $photos = $files->map(fn (string $file, int $index) => [
            'id' => $index + 1,
            'name' => basename($file),
            'photo_url' => asset('storage/'.$file),
        ])->values();

        return response()->json([
            'data' => [
                'count' => $photos->count(),
                'photos' => $photos,
            ],
        ]);
    }
}