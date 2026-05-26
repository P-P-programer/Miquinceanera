<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\AttendanceLog;
use App\Models\Registration;
use App\Models\RegistrationPerson;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegistrationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'titular_name' => ['required', 'string', 'max:255'],
            'titular_email' => ['nullable', 'email', 'max:255'],
            'titular_phone' => ['nullable', 'string', 'max:50'],
            'guests' => ['nullable', 'array', 'max:4'],
            'guests.*.name' => ['required_with:guests', 'string', 'max:255'],
            'guests.*.email' => ['nullable', 'email', 'max:255'],
        ]);

        $event = $this->resolveEvent($data['event_id'] ?? null);
        $guestCount = count($data['guests'] ?? []);
        $totalPeople = 1 + $guestCount;

        if ($totalPeople > ($event->max_guests_per_registration + 1)) {
            throw ValidationException::withMessages([
                'guests' => 'El registro no puede superar 5 personas en total.',
            ]);
        }

        $registeredPeople = (int) $event->registrations()->sum('total_people');
        if ($registeredPeople + $totalPeople > $event->capacity) {
            throw ValidationException::withMessages([
                'capacity' => 'No hay cupo suficiente para este registro.',
            ]);
        }

        $registration = DB::transaction(function () use ($event, $data, $guestCount, $totalPeople) {
            $registration = Registration::create([
                'event_id' => $event->id,
                'titular_name' => $data['titular_name'],
                'titular_email' => $data['titular_email'] ?? null,
                'titular_phone' => $data['titular_phone'] ?? null,
                'total_people' => $totalPeople,
                'qr_code' => (string) Str::uuid(),
                'status' => 'confirmed',
            ]);

            RegistrationPerson::create([
                'registration_id' => $registration->id,
                'name' => $data['titular_name'],
                'email' => $data['titular_email'] ?? null,
                'is_titular' => true,
            ]);

            foreach ($data['guests'] ?? [] as $guest) {
                RegistrationPerson::create([
                    'registration_id' => $registration->id,
                    'name' => $guest['name'],
                    'email' => $guest['email'] ?? null,
                    'is_titular' => false,
                ]);
            }

            return $registration->load(['event', 'people']);
        });

        return response()->json([
            'message' => 'Registro creado correctamente.',
            'data' => [
                'registration' => $registration,
                'qr_value' => $registration->qr_code,
                'qr_image_url' => route('registrations.qr', ['qrCode' => $registration->qr_code]),
                'qr_download_url' => route('registrations.qr.download', ['qrCode' => $registration->qr_code]),
                'available_seats' => max(0, $event->capacity - $event->registrations()->sum('total_people')),
            ],
        ], 201);
    }

    public function show(string $qrCode): JsonResponse
    {
        $registration = Registration::with(['event', 'people'])
            ->where('qr_code', $qrCode)
            ->firstOrFail();

        return response()->json([
            'data' => $registration,
        ]);
    }

    public function qr(string $qrCode): Response
    {
        $registration = $this->findRegistrationByQrCode($qrCode);

        return response($this->makeQrSvg($registration->qr_code), 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function downloadQr(string $qrCode): Response
    {
        $registration = $this->findRegistrationByQrCode($qrCode);

        return response($this->makeQrSvg($registration->qr_code), 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="qr-'.$qrCode.'.svg"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function scan(Request $request, string $qrCode): JsonResponse
    {
        $data = $request->validate([
            'scanned_by' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $registration = $this->findRegistrationByQrCode($qrCode);

        $existingLog = AttendanceLog::query()
            ->where('registration_id', $registration->id)
            ->latest('scanned_at')
            ->first();

        if ($existingLog !== null) {
            return response()->json([
                'message' => 'Este QR ya fue validado.',
                'data' => [
                    'registration_id' => $registration->id,
                    'qr_code' => $registration->qr_code,
                    'already_scanned' => true,
                    'scanned_at' => $existingLog->scanned_at,
                ],
            ]);
        }

        $attendanceLog = AttendanceLog::create([
            'registration_id' => $registration->id,
            'scanned_by' => $data['scanned_by'] ?? null,
            'scanned_at' => now(),
            'note' => $data['note'] ?? null,
        ]);

        return response()->json([
            'message' => 'QR validado correctamente.',
            'data' => [
                'registration_id' => $registration->id,
                'qr_code' => $registration->qr_code,
                'already_scanned' => false,
                'scanned_at' => $attendanceLog->scanned_at,
            ],
        ], 201);
    }

    private function resolveEvent(?int $eventId): Event
    {
        if ($eventId !== null) {
            return Event::findOrFail($eventId);
        }

        $event = Event::query()->orderBy('id')->first();

        if ($event !== null) {
            return $event;
        }

        return Event::create([
            'title' => 'Quinceañera',
            'description' => 'Evento principal de la celebración.',
            'starts_at' => now()->setDate(2026, 7, 4)->setTime(19, 0),
            'capacity' => 100,
            'max_guests_per_registration' => 4,
        ]);
    }

    private function makeQrSvg(string $qrCode): string
    {
        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle(320, 8),
                new SvgImageBackEnd()
            )
        );

        return $writer->writeString($qrCode);
    }

    private function findRegistrationByQrCode(string $qrCode): Registration
    {
        return Registration::where('qr_code', $qrCode)->firstOrFail();
    }
}
