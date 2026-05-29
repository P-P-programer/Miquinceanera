<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Dashboard Admin | {{ config('app.name', 'Laravel') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/css/admin.css', 'resources/js/admin/dashboard.js'])
    </head>
    <body>
        <div class="admin-page px-6 py-8">
            <div class="admin-shell space-y-6">
                @if (session('status'))
                    <div class="admin-card border-emerald-300/20 bg-emerald-300/10 px-5 py-4 text-sm text-emerald-100">
                        {{ session('status') }}
                    </div>
                @endif

                <header class="admin-panel flex flex-col gap-4 p-6 lg:flex-row lg:items-center lg:justify-between">
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
                    <div class="admin-card p-5">
                        <p class="text-sm text-slate-400">Registros</p>
                        <p class="mt-2 text-3xl font-semibold text-white">{{ $stats['registrations'] }}</p>
                    </div>
                    <div class="admin-card p-5">
                        <p class="text-sm text-slate-400">Cupos ocupados</p>
                        <p class="mt-2 text-3xl font-semibold text-white">{{ $stats['occupiedSeats'] }}</p>
                    </div>
                    <div class="admin-card p-5">
                        <p class="text-sm text-slate-400">Cupos libres</p>
                        <p class="mt-2 text-3xl font-semibold text-white">{{ $stats['availableSeats'] }}</p>
                    </div>
                    <div class="admin-card p-5">
                        <p class="text-sm text-slate-400">Escaneos</p>
                        <p class="mt-2 text-3xl font-semibold text-white">{{ $stats['attendanceLogs'] }}</p>
                    </div>
                </section>

                <section class="admin-responsive-grid">
                    <div class="admin-panel space-y-6 p-6">
                        <div>
                            <p class="text-[11px] uppercase tracking-[0.35em] text-slate-400">Validar QR</p>
                            <h2 class="mt-2 text-2xl font-semibold text-white">Escanear y marcar asistencia</h2>
                            <p class="mt-3 text-sm leading-6 text-slate-300">La cámara necesita permiso del dispositivo. Si no hay soporte, usa el token manual o el código humano.</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 rounded-[1.5rem] border border-white/10 bg-slate-950/60 p-3 text-sm">
                            <label class="admin-chip-button flex cursor-pointer items-center justify-center gap-2 rounded-2xl px-4 py-3 transition" data-scan-mode="qr">
                                <input type="radio" name="scan-mode" value="qr" checked class="accent-cyan-300">
                                <span>Escanear QR</span>
                            </label>
                            <label class="admin-chip-button flex cursor-pointer items-center justify-center gap-2 rounded-2xl px-4 py-3 transition" data-scan-mode="human">
                                <input type="radio" name="scan-mode" value="human" class="accent-cyan-300">
                                <span>Código humano</span>
                            </label>
                        </div>

                        <div id="qr-mode-panel" class="space-y-4">
                            <div class="admin-video-shell">
                                <video id="qr-video" class="admin-video" playsinline muted></video>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <button id="qr-start" type="button" class="admin-chip-button rounded-2xl bg-gradient-to-r from-slate-100 to-slate-300 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:opacity-90">Activar cámara</button>
                                <button id="qr-stop" type="button" class="admin-chip-button rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm font-medium text-white transition hover:bg-white/10">Detener cámara</button>
                            </div>
                            <p class="text-xs uppercase tracking-[0.3em] text-slate-500">La cámara pide permiso al dispositivo cuando la activas.</p>
                        </div>

                        <div id="human-mode-panel" class="hidden space-y-4 rounded-[1.5rem] border border-white/10 bg-slate-950/60 p-4">
                            <div>
                                <p class="text-[11px] uppercase tracking-[0.35em] text-slate-400">Lookup manual</p>
                                <p class="mt-2 text-sm text-slate-300">Usa este código para recuperar el registro sin escanear el QR.</p>
                            </div>
                            <form id="human-code-form" class="space-y-3">
                                <input id="human-code-input" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-300/40" placeholder="Ej: K7M4-Q2P9" autocomplete="off">
                                <button type="submit" class="admin-chip-button w-full rounded-2xl bg-cyan-300/15 px-4 py-3 text-sm font-semibold text-cyan-100 transition hover:bg-cyan-300/20">Buscar registro</button>
                            </form>
                        </div>

                        <form id="scan-form" class="space-y-3 rounded-[1.5rem] border border-white/10 bg-slate-950/60 p-4">
                            <input id="qr-code" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-300/40" placeholder="Token del QR" autocomplete="off">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <input id="scanned-by" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-300/40" placeholder="Validado por (opcional)" autocomplete="off">
                                <input id="scan-note" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-300/40" placeholder="Nota (opcional)" autocomplete="off">
                            </div>
                            <button type="submit" class="admin-chip-button w-full rounded-2xl bg-gradient-to-r from-slate-100 to-slate-300 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:opacity-90">Marcar asistencia</button>
                        </form>

                        <div id="scan-preview" class="rounded-[1.5rem] border border-white/10 bg-slate-950/60 p-4 text-sm text-slate-300">Escanea un QR para ver el titular e integrantes antes de marcar asistencia.</div>
                        <div id="scan-result" class="rounded-[1.5rem] border border-white/10 bg-slate-950/60 p-4 text-sm text-slate-300">Aquí aparecerá el resultado del escaneo.</div>
                    </div>

                    <div class="admin-panel space-y-6 p-6">
                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <div class="rounded-[1.5rem] border border-white/10 bg-slate-950/60 p-4">
                                <p class="text-sm text-slate-400">Confirmados</p>
                                <p class="mt-2 text-2xl font-semibold text-white">{{ $registrations->where('status', 'confirmed')->count() }}</p>
                            </div>
                            <div class="rounded-[1.5rem] border border-white/10 bg-slate-950/60 p-4">
                                <p class="text-sm text-slate-400">Pendientes</p>
                                <p class="mt-2 text-2xl font-semibold text-white">{{ $registrations->where('status', 'pending')->count() }}</p>
                            </div>
                            <div class="rounded-[1.5rem] border border-white/10 bg-slate-950/60 p-4">
                                <p class="text-sm text-slate-400">Cancelados</p>
                                <p class="mt-2 text-2xl font-semibold text-white">{{ $registrations->where('status', 'cancelled')->count() }}</p>
                            </div>
                            <div class="rounded-[1.5rem] border border-white/10 bg-slate-950/60 p-4">
                                <p class="text-sm text-slate-400">Con asistencia</p>
                                <p class="mt-2 text-2xl font-semibold text-white">{{ $registrations->where('attendance_logs_count', '>', 0)->count() }}</p>
                            </div>
                        </div>

                        <div class="admin-card p-4">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                                <div>
                                    <p class="text-[11px] uppercase tracking-[0.35em] text-slate-400">Buscador dinámico</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-white">Tabla de registros</h2>
                                    <p class="mt-2 text-sm text-slate-300">Escribe al menos 3 letras para que la búsqueda empiece a filtrar en vivo.</p>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2 lg:min-w-[26rem]">
                                    <input id="registration-search" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-300/40" placeholder="Buscar titular, acompañantes o código" autocomplete="off">
                                    <select id="registration-status" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-300/40">
                                        <option value="confirmed" selected>Confirmados</option>
                                        <option value="all">Todos</option>
                                        <option value="pending">Pendientes</option>
                                        <option value="cancelled">Cancelados</option>
                                    </select>
                                </div>
                            </div>

                            <div id="search-hint" class="mt-3 hidden rounded-2xl border border-amber-300/20 bg-amber-300/10 px-4 py-3 text-sm text-amber-100">
                                Escribe por lo menos 3 letras para activar el filtro por texto.
                            </div>

                            <div class="admin-table-wrap mt-4">
                                <table class="min-w-full divide-y divide-white/10 text-left text-sm">
                                    <thead class="text-xs uppercase tracking-[0.25em] text-slate-400">
                                        <tr>
                                            <th class="px-4 py-3">Estado</th>
                                            <th class="px-4 py-3">Titular y acompañantes</th>
                                            <th class="px-4 py-3">Código humano</th>
                                            <th class="admin-hide-mobile px-4 py-3">Fecha de registro</th>
                                            <th class="admin-hide-mobile px-4 py-3">Asistencia</th>
                                            <th class="px-4 py-3">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="registration-table-body" class="divide-y divide-white/10">
                                        @forelse ($registrations as $registration)
                                            <tr data-status="{{ $registration['status'] }}" data-search="{{ $registration['search_text'] }}" class="align-top">
                                                <td class="px-4 py-4">
                                                    <span class="inline-flex rounded-full border px-3 py-1 text-xs font-medium uppercase tracking-[0.3em] {{ $registration['status'] === 'confirmed' ? 'border-emerald-300/20 bg-emerald-300/10 text-emerald-100' : ($registration['status'] === 'pending' ? 'border-amber-300/20 bg-amber-300/10 text-amber-100' : 'border-rose-300/20 bg-rose-300/10 text-rose-100') }}">{{ $registration['status'] }}</span>
                                                </td>
                                                <td class="px-4 py-4">
                                                    <div class="space-y-2">
                                                        <p class="font-semibold text-white">{{ $registration['titular_name'] }}</p>
                                                        <div class="space-y-1 text-sm text-slate-300">
                                                            @foreach ($registration['people'] as $person)
                                                                @if (! $person['is_titular'])
                                                                    <p class="flex items-center gap-2"><span class="text-slate-500">•</span>{{ $person['name'] }}</p>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-4">
                                                    <div class="space-y-2">
                                                        <p class="text-base font-semibold tracking-[0.2em] text-white">{{ $registration['access_code'] }}</p>
                                                        <button type="button" class="copy-access-code rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-medium text-white transition hover:bg-white/10" data-access-code="{{ $registration['access_code'] }}">Copiar código</button>
                                                    </div>
                                                </td>
                                                <td class="admin-hide-mobile px-4 py-4 text-slate-300">{{ $registration['created_at'] }}</td>
                                                <td class="admin-hide-mobile px-4 py-4">
                                                    <div class="space-y-2">
                                                        <p class="text-sm text-slate-300">Personas: {{ $registration['total_people'] }}</p>
                                                        <p class="text-sm text-slate-400">Escaneos: {{ $registration['attendance_logs_count'] }}</p>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-4">
                                                    <div class="flex flex-wrap gap-2">
                                                        <a class="admin-action-button" href="{{ $registration['qr_image_url'] }}" target="_blank" rel="noreferrer">Ver QR</a>
                                                        <a class="admin-action-button" href="{{ $registration['qr_download_url'] }}">Descargar QR</a>
                                                        @if ($registration['status'] !== 'cancelled')
                                                            <form method="POST" action="{{ route('admin.registrations.cancel', $registration['id']) }}" class="cancel-registration-form w-full" data-confirm-message="¿Cancelar el registro de {{ $registration['titular_name'] }}? Esta acción no se puede deshacer, pero liberará el cupo y conservará el historial.">
                                                                @csrf
                                                                <button type="submit" class="admin-action-button w-full border border-rose-300/20 bg-rose-300/10 text-rose-100 transition hover:bg-rose-300/20">Cancelar registro</button>
                                                            </form>
                                                        @else
                                                            <span class="admin-action-button w-full border border-white/10 bg-white/5 text-slate-400">Cancelado</span>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="px-4 py-8 text-center text-slate-400">Aún no hay registros.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            <div class="admin-card p-4">
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

                            <div class="admin-card p-4">
                                <p class="text-[11px] uppercase tracking-[0.35em] text-slate-400">Resultado del lookup</p>
                                <div id="lookup-result" class="mt-4 rounded-2xl border border-white/10 bg-white/5 p-4 text-sm text-slate-300">Cuando busques por código humano, aquí se mostrará el registro.</div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div id="admin-toast-stack" class="admin-toast-stack pointer-events-none"></div>

        <div id="attendance-confirm-backdrop" class="admin-modal-backdrop hidden" aria-hidden="true">
            <div class="admin-modal-panel p-6 sm:p-8" role="dialog" aria-modal="true" aria-labelledby="attendance-confirm-title">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <span class="admin-modal-pill">Confirmar asistencia</span>
                        <h2 id="attendance-confirm-title" class="mt-4 text-3xl font-semibold text-white">Revisar integrantes antes de marcar</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-300">Confirma que el titular y sus acompañantes sí ingresaron. Este paso no libera cupo.</p>
                    </div>
                    <button id="attendance-confirm-close" type="button" class="rounded-full border border-white/10 bg-white/5 px-3 py-2 text-sm text-slate-300 transition hover:bg-white/10">Cerrar</button>
                </div>

                <div class="mt-6 rounded-[1.5rem] border border-white/10 bg-slate-950/70 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-[0.35em] text-slate-400">Titular</p>
                            <p id="attendance-confirm-holder" class="mt-2 text-2xl font-semibold text-white">—</p>
                        </div>
                        <div class="rounded-full border border-cyan-300/20 bg-cyan-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.3em] text-cyan-100">
                            <span id="attendance-confirm-status">Pendiente</span>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Código humano</p>
                            <p id="attendance-confirm-access-code" class="mt-2 text-lg font-semibold tracking-[0.2em] text-white">—</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Integrantes</p>
                            <p id="attendance-confirm-count" class="mt-2 text-lg font-semibold text-white">—</p>
                        </div>
                    </div>

                    <div class="mt-5">
                        <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Lista</p>
                        <div id="attendance-confirm-members" class="admin-modal-member-list mt-3 text-sm text-slate-200"></div>
                    </div>
                </div>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button id="attendance-confirm-cancel" type="button" class="rounded-2xl border border-white/10 bg-white/5 px-5 py-3 text-sm font-medium text-white transition hover:bg-white/10">Volver</button>
                    <button id="attendance-confirm-submit" type="button" class="rounded-2xl bg-gradient-to-r from-cyan-300 to-sky-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:opacity-95">Sí, marcar asistencia</button>
                </div>
            </div>
        </div>

        <div id="cancel-confirm-backdrop" class="admin-modal-backdrop hidden" aria-hidden="true">
            <div class="admin-modal-panel admin-cancel-panel p-6 sm:p-8" role="dialog" aria-modal="true" aria-labelledby="cancel-confirm-title">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <span class="admin-modal-pill admin-cancel-pill">Cancelar registro</span>
                        <h2 id="cancel-confirm-title" class="mt-4 text-3xl font-semibold text-white">¿Seguro que deseas continuar?</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-300">Esta acción conserva el historial, pero libera el cupo y cambia el estado del registro.</p>
                    </div>
                    <button id="cancel-confirm-close" type="button" class="rounded-full border border-white/10 bg-white/5 px-3 py-2 text-sm text-slate-300 transition hover:bg-white/10">Cerrar</button>
                </div>

                <div class="mt-6 rounded-[1.5rem] border border-rose-300/20 bg-rose-300/10 p-5">
                    <p class="text-xs uppercase tracking-[0.35em] text-rose-100/80">Atención</p>
                    <p id="cancel-confirm-message" class="mt-3 text-sm leading-6 text-rose-50">—</p>
                </div>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button id="cancel-confirm-back" type="button" class="rounded-2xl border border-white/10 bg-white/5 px-5 py-3 text-sm font-medium text-white transition hover:bg-white/10">Volver</button>
                    <button id="cancel-confirm-submit" type="button" class="rounded-2xl bg-gradient-to-r from-rose-300 to-rose-500 px-5 py-3 text-sm font-semibold text-rose-950 transition hover:opacity-95">Sí, cancelar registro</button>
                </div>
            </div>
        </div>

    </body>
</html>
