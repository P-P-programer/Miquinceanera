import React, { useEffect, useMemo, useState } from 'react';
import { NotificationProvider, useNotifications } from './ui/NotificationSystem';

const storageKey = 'miquinceanera-registration';

function normalizeRegistration(payload) {
    return payload?.data ?? payload;
}

function AlbumAppShell() {
    const { pushNotification } = useNotifications();
    const [restoringSession, setRestoringSession] = useState(true);
    const [accessCode, setAccessCode] = useState('');
    const [accessCodeInput, setAccessCodeInput] = useState('');
    const [photos, setPhotos] = useState([]);
    const [loadingPhotos, setLoadingPhotos] = useState(false);
    const [submissionState, setSubmissionState] = useState({ status: 'idle', message: '' });
    const [formState, setFormState] = useState({ caption: '', photo: null });
    const [inputKey, setInputKey] = useState(0);

    useEffect(() => {
        const stored = window.localStorage.getItem(storageKey);

        if (!stored) {
            setRestoringSession(false);
            return;
        }

        try {
            const parsed = JSON.parse(stored);

            if (parsed?.access_code) {
                setAccessCode(parsed.access_code);
                setAccessCodeInput(parsed.access_code);
            }
        } catch {
            window.localStorage.removeItem(storageKey);
        } finally {
            setRestoringSession(false);
        }
    }, []);

    useEffect(() => {
        if (!accessCode) {
            setPhotos([]);
            return;
        }

        const controller = new AbortController();

        const loadPhotos = async () => {
            setLoadingPhotos(true);

            try {
                const response = await fetch(`/api/album/photos?access_code=${encodeURIComponent(accessCode)}`, {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                });

                const payload = await response.json();

                if (!response.ok) {
                    throw new Error(payload.message || 'No pudimos cargar el álbum.');
                }

                setPhotos(payload.data?.photos ?? []);
            } catch (error) {
                setPhotos([]);

                if (!(error instanceof DOMException && error.name === 'AbortError')) {
                    pushNotification({
                        variant: 'error',
                        title: 'Álbum no disponible',
                        message: error instanceof Error ? error.message : 'No pudimos cargar el álbum.',
                    });
                }
            } finally {
                setLoadingPhotos(false);
            }
        };

        loadPhotos();

        return () => controller.abort();
    }, [accessCode, pushNotification, submissionState.message]);

    async function handleAccessCodeSubmit(event) {
        event.preventDefault();

        const code = accessCodeInput.trim();

        if (!code) {
            pushNotification({
                variant: 'warning',
                title: 'Falta el código',
                message: 'Escribe tu código para abrir el álbum.',
            });
            return;
        }

        try {
            const response = await fetch(`/api/registrations/access/${encodeURIComponent(code)}`, {
                headers: {
                    Accept: 'application/json',
                },
            });

            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message || 'No pudimos validar ese código.');
            }

            const registration = normalizeRegistration(payload);
            setAccessCode(registration.access_code);
            window.localStorage.setItem(storageKey, JSON.stringify({ access_code: registration.access_code }));

            pushNotification({
                variant: 'success',
                title: 'Acceso confirmado',
                message: 'Ya puedes ver y subir fotos en el álbum.',
            });
        } catch (error) {
            pushNotification({
                variant: 'error',
                title: 'Código inválido',
                message: error instanceof Error ? error.message : 'No pudimos validar el código.',
            });
        }
    }

    async function handleAlbumSubmit(event) {
        event.preventDefault();

        if (!accessCode || !formState.photo) {
            setSubmissionState({
                status: 'error',
                message: 'Selecciona una foto antes de enviarla.',
            });
            return;
        }

        setSubmissionState({
            status: 'loading',
            message: 'Guardando la foto...',
        });

        const formData = new FormData();
        formData.append('access_code', accessCode);
        formData.append('submitted_by', 'Invitado');
        formData.append('caption', formState.caption);
        formData.append('photo', formState.photo);

        try {
            const response = await fetch('/api/album/photos', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                },
                body: formData,
            });

            const payload = await response.json();

            if (!response.ok) {
                const firstError = Object.values(payload.errors || {})[0]?.[0] || 'No se pudo guardar la foto.';
                throw new Error(firstError);
            }

            setSubmissionState({
                status: 'success',
                message: payload.message,
            });
            setFormState({ caption: '', photo: null });
            setInputKey((current) => current + 1);
            setPhotos((current) => [payload.data, ...current]);

            pushNotification({
                variant: 'success',
                title: 'Foto guardada',
                message: 'Tu momento quedó en el álbum.',
            });
        } catch (error) {
            setSubmissionState({
                status: 'error',
                message: error instanceof Error ? error.message : 'No se pudo guardar la foto.',
            });
        }
    }

    const stats = useMemo(() => ({
        photos: photos.length,
    }), [photos.length]);

    return (
        <div className="min-h-screen text-slate-100">
            <div className="mx-auto flex min-h-screen w-full max-w-7xl flex-col gap-8 px-6 py-6 lg:px-10">
                <header className="flex flex-col gap-4 rounded-[2rem] border border-white/10 bg-white/5 p-6 backdrop-blur-xl lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p className="text-[11px] uppercase tracking-[0.4em] text-slate-400">Miquinceañera</p>
                        <h1 className="mt-2 text-4xl font-semibold text-white">Álbum de momentos</h1>
                        <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-300">Sube tus mejores momentos aquí. El index solo muestra el conteo; esta vista está hecha para mirar las fotos con más espacio.</p>
                    </div>
                    <div className="flex flex-col gap-3 sm:flex-row">
                        <a href="/" className="inline-flex items-center justify-center rounded-2xl border border-white/10 bg-slate-950/60 px-4 py-3 text-sm font-medium text-white transition hover:bg-white/10">Volver a la invitación</a>
                        <a href="#upload" className="inline-flex items-center justify-center rounded-2xl bg-gradient-to-r from-slate-100 to-slate-300 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:opacity-90">Subir foto</a>
                    </div>
                </header>

                <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div className="rounded-[1.5rem] border border-white/10 bg-slate-950/70 p-5">
                        <p className="text-sm text-slate-400">Fotos compartidas</p>
                        <p className="mt-2 text-4xl font-semibold text-white">{stats.photos}</p>
                    </div>
                    <div className="rounded-[1.5rem] border border-white/10 bg-slate-950/70 p-5">
                        <p className="text-sm text-slate-400">Formato recomendado</p>
                        <p className="mt-2 text-xl font-semibold text-white">WebP</p>
                    </div>
                    <div className="rounded-[1.5rem] border border-white/10 bg-slate-950/70 p-5">
                        <p className="text-sm text-slate-400">Vista</p>
                        <p className="mt-2 text-xl font-semibold text-white">Galería amplia</p>
                    </div>
                    <div className="rounded-[1.5rem] border border-white/10 bg-slate-950/70 p-5">
                        <p className="text-sm text-slate-400">Acceso</p>
                        <p className="mt-2 text-xl font-semibold text-white">Código de invitado</p>
                    </div>
                </section>

                {!accessCode ? (
                    <section className="rounded-[2rem] border border-white/10 bg-slate-950/70 p-6 backdrop-blur-xl">
                        <p className="text-[11px] uppercase tracking-[0.35em] text-slate-400">Validar acceso</p>
                        <h2 className="mt-2 text-2xl font-semibold text-white">Ingresa tu código para abrir el álbum</h2>
                        <form className="mt-4 flex flex-col gap-3 sm:flex-row" onSubmit={handleAccessCodeSubmit}>
                            <input
                                className="flex-1 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-300/40"
                                placeholder="Código de acceso"
                                value={accessCodeInput}
                                onChange={(event) => setAccessCodeInput(event.target.value.toUpperCase())}
                            />
                            <button type="submit" className="rounded-2xl bg-gradient-to-r from-slate-100 to-slate-300 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:opacity-90">
                                Abrir álbum
                            </button>
                        </form>
                    </section>
                ) : null}

                <section id="upload" className="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
                    <article className="rounded-[2rem] border border-white/10 bg-white/5 p-6 backdrop-blur-xl">
                        <p className="text-[11px] uppercase tracking-[0.35em] text-slate-400">Subir momento</p>
                        <h2 className="mt-2 text-2xl font-semibold text-white">Comparte una foto</h2>
                        <p className="mt-2 text-sm leading-6 text-slate-300">La imagen se guarda en formato WebP cuando el servidor lo permite, para que cargue más rápido.</p>

                        <form className="mt-4 space-y-3" onSubmit={handleAlbumSubmit}>
                            <input
                                key={inputKey}
                                type="file"
                                accept="image/*"
                                className="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white file:mr-4 file:rounded-full file:border-0 file:bg-cyan-300/15 file:px-4 file:py-2 file:text-sm file:font-medium file:text-cyan-100"
                                onChange={(event) => setFormState((current) => ({ ...current, photo: event.target.files?.[0] ?? null }))}
                                required
                            />
                            <textarea
                                className="min-h-[110px] w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white placeholder:text-slate-500 outline-none transition focus:border-cyan-300/40"
                                placeholder="Escribe una frase corta para acompañar la foto"
                                value={formState.caption}
                                onChange={(event) => setFormState((current) => ({ ...current, caption: event.target.value }))}
                            />
                            <button
                                type="submit"
                                className="w-full rounded-2xl bg-gradient-to-r from-slate-100 to-slate-300 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                                disabled={submissionState.status === 'loading' || !accessCode}
                            >
                                {submissionState.status === 'loading' ? 'Guardando foto...' : 'Guardar foto'}
                            </button>
                        </form>

                        {submissionState.message ? (
                            <div className={`mt-3 rounded-2xl border px-4 py-3 text-sm ${submissionState.status === 'error' ? 'border-rose-300/20 bg-rose-300/10 text-rose-100' : 'border-emerald-300/20 bg-emerald-300/10 text-emerald-100'}`}>
                                {submissionState.message}
                            </div>
                        ) : null}
                    </article>

                    <article className="rounded-[2rem] border border-white/10 bg-slate-950/70 p-6 backdrop-blur-xl">
                        <div className="flex items-end justify-between gap-4">
                            <div>
                                <p className="text-[11px] uppercase tracking-[0.35em] text-slate-400">Galería</p>
                                <h2 className="mt-2 text-2xl font-semibold text-white">Momentos compartidos</h2>
                            </div>
                            {loadingPhotos ? <span className="text-xs uppercase tracking-[0.3em] text-slate-500">Cargando...</span> : null}
                        </div>

                        <div className="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            {photos.length ? photos.map((photo) => (
                                <article key={photo.id} className="overflow-hidden rounded-[1.5rem] border border-white/10 bg-white/5">
                                    <img src={photo.photo_url} alt={photo.caption || `Foto de ${photo.submitted_by}`} className="h-72 w-full object-cover" />
                                    <div className="space-y-2 p-4">
                                        <p className="text-sm font-semibold text-white">{photo.submitted_by}</p>
                                        <p className="text-sm leading-6 text-slate-300">{photo.caption || 'Sin descripción'}</p>
                                        <p className="text-xs uppercase tracking-[0.3em] text-slate-500">{photo.created_at}</p>
                                    </div>
                                </article>
                            )) : (
                                <p className="text-sm text-slate-400">Aún no hay fotos compartidas.</p>
                            )}
                        </div>
                    </article>
                </section>
            </div>
        </div>
    );
}

export default function AlbumApp() {
    return (
        <NotificationProvider>
            <AlbumAppShell />
        </NotificationProvider>
    );
}