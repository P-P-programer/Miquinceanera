<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Dashboard Admin | {{ config('app.name', 'Laravel') }}</title>
        @fonts
        @vite(['resources/css/app.css'])
    </head>
    <body>
        <div class="min-h-screen bg-slate-950 px-6 py-8 text-slate-100">
            <div class="mx-auto max-w-7xl space-y-6">
                <header class="flex flex-col gap-4 rounded-[2rem] border border-white/10 bg-white/5 p-6 backdrop-blur-xl lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-[11px] uppercase tracking-[0.4em] text-slate-400">Panel admin</p>
                        <h1 class="mt-2 text-3xl font-semibold text-white">Control de invitaciones y asistencia</h1>
                        <p class="mt-2 text-sm text-slate-300">Evento: {{ $event?->title ?? 'Quinceañera' }}</p>
                    </div>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm font-medium text-white transition hover:bg-white/10">Cerrar sesión</button>
                    </form>
                </header>

                <section class="grid gap-4 md:grid-cols-4">
                    <div class="rounded-[1.5rem] border border-white/10 bg-white/5 p-5">
                        <p class="text-sm text-slate-400">Registros</p>
                        <p class="mt-2 text-3xl font-semibold text-white">{{ $stats['registrations'] }}</p>
                    </div>
                    <div class="rounded-[1.5rem] border border-white/10 bg-white/5 p-5">
                        <p class="text-sm text-slate-400">Cupos ocupados</p>
                        <p class="mt-2 text-3xl font-semibold text-white">{{ $stats['occupiedSeats'] }}</p>
                    </div>
                    <div class="rounded-[1.5rem] border border-white/10 bg-white/5 p-5">
                        <p class="text-sm text-slate-400">Cupos libres</p>
                        <p class="mt-2 text-3xl font-semibold text-white">{{ $stats['availableSeats'] }}</p>
                    </div>
                    <div class="rounded-[1.5rem] border border-white/10 bg-white/5 p-5">
                        <p class="text-sm text-slate-400">Escaneos</p>
                        <p class="mt-2 text-3xl font-semibold text-white">{{ $stats['attendanceLogs'] }}</p>
                    </div>
                </section>

                <section class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
                    <div class="rounded-[2rem] border border-white/10 bg-white/5 p-6 backdrop-blur-xl">
                        <p class="text-[11px] uppercase tracking-[0.35em] text-slate-400">Validar QR</p>
                        <h2 class="mt-2 text-2xl font-semibold text-white">Escanear y marcar asistencia</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-300">Pega el token del QR o usa el lector físico para llenarlo y marcar la entrada.</p>

                        <form id="scan-form" class="mt-5 space-y-3">
                            <input id="qr-code" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-300/40" placeholder="Token del QR" autocomplete="off">
                            <input id="scanned-by" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-300/40" placeholder="Validado por (opcional)" autocomplete="off">
                            <input id="scan-note" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-300/40" placeholder="Nota (opcional)" autocomplete="off">
                            <button type="submit" class="w-full rounded-2xl bg-gradient-to-r from-slate-100 to-slate-300 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:opacity-90">Marcar asistencia</button>
                        </form>

                        <div id="scan-result" class="mt-4 rounded-[1.5rem] border border-white/10 bg-slate-950/60 p-4 text-sm text-slate-300">Aquí aparecerá el resultado del escaneo.</div>
                    </div>

                    <div class="rounded-[2rem] border border-white/10 bg-white/5 p-6 backdrop-blur-xl">
                        <p class="text-[11px] uppercase tracking-[0.35em] text-slate-400">Escaneos recientes</p>
                        <div class="mt-4 space-y-3">
                            @forelse ($recentScans as $log)
                                <article class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="font-semibold text-white">{{ $log->registration?->titular_name ?? 'Registro eliminado' }}</p>
                                            <p class="mt-1 text-sm text-slate-400">QR: {{ $log->registration?->qr_code ?? 'N/A' }}</p>
                                        </div>
                                        <p class="text-xs uppercase tracking-[0.3em] text-slate-500">{{ $log->scanned_at?->format('d/m H:i') }}</p>
                                    </div>
                                </article>
                            @empty
                                <p class="text-sm text-slate-400">Aún no hay escaneos registrados.</p>
                            @endforelse
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <script>
            const scanForm = document.getElementById('scan-form');
            const qrCodeInput = document.getElementById('qr-code');
            const scannedByInput = document.getElementById('scanned-by');
            const scanNoteInput = document.getElementById('scan-note');
            const scanResult = document.getElementById('scan-result');

            scanForm.addEventListener('submit', async (event) => {
                event.preventDefault();

                const qrCode = qrCodeInput.value.trim();

                if (!qrCode) {
                    scanResult.textContent = 'Debes escribir o escanear un QR primero.';
                    return;
                }

                scanResult.textContent = 'Validando QR...';

                try {
                    const response = await fetch(`/api/registrations/${encodeURIComponent(qrCode)}/scan`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                        },
                        body: JSON.stringify({
                            scanned_by: scannedByInput.value.trim() || null,
                            note: scanNoteInput.value.trim() || null,
                        }),
                    });

                    const payload = await response.json();

                    if (!response.ok) {
                        throw new Error(payload.message || 'No se pudo validar el QR.');
                    }

                    scanResult.textContent = `${payload.message} Titular: ${payload.data.qr_code}.`;
                    qrCodeInput.value = '';
                    scanNoteInput.value = '';
                } catch (error) {
                    scanResult.textContent = error instanceof Error ? error.message : 'No se pudo validar el QR.';
                }
            });
        </script>
    </body>
</html>
