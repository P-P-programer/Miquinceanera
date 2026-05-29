import React, { useEffect, useState } from 'react';
import ConfirmDialog from './ui/ConfirmDialog';
import { NotificationProvider, useNotifications } from './ui/NotificationSystem';

const eventDate = new Date('2026-07-04T19:00:00-05:00');
const totalCapacity = 100;
const storageKey = 'miquinceanera-registration';

const initialStats = {
    eventTitle: 'Quinceañera',
    eventStartsAt: eventDate.toISOString(),
    totalCapacity: totalCapacity,
    availableSeats: totalCapacity,
    registeredGroups: 0,
    confirmedGroups: 0,
    confirmedGuests: 0,
    attendedGroups: 0,
    attendedGuests: 0,
    registrationOpen: true,
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

function buildGuestRows() {
    return ['', '', '', ''];
}

function normalizeRegistration(payload) {
    return payload?.data ?? payload;
}

async function downloadSvgAsPng(svgUrl, fileName) {
    const response = await fetch(svgUrl, {
        headers: {
            Accept: 'image/svg+xml',
        },
    });

    if (!response.ok) {
        throw new Error('No pudimos generar la imagen del QR.');
    }

    const svgText = await response.text();
    const svgBlob = new Blob([svgText], { type: 'image/svg+xml;charset=utf-8' });
    const objectUrl = URL.createObjectURL(svgBlob);

    try {
        const image = new Image();
        image.decoding = 'async';

        const loaded = new Promise((resolve, reject) => {
            image.onload = resolve;
            image.onerror = reject;
        });

        image.src = objectUrl;
        await loaded;

        const size = Math.max(image.width, image.height, 320);
        const canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = size;

        const context = canvas.getContext('2d');
        if (!context) {
            throw new Error('No pudimos preparar el lienzo de descarga.');
        }

        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, size, size);
        context.drawImage(image, 0, 0, size, size);

        const pngBlob = await new Promise((resolve, reject) => {
            canvas.toBlob((blob) => {
                if (!blob) {
                    reject(new Error('No pudimos convertir el QR a PNG.'));
                    return;
                }

                resolve(blob);
            }, 'image/png');
        });

        const downloadUrl = URL.createObjectURL(pngBlob);
        const anchor = document.createElement('a');
        anchor.href = downloadUrl;
        anchor.download = fileName;
        document.body.appendChild(anchor);
        anchor.click();
        anchor.remove();
        URL.revokeObjectURL(downloadUrl);
    } finally {
        URL.revokeObjectURL(objectUrl);
    }
}

export default function EventApp() {
    return (
        <NotificationProvider>
            <EventAppContent />
        </NotificationProvider>
    );
}

