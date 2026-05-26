import React, { useEffect, useMemo, useState } from 'react';

const eventDate = new Date('2026-07-04T19:00:00-05:00');
const totalCapacity = 100;

const statsSeed = {
    registeredGroups: 18,
    occupiedSeats: 54,
    confirmedGuests: 38,
};

function formatNumber(value) {
    return String(value).padStart(2, '0');
}

function getCountdown(targetDate) {
    const now = new Date();
    const total = Math.max(targetDate.getTime() - now.getTime(), 0);

    return {
        days: Math.floor(total / (1000 * 60 * 60 * 24)),
        hours: Math.floor((total / (1000 * 60 * 60)) % 24),
        minutes: Math.floor((total / (1000 * 60)) % 60),
        seconds: Math.floor((total / 1000) % 60),
    };
}

export default function EventApp() {
    const [countdown, setCountdown] = useState(() => getCountdown(eventDate));
    const [formState, setFormState] = useState({
        titular_name: '',
        titular_email: '',
        titular_phone: '',
        guests: ['', '', '', ''],
    });
    const [submissionState, setSubmissionState] = useState({
        status: 'idle',
        message: '',
        registration: null,
    });

    useEffect(() => {
        const timer = window.setInterval(() => {
            setCountdown(getCountdown(eventDate));
        }, 1000);

        return () => window.clearInterval(timer);
    }, []);

    const availableSeats = useMemo(
        () => Math.max(totalCapacity - statsSeed.occupiedSeats, 0),
        [],
    );

    const metrics = [
        { label: 'Cupos restantes', value: availableSeats },
        { label: 'Grupos registrados', value: statsSeed.registeredGroups },
        { label: 'Invitados confirmados', value: statsSeed.confirmedGuests },
    ];

    const hasRegistration = submissionState.registration !== null;

    function updateField(field, value) {
        setFormState((current) => ({
            ...current,
            [field]: value,
        }));
    }

    function updateGuest(index, value) {
        setFormState((current) => {
            const guests = [...current.guests];
            guests[index] = value;

            return {
                ...current,
                guests,
            };
        });
    }

    async function handleSubmit(event) {
        event.preventDefault();

        setSubmissionState({
            status: 'loading',
            message: 'Procesando registro...',
            registration: null,
        });

        const guests = formState.guests
            .map((name) => name.trim())
            .filter(Boolean)
            .map((name) => ({ name }));

        try {
            const response = await fetch('/api/registrations', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    titular_name: formState.titular_name,
                    titular_email: formState.titular_email || null,
                    titular_phone: formState.titular_phone || null,
                    guests,
                }),
            });

            const payload = await response.json();

            if (!response.ok) {
                const firstError = Object.values(payload.errors || {})[0]?.[0] || 'No se pudo completar el registro.';
                throw new Error(firstError);
            }

            setSubmissionState({
                status: 'success',
                message: payload.message,
                registration: payload.data,
            });
        } catch (error) {
            setSubmissionState({
                status: 'error',
                message: error instanceof Error ? error.message : 'Ocurrió un error inesperado.',
                registration: null,
            });
        }
    }

    const privateSections = [
        'Detalles del evento',
        'Horario de la fiesta',
        'Código de vestimenta',
        'Ubicación con mapa',
        'Álbum colaborativo',
        'Recomendación de canciones',
    ];

    return (
        <div className="min-h-screen text-slate-100">
            <div className="mx-auto flex min-h-screen w-full max-w-7xl flex-col gap-8 px-6 py-6 lg:px-10">
                <header className="flex items-center justify-between rounded-full border border-white/10 bg-white/5 px-5 py-3 backdrop-blur-xl">
                    <div>
                        <p className="text-[11px] uppercase tracking-[0.4em] text-slate-400">Miquinceañera</p>
                        <h1 className="text-lg font-semibold text-white">Invitación digital</h1>
                    </div>
                    <div className="rounded-full border border-slate-200/10 bg-slate-950/60 px-4 py-2 text-right">
                        <p className="text-[11px] uppercase tracking-[0.3em] text-slate-400">Evento</p>
                        <p className="text-sm font-medium text-slate-100">4 de julio de 2026 · 7:00 PM</p>
                    </div>
                </header>

                <main className="grid flex-1 gap-6 xl:grid-cols-[1.45fr_0.95fr]">
                    <section className="relative overflow-hidden rounded-[2rem] border border-white/10 bg-[linear-gradient(135deg,rgba(2,6,23,0.96),rgba(15,23,42,0.9)_42%,rgba(148,163,184,0.12))] p-8 shadow-2xl shadow-slate-950/50">
                        <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(226,232,240,0.16),transparent_35%),radial-gradient(circle_at_bottom_left,rgba(125,211,252,0.12),transparent_30%)]" />
                        <div className="relative grid gap-10 lg:grid-cols-[1.1fr_0.9fr]">
                            <div className="space-y-6">
                                <span className="inline-flex items-center rounded-full border border-cyan-200/20 bg-cyan-300/10 px-4 py-2 text-xs uppercase tracking-[0.3em] text-cyan-200">
                                    New York night mood
                                </span>
                                <div className="space-y-4">
                                    <p className="text-sm uppercase tracking-[0.4em] text-slate-400">Cuenta regresiva</p>
                                    <div className="max-w-2xl">
                                        <h2 className="text-4xl font-semibold leading-tight text-white lg:text-6xl">
                                            Una noche elegante para celebrar tus quince años.
                                        </h2>
                                    </div>
                                    <p className="max-w-xl text-base leading-7 text-slate-300 lg:text-lg">
                                        Registro por grupo, QR único, acceso privado a los detalles del evento y control total de cupos para la fiesta.
                                    </p>
                                </div>

                                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    {Object.entries(countdown).map(([label, value]) => (
                                        <div key={label} className="rounded-2xl border border-white/10 bg-white/5 px-4 py-5 text-center backdrop-blur-md">
                                            <div className="text-3xl font-semibold text-white">{formatNumber(value)}</div>
                                            <div className="mt-1 text-xs uppercase tracking-[0.3em] text-slate-400">{label}</div>
                                        </div>
                                    ))}
                                </div>

                                <div className="grid gap-3 sm:grid-cols-3">
                                    {metrics.map((item) => (
                                        <div key={item.label} className="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                                            <p className="text-sm text-slate-400">{item.label}</p>
                                            <p className="mt-2 text-2xl font-semibold text-white">{item.value}</p>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="space-y-4">
                                <div className="rounded-[1.75rem] border border-white/10 bg-slate-950/70 p-5 backdrop-blur-xl">
                                    <p className="text-[11px] uppercase tracking-[0.35em] text-slate-400">Estado del acceso</p>
                                    <div className="mt-3 flex items-end justify-between gap-4">
                                        <div>
                                            <p className="text-3xl font-semibold text-white">{availableSeats}</p>
                                            <p className="text-sm text-slate-400">cupos disponibles de {totalCapacity}</p>
                                        </div>
                                        <span className="rounded-full border border-emerald-300/20 bg-emerald-300/10 px-3 py-1 text-xs font-medium text-emerald-200">
                                            Registro abierto
                                        </span>
                                    </div>
                                    <div className="mt-5 h-2 overflow-hidden rounded-full bg-white/10">
                                        <div
                                            className="h-full rounded-full bg-gradient-to-r from-slate-200 via-cyan-200 to-slate-400"
                                            style={{ width: `${Math.max((statsSeed.occupiedSeats / totalCapacity) * 100, 8)}%` }}
                                        />
                                    </div>
                                </div>

                                <div className="rounded-[1.75rem] border border-white/10 bg-white/5 p-5 backdrop-blur-xl">
                                    <p className="text-[11px] uppercase tracking-[0.35em] text-slate-400">Lo privado</p>
                                    <p className="mt-3 text-lg font-medium text-white">Se desbloquea después del registro o con tu código de acceso.</p>
                                    <div className="mt-4 grid gap-2">
                                        {privateSections.map((item) => (
                                            <div key={item} className="flex items-center justify-between rounded-2xl border border-white/8 bg-slate-950/55 px-4 py-3 text-sm text-slate-300">
                                                <span>{item}</span>
                                                <span className="text-slate-500">Bloqueado</span>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <aside className="space-y-6 rounded-[2rem] border border-white/10 bg-slate-950/70 p-6 shadow-2xl shadow-slate-950/50 backdrop-blur-xl">
                        <div>
                            <p className="text-[11px] uppercase tracking-[0.35em] text-slate-400">Registro</p>
                            <h3 className="mt-2 text-2xl font-semibold text-white">Titular + hasta 4 acompañantes</h3>
                            <p className="mt-3 text-sm leading-6 text-slate-300">
                                El sistema guardará cada persona por separado para validar nombres exactos, cupos y asistencia el día del evento.
                            </p>
                        </div>

                        <form className="space-y-4" onSubmit={handleSubmit}>
                            <input
                                className="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 outline-none transition focus:border-cyan-300/40"
                                placeholder="Nombre completo del titular"
                                value={formState.titular_name}
                                onChange={(event) => updateField('titular_name', event.target.value)}
                                required
                            />
                            <input
                                type="email"
                                className="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 outline-none transition focus:border-cyan-300/40"
                                placeholder="Correo del titular"
                                value={formState.titular_email}
                                onChange={(event) => updateField('titular_email', event.target.value)}
                            />
                            <input
                                className="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 outline-none transition focus:border-cyan-300/40"
                                placeholder="Teléfono del titular"
                                value={formState.titular_phone}
                                onChange={(event) => updateField('titular_phone', event.target.value)}
                            />
                            <div className="grid gap-3 sm:grid-cols-2">
                                {formState.guests.map((guest, index) => (
                                    <input
                                        key={`guest-${index}`}
                                        className="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 outline-none transition focus:border-cyan-300/40"
                                        placeholder={`Acompañante ${index + 1}`}
                                        value={guest}
                                        onChange={(event) => updateGuest(index, event.target.value)}
                                    />
                                ))}
                            </div>
                            <button
                                type="submit"
                                className="w-full rounded-2xl bg-gradient-to-r from-slate-100 to-slate-300 px-4 py-3 font-semibold text-slate-950 transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                                disabled={submissionState.status === 'loading'}
                            >
                                {submissionState.status === 'loading' ? 'Registrando...' : 'Confirmar registro'}
                            </button>
                        </form>

                        {submissionState.message ? (
                            <div
                                className={`rounded-[1.5rem] border p-4 text-sm ${
                                    submissionState.status === 'success'
                                        ? 'border-emerald-300/20 bg-emerald-300/10 text-emerald-100'
                                        : submissionState.status === 'error'
                                            ? 'border-rose-300/20 bg-rose-300/10 text-rose-100'
                                            : 'border-cyan-300/20 bg-cyan-300/10 text-cyan-100'
                                }`}
                            >
                                {submissionState.message}
                            </div>
                        ) : null}

                        {hasRegistration ? (
                            <div className="rounded-[1.5rem] border border-white/10 bg-slate-950/60 p-5">
                                <p className="text-[11px] uppercase tracking-[0.35em] text-slate-400">Tu QR</p>
                                <p className="mt-2 text-sm text-slate-300">
                                    Guarda o descarga este código para mostrarlo en la entrada.
                                </p>
                                <div className="mt-4 overflow-hidden rounded-3xl border border-white/10 bg-white p-4">
                                    <img
                                        alt="Código QR de registro"
                                        className="mx-auto h-64 w-64"
                                        src={submissionState.registration.qr_image_url}
                                    />
                                </div>
                                <div className="mt-4 flex flex-col gap-3 sm:flex-row">
                                    <a
                                        className="inline-flex flex-1 items-center justify-center rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-medium text-white transition hover:bg-white/10"
                                        href={submissionState.registration.qr_image_url}
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        Ver QR
                                    </a>
                                    <a
                                        className="inline-flex flex-1 items-center justify-center rounded-2xl bg-gradient-to-r from-slate-100 to-slate-300 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:opacity-90"
                                        href={submissionState.registration.qr_download_url}
                                    >
                                        Descargar QR
                                    </a>
                                </div>
                                <p className="mt-4 text-xs uppercase tracking-[0.3em] text-slate-500">
                                    Código: {submissionState.registration.qr_value}
                                </p>
                            </div>
                        ) : null}

                        <div className="rounded-[1.5rem] border border-cyan-200/10 bg-cyan-300/5 p-5">
                            <p className="text-sm font-medium text-cyan-100">Siguiente paso</p>
                            <p className="mt-2 text-sm leading-6 text-slate-300">
                                Cuando el registro exista, el invitado verá los detalles del evento, podrá regresar con su código y se desbloqueará el panel de recomendaciones y álbum.
                            </p>
                        </div>
                    </aside>
                </main>
            </div>
        </div>
    );
}
