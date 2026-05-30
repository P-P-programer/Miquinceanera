<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AlbumPhoto;
use App\Models\AttendanceLog;
use App\Models\Event;
use App\Models\Registration;
use App\Models\SongRequest;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $event = Event::query()->orderBy('id')->first();
        $totalCapacity = $event?->capacity ?? 100;
        $occupiedSeats = (int) Registration::query()->where('status', 'confirmed')->sum('total_people');
        $availableSeats = max(0, $totalCapacity - $occupiedSeats);
        $registrations = Registration::with(['people'])
            ->withCount('attendanceLogs')
            ->latest()
            ->get()
            ->map(fn (Registration $registration) => $this->formatRegistration($registration))
            ->values();

        return view('admin.dashboard', [
            'event' => $event,
            'stats' => [
                'registrations' => Registration::count(),
                'occupiedSeats' => $occupiedSeats,
                'availableSeats' => $availableSeats,
                'attendanceLogs' => AttendanceLog::count(),
                'albumPhotos' => AlbumPhoto::count(),
                'songRequests' => SongRequest::count(),
            ],
            'registrations' => $registrations,
            'albumPhotos' => AlbumPhoto::query()
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (AlbumPhoto $photo) => [
                    'id' => $photo->id,
                    'submitted_by' => $photo->submitted_by,
                    'caption' => $photo->caption,
                    'photo_url' => asset('storage/'.$photo->photo_path),
                    'created_at' => $photo->created_at?->format('d/m/Y H:i'),
                ])
                ->values(),
            'songRequests' => SongRequest::query()
                ->latest()
                ->limit(20)
                ->get()
                ->values(),
            'recentScans' => AttendanceLog::with('registration')
                ->latest('scanned_at')
                ->limit(8)
                ->get(),
        ]);
    }

    private function formatRegistration(Registration $registration): array
    {
        $statusLabel = match (true) {
            $registration->status === 'cancelled' => 'Cancelado',
            $registration->attendance_logs_count > 0 => 'Asistió',
            $registration->status === 'pending' => 'Pendiente',
            default => 'Confirmado',
        };

        $statusClass = match (true) {
            $registration->status === 'cancelled' => 'border-rose-300/20 bg-rose-300/10 text-rose-100',
            $registration->attendance_logs_count > 0 => 'border-cyan-300/20 bg-cyan-300/10 text-cyan-100',
            $registration->status === 'pending' => 'border-amber-300/20 bg-amber-300/10 text-amber-100',
            default => 'border-emerald-300/20 bg-emerald-300/10 text-emerald-100',
        };

        return [
            'id' => $registration->id,
            'status' => $registration->status,
            'status_label' => $statusLabel,
            'status_class' => $statusClass,
            'titular_name' => $registration->titular_name,
            'total_people' => $registration->total_people,
            'access_code' => $registration->access_code,
            'qr_code' => $registration->qr_code,
            'qr_image_url' => route('registrations.qr', ['qrCode' => $registration->qr_code]),
            'qr_download_url' => route('registrations.qr.download', ['qrCode' => $registration->qr_code]),
            'created_at' => $registration->created_at?->format('d/m/Y H:i'),
            'created_at_iso' => $registration->created_at?->toIso8601String(),
            'people' => $registration->people->map(fn ($person) => [
                'name' => $person->name,
                'is_titular' => $person->is_titular,
            ])->values(),
            'search_text' => mb_strtolower(trim(implode(' ', array_filter([
                $registration->titular_name,
                $registration->access_code,
                $registration->people->pluck('name')->implode(' '),
            ])))),
            'attendance_logs_count' => $registration->attendance_logs_count,
        ];
    }
}
