<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\Event;
use App\Models\Registration;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $event = Event::query()->orderBy('id')->first();
        $totalCapacity = $event?->capacity ?? 100;
        $occupiedSeats = (int) Registration::query()->sum('total_people');
        $availableSeats = max(0, $totalCapacity - $occupiedSeats);

        return view('admin.dashboard', [
            'event' => $event,
            'stats' => [
                'registrations' => Registration::count(),
                'occupiedSeats' => $occupiedSeats,
                'availableSeats' => $availableSeats,
                'attendanceLogs' => AttendanceLog::count(),
            ],
            'recentScans' => AttendanceLog::with('registration')
                ->latest('scanned_at')
                ->limit(8)
                ->get(),
        ]);
    }
}