function EventAppContent() {
    const [countdown, setCountdown] = useState(() => getCountdown(eventDate));
    const [formState, setFormState] = useState({
        titular_name: '',
        titular_email: '',
        titular_phone: '',
        guests: buildGuestRows(),
    });
    const [submissionState, setSubmissionState] = useState({
        status: 'idle',
        message: '',
        registration: null,
    });
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [restoringSession, setRestoringSession] = useState(true);
    const [manualAccessOpen, setManualAccessOpen] = useState(false);
    const [accessCodeInput, setAccessCodeInput] = useState('');
    const [eventStats, setEventStats] = useState(initialStats);
    const { pushNotification } = useNotifications();

    useEffect(() => {
        const stored = window.localStorage.getItem(storageKey);

        if (!stored) {
            setRestoringSession(false);
            return;
        }

        const restore = async () => {
            try {
                const parsed = JSON.parse(stored);

                if (!parsed?.access_code) {
                    window.localStorage.removeItem(storageKey);
                    return;
                }

                const response = await fetch(`/api/registrations/access/${parsed.access_code}`, {
                    headers: {
                        Accept: 'application/json',
                    },
                });

                if (!response.ok) {
                    window.localStorage.removeItem(storageKey);
                    return;
                }

                const payload = await response.json();

                setSubmissionState({
                    status: 'success',
                    message: 'Ya tienes un registro activo. Puedes volver a ver los detalles con tu código.',
                    registration: normalizeRegistration(payload),
                });
            } catch {
                window.localStorage.removeItem(storageKey);
            } finally {
                setRestoringSession(false);
            }
        };

        restore();
    }, []);

    useEffect(() => {
        const timer = window.setInterval(() => {
            setCountdown(getCountdown(eventDate));
        }, 1000);

        return () => window.clearInterval(timer);
    }, []);

    useEffect(() => {
        const controller = new AbortController();

        const loadStats = async () => {
            try {
                const response = await fetch('/api/event/stats', {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                });

                if (!response.ok) {
                    return;
                }

                const payload = await response.json();
                const data = payload?.data ?? {};

                setEventStats({
                    eventTitle: data.event_title ?? initialStats.eventTitle,
                    eventStartsAt: data.event_starts_at ?? initialStats.eventStartsAt,
                    totalCapacity: Number(data.total_capacity ?? initialStats.totalCapacity),
                    availableSeats: Number(data.available_seats ?? initialStats.availableSeats),
                    registeredGroups: Number(data.registered_groups ?? initialStats.registeredGroups),
                    confirmedGroups: Number(data.confirmed_groups ?? initialStats.confirmedGroups),
                    confirmedGuests: Number(data.confirmed_guests ?? initialStats.confirmedGuests),
                    attendedGroups: Number(data.attended_groups ?? initialStats.attendedGroups),
                    attendedGuests: Number(data.attended_guests ?? initialStats.attendedGuests),
                    registrationOpen: Boolean(data.registration_open ?? initialStats.registrationOpen),
                });
            } catch {
                // Keep the initial values if the stats endpoint is unavailable.
            }
        };

        loadStats();

        return () => controller.abort();
    }, []);

    const metrics = [
        { label: 'Cupos restantes', value: eventStats.availableSeats },
        { label: 'Grupos registrados', value: eventStats.registeredGroups },
        { label: 'Grupos confirmados', value: eventStats.confirmedGroups },
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

    function openConfirmation(event) {
        event.preventDefault();

        if (!eventStats.registrationOpen) {
            pushNotification({
                variant: 'warning',
                title: 'Registro cerrado',
                message: 'Ya no quedan cupos disponibles para nuevas inscripciones.',
            });

            return;
        }

        setConfirmOpen(true);
    }

    async function handleSubmit() {
        setConfirmOpen(false);

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

            window.localStorage.setItem(storageKey, JSON.stringify({
                access_code: payload.data.access_code,
            }));

            setFormState({
                titular_name: '',
                titular_email: '',
                titular_phone: '',
                guests: buildGuestRows(),
            });

            pushNotification({
                variant: 'success',
                title: 'Registro confirmado',
                message: 'El QR quedó listo y ya se puede descargar o mostrar en el acceso.',
            });
        } catch (error) {
            setSubmissionState({
                status: 'error',
                message: error instanceof Error ? error.message : 'Ocurrió un error inesperado.',
                registration: null,
            });

            pushNotification({
                variant: 'error',
                title: 'No se pudo registrar',
                message: error instanceof Error ? error.message : 'Ocurrió un error inesperado.',
            });
        }
    }

    async function handleAccessCodeSubmit(event) {
        event.preventDefault();

        const accessCode = accessCodeInput.trim();

        if (!accessCode) {
            pushNotification({
                variant: 'warning',
                title: 'Falta el código',
                message: 'Escribe tu código de acceso para continuar.',
            });

            return;
        }

        try {
            const response = await fetch(`/api/registrations/access/${accessCode}`, {
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('No encontramos ese código. Revisa que esté bien escrito.');
            }

            const payload = await response.json();
            const registration = normalizeRegistration(payload);

            setSubmissionState({
                status: 'success',
                message: 'Acceso verificado. Ya puedes ver los detalles de la celebración.',
                registration,
            });

            window.localStorage.setItem(storageKey, JSON.stringify({
                access_code: registration.access_code,
            }));

            setAccessCodeInput('');
            setManualAccessOpen(false);

            pushNotification({
                variant: 'success',
                title: 'Acceso confirmado',
                message: 'Ya cargamos tu registro y tus detalles.',
            });
        } catch (error) {
            pushNotification({
                variant: 'error',
                title: 'Código inválido',
                message: error instanceof Error ? error.message : 'No pudimos validar tu código.',
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
                        <p className="text-sm font-medium text-slate-100">{eventStats.eventTitle} · 4 de julio de 2026 · 7:00 PM</p>
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
                                            <p className="text-3xl font-semibold text-white">{eventStats.availableSeats}</p>
                                            <p className="text-sm text-slate-400">cupos disponibles de {eventStats.totalCapacity}</p>
                                        </div>
                                                <span className={`rounded-full px-3 py-1 text-xs font-medium ${eventStats.registrationOpen ? 'border border-emerald-300/20 bg-emerald-300/10 text-emerald-200' : 'border border-rose-300/20 bg-rose-300/10 text-rose-200'}`}>
                                                    {eventStats.registrationOpen ? 'Registro abierto' : 'Registro cerrado'}
                                                </span>
                                    </div>
                                    <div className="mt-5 h-2 overflow-hidden rounded-full bg-white/10">
                                        <div
                                            className="h-full rounded-full bg-gradient-to-r from-slate-200 via-cyan-200 to-slate-400"
                                            style={{ width: `${Math.max(eventStats.totalCapacity > 0 ? ((eventStats.totalCapacity - eventStats.availableSeats) / eventStats.totalCapacity) * 100 : 0, 8)}%` }}
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

                        {!restoringSession && !hasRegistration ? (
                            <form className="space-y-4" onSubmit={openConfirmation}>
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
                                disabled={submissionState.status === 'loading' || !eventStats.registrationOpen}
                            >
                                {submissionState.status === 'loading' ? 'Registrando...' : eventStats.registrationOpen ? 'Confirmar registro' : 'Registro cerrado'}
                            </button>
                            </form>
                        ) : null}

                        {!restoringSession && !hasRegistration ? (
                            <div className="space-y-3 rounded-[1.5rem] border border-white/10 bg-white/5 p-4">
                                <button
                                    type="button"
                                    className="w-full rounded-2xl border border-white/10 bg-slate-950/55 px-4 py-3 text-sm font-medium text-white transition hover:bg-white/10"
                                    onClick={() => setManualAccessOpen((current) => !current)}
                                >
                                    Ya tengo mi código de acceso
                                </button>

                                {manualAccessOpen ? (
                                    <form className="space-y-3" onSubmit={handleAccessCodeSubmit}>
                                        <input
                                            className="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white placeholder:text-slate-500 outline-none transition focus:border-cyan-300/40"
                                            placeholder="Ej: K7M4-Q2P9"
                                            value={accessCodeInput}
                                            onChange={(event) => setAccessCodeInput(event.target.value.toUpperCase())}
                                            autoComplete="off"
                                        />
                                        <button
                                            type="submit"
                                            className="w-full rounded-2xl bg-gradient-to-r from-slate-100 to-slate-300 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:opacity-90"
                                        >
                                            Validar código
                                        </button>
                                    </form>
                                ) : null}
                            </div>
                        ) : null}

                        <ConfirmDialog
                            open={confirmOpen}
                            title={`¿Estás seguro de registrar a ${formState.titular_name || 'este invitado'}?`}
                            description={`Se registrará a ${formState.titular_name || 'esta persona'} y a sus acompañantes. Una vez confirmado, no podrás revertir la inscripción desde esta pantalla.`}
                            confirmLabel="Sí, confirmar registro"
                            cancelLabel="Cancelar"
                            onConfirm={handleSubmit}
                            onCancel={() => setConfirmOpen(false)}
                        />

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
                                <div className="mt-4 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-200">
                                    <p className="text-[11px] uppercase tracking-[0.3em] text-slate-400">Código de acceso</p>
                                    <p className="mt-1 text-base font-semibold tracking-[0.2em] text-white">
                                        {submissionState.registration.access_code}
                                    </p>
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
                                        download
                                    >
                                        Descargar SVG
                                    </a>
                                    <button
                                        type="button"
                                        className="inline-flex flex-1 items-center justify-center rounded-2xl bg-cyan-300/15 px-4 py-3 text-sm font-semibold text-cyan-100 transition hover:bg-cyan-300/20"
                                        onClick={async () => {
                                            try {
                                                await downloadSvgAsPng(
                                                    submissionState.registration.qr_image_url,
                                                    `qr-${submissionState.registration.access_code}.png`,
                                                );

                                                pushNotification({
                                                    variant: 'success',
                                                    title: 'PNG listo',
                                                    message: 'El QR se descargó como PNG correctamente.',
                                                });
                                            } catch (error) {
                                                pushNotification({
                                                    variant: 'error',
                                                    title: 'No se pudo descargar',
                                                    message: error instanceof Error ? error.message : 'Ocurrió un error al generar el PNG.',
                                                });
                                            }
                                        }}
                                    >
                                        Descargar PNG
                                    </button>
                                </div>
                                <button
                                    type="button"
                                    className="mt-4 w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-medium text-white transition hover:bg-white/10"
                                    onClick={() => {
                                        window.localStorage.removeItem(storageKey);
                                        setSubmissionState({
                                            status: 'idle',
                                            message: '',
                                            registration: null,
                                        });
                                    }}
                                >
                                    Cerrar sesión local
                                </button>
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
