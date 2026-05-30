<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AlbumPhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class AlbumPhotoController extends Controller
{
    public function destroy(AlbumPhoto $albumPhoto): RedirectResponse
    {
        Storage::disk('public')->delete($albumPhoto->photo_path);
        $albumPhoto->delete();

        return back()->with('status', 'Foto del álbum eliminada.');
    }
}