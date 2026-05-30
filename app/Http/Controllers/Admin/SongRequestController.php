<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SongRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class SongRequestController extends Controller
{
    public function export(): Response
    {
        $songs = SongRequest::query()
            ->latest()
            ->get()
            ->values();

        $pdf = Pdf::loadView('admin.song-requests-pdf', [
            'songs' => $songs,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);

        return $pdf->download('canciones-dj.pdf');
    }
}