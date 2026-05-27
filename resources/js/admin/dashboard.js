import QrScanner from 'qr-scanner';

QrScanner.WORKER_PATH = new URL('qr-scanner/qr-scanner-worker.min.js', import.meta.url).toString();

const scanForm = document.getElementById('scan-form');
const qrCodeInput = document.getElementById('qr-code');
const scannedByInput = document.getElementById('scanned-by');
const scanNoteInput = document.getElementById('scan-note');
const scanResult = document.getElementById('scan-result');
const registrationSearch = document.getElementById('registration-search');
const registrationStatus = document.getElementById('registration-status');
const searchHint = document.getElementById('search-hint');
const tableBody = document.getElementById('registration-table-body');
const copyButtons = document.querySelectorAll('.copy-access-code');
const humanModePanel = document.getElementById('human-mode-panel');
const qrModePanel = document.getElementById('qr-mode-panel');
const scanModeRadios = document.querySelectorAll('input[name="scan-mode"]');
const qrVideo = document.getElementById('qr-video');
const qrStart = document.getElementById('qr-start');
const qrStop = document.getElementById('qr-stop');
const humanCodeForm = document.getElementById('human-code-form');
const humanCodeInput = document.getElementById('human-code-input');
const scanPreview = document.getElementById('scan-preview');
const lookupResult = document.getElementById('lookup-result');
const cancelForms = document.querySelectorAll('.cancel-registration-form');
const attendanceConfirmBackdrop = document.getElementById('attendance-confirm-backdrop');
const attendanceConfirmClose = document.getElementById('attendance-confirm-close');
const attendanceConfirmCancel = document.getElementById('attendance-confirm-cancel');
const attendanceConfirmSubmit = document.getElementById('attendance-confirm-submit');
const attendanceConfirmHolder = document.getElementById('attendance-confirm-holder');
const attendanceConfirmStatus = document.getElementById('attendance-confirm-status');
const attendanceConfirmAccessCode = document.getElementById('attendance-confirm-access-code');
const attendanceConfirmCount = document.getElementById('attendance-confirm-count');
const attendanceConfirmMembers = document.getElementById('attendance-confirm-members');

let qrScanner = null;
let qrScanActive = false;
let loadedRegistration = null;
let attendanceConfirmResolve = null;

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

function normalize(value) {
    return String(value || '').toLowerCase();
}

function renderLookup(registration) {
    const companions = (registration.people || [])
        .filter((person) => !person.is_titular)
        .map((person) => `<li class="flex items-center gap-2"><span class="text-slate-500">•</span>${escapeHtml(person.name)}</li>`)
        .join('');

    lookupResult.innerHTML = `
        <div class="space-y-3">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">${escapeHtml(registration.status)}</p>
                    <h3 class="mt-1 text-2xl font-semibold text-white">${escapeHtml(registration.titular_name)}</h3>
                </div>
                <p class="text-sm text-slate-400">${escapeHtml(registration.created_at || '')}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-950/70 p-4 text-sm text-slate-300">
                <p class="text-[11px] uppercase tracking-[0.3em] text-slate-400">Código humano</p>
                <p class="mt-1 text-lg font-semibold tracking-[0.2em] text-white">${escapeHtml(registration.access_code)}</p>
                <p class="mt-3 text-sm text-slate-400">Acompañantes:</p>
                <ul class="mt-2 space-y-1">${companions || '<li class="text-slate-500">Sin acompañantes</li>'}</ul>
            </div>
        </div>
    `;
}

function renderScanPreview(registration) {
    const companions = (registration.people || [])
        .filter((person) => !person.is_titular)
        .map((person) => `<li class="flex items-center gap-2"><span class="text-slate-500">•</span>${escapeHtml(person.name)}</li>`)
        .join('');

    scanPreview.innerHTML = `
        <div class="space-y-3">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-cyan-200/80">Token validado</p>
                    <h3 class="mt-1 text-2xl font-semibold text-white">${escapeHtml(registration.titular_name)}</h3>
                </div>
                <span class="rounded-full border border-cyan-300/20 bg-cyan-300/10 px-3 py-1 text-xs font-medium uppercase tracking-[0.3em] text-cyan-100">${escapeHtml(registration.status)}</span>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-950/70 p-4 text-sm text-slate-300">
                <p class="text-[11px] uppercase tracking-[0.3em] text-slate-400">Código humano</p>
                <p class="mt-1 text-lg font-semibold tracking-[0.2em] text-white">${escapeHtml(registration.access_code)}</p>
                <p class="mt-3 text-sm text-slate-400">Integrantes:</p>
                <ul class="mt-2 space-y-1">${companions || '<li class="text-slate-500">Sin acompañantes</li>'}</ul>
            </div>
        </div>
    `;
}

function updateTableFilter() {
    const query = normalize(registrationSearch.value).trim();
    const status = registrationStatus.value;
    const hintVisible = query.length > 0 && query.length < 3;

    searchHint.classList.toggle('hidden', !hintVisible);

    Array.from(tableBody.querySelectorAll('tr[data-status]')).forEach((row) => {
        const rowStatus = row.dataset.status;
        const rowSearch = normalize(row.dataset.search);
        const matchesStatus = status === 'all' || rowStatus === status;
        const matchesQuery = query.length < 3 || rowSearch.includes(query);
        row.classList.toggle('hidden', !(matchesStatus && matchesQuery));
    });
}

async function submitQrScan(qrCode) {
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

    qrCodeInput.value = '';
    scanNoteInput.value = '';

    return payload;
}

async function fetchRegistrationByQr(qrCode) {
    const response = await fetch(`/api/registrations/${encodeURIComponent(qrCode)}`, {
        headers: {
            Accept: 'application/json',
        },
    });

    const payload = await response.json();

    if (!response.ok) {
        throw new Error(payload.message || 'No pudimos leer el registro del QR.');
    }

    return payload.data;
}

async function loadRegistrationPreview(qrCode) {
    loadedRegistration = await fetchRegistrationByQr(qrCode);
    renderScanPreview(loadedRegistration);
    scanResult.textContent = 'Token validado. Revisa los integrantes y luego marca asistencia.';
}

function renderAttendanceConfirmModal(registration) {
    const members = (registration.people || []).map((person) => {
        const roleLabel = person.is_titular ? 'Titular' : 'Integrante';

        return `
            <div class="admin-modal-member-item">
                <div>
                    <p class="font-medium text-white">${escapeHtml(person.name)}</p>
                    <p class="text-xs uppercase tracking-[0.25em] text-slate-400">${roleLabel}</p>
                </div>
                <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-medium uppercase tracking-[0.25em] text-slate-300">${person.is_titular ? 'Titular' : 'Acompañante'}</span>
            </div>
        `;
    }).join('');

    attendanceConfirmHolder.textContent = registration.titular_name;
    attendanceConfirmStatus.textContent = registration.status;
    attendanceConfirmAccessCode.textContent = registration.access_code;
    attendanceConfirmCount.textContent = `${registration.people?.length || 1} persona${(registration.people?.length || 1) === 1 ? '' : 's'}`;
    attendanceConfirmMembers.innerHTML = members || '<div class="text-slate-400">No hay integrantes registrados.</div>';
}

function closeAttendanceConfirmModal() {
    attendanceConfirmBackdrop.classList.add('hidden');
    attendanceConfirmBackdrop.setAttribute('aria-hidden', 'true');

    if (attendanceConfirmResolve) {
        attendanceConfirmResolve(false);
        attendanceConfirmResolve = null;
    }
}

function openAttendanceConfirmModal(registration) {
    renderAttendanceConfirmModal(registration);
    attendanceConfirmBackdrop.classList.remove('hidden');
    attendanceConfirmBackdrop.setAttribute('aria-hidden', 'false');
    return new Promise((resolve) => {
        attendanceConfirmResolve = resolve;
    });
}

async function handleDetectedCode(detectedValue) {
    if (!qrScanActive) {
        return;
    }

    qrCodeInput.value = detectedValue;

    try {
        await loadRegistrationPreview(detectedValue);
        stopQrCamera();
    } catch (error) {
        scanResult.textContent = error instanceof Error ? error.message : 'No se pudo validar el QR.';
    }
}

function stopQrCamera() {
    qrScanActive = false;

    if (qrScanner) {
        qrScanner.stop();
        qrScanner.destroy();
        qrScanner = null;
    }

    qrVideo.srcObject = null;
}

async function startQrCamera() {
    if (!window.isSecureContext) {
        scanResult.textContent = 'La cámara solo funciona en contexto seguro. Usa localhost, HTTPS o habilita un host seguro.';
        return;
    }

    if (!navigator.mediaDevices?.getUserMedia) {
        scanResult.textContent = 'Este dispositivo no permite abrir la cámara desde el navegador.';
        return;
    }

    try {
        scanResult.textContent = 'Solicitando permiso de cámara...';

        const hasCamera = await QrScanner.hasCamera();

        if (!hasCamera) {
            scanResult.textContent = 'No encontramos una cámara disponible en este dispositivo.';
            return;
        }

        if (qrScanner) {
            qrScanner.destroy();
        }

        qrScanner = new QrScanner(
            qrVideo,
            (result) => {
                void handleDetectedCode(result.data);
            },
            {
                preferredCamera: 'environment',
                highlightScanRegion: true,
                highlightCodeOutline: true,
                maxScansPerSecond: 10,
                returnDetailedScanResult: true,
            },
        );

        qrScanActive = true;
        scanResult.textContent = 'Cámara activa. Apunta al QR para marcar asistencia.';

        await qrScanner.start();
    } catch (error) {
        const errorName = error instanceof DOMException ? error.name : '';

        if (errorName === 'NotAllowedError') {
            scanResult.textContent = 'Necesitamos permiso de cámara. Revisa el candado del navegador y vuelve a intentarlo.';
        } else if (errorName === 'NotFoundError') {
            scanResult.textContent = 'No encontramos una cámara disponible en este dispositivo.';
        } else if (errorName === 'NotReadableError') {
            scanResult.textContent = 'La cámara está ocupada por otra app o no puede abrirse en este momento.';
        } else {
            scanResult.textContent = error instanceof Error ? error.message : 'No pudimos abrir la cámara.';
        }

        stopQrCamera();
    }
}

function bindEvents() {
    scanForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const qrCode = qrCodeInput.value.trim();

        if (!qrCode) {
            scanResult.textContent = 'Debes escribir o escanear un QR primero.';
            return;
        }

        if (!loadedRegistration || loadedRegistration.qr_code !== qrCode) {
            scanResult.textContent = 'Buscando datos del registro...';

            try {
                await loadRegistrationPreview(qrCode);
            } catch (error) {
                scanResult.textContent = error instanceof Error ? error.message : 'No pudimos cargar el registro.';
                return;
            }
        }

        const confirmed = await openAttendanceConfirmModal(loadedRegistration);

        if (!confirmed) {
            scanResult.textContent = 'Marcar asistencia cancelado por el usuario.';
            return;
        }

        scanResult.textContent = 'Validando QR...';

        try {
            const payload = await submitQrScan(qrCode);
            scanResult.textContent = payload.data?.already_scanned
                ? `${payload.message} ${loadedRegistration.titular_name} ya estaba validado; no se registró de nuevo.`
                : `${payload.message} ${loadedRegistration.titular_name} quedó marcado con asistencia. El cupo no se modifica.`;
        } catch (error) {
            scanResult.textContent = error instanceof Error ? error.message : 'No se pudo validar el QR.';
        }
    });

    qrStart.addEventListener('click', startQrCamera);
    qrStop.addEventListener('click', stopQrCamera);

    scanModeRadios.forEach((radio) => {
        radio.addEventListener('change', () => {
            const useHumanMode = document.querySelector('input[name="scan-mode"]:checked')?.value === 'human';

            humanModePanel.classList.toggle('hidden', !useHumanMode);
            qrModePanel.classList.toggle('hidden', useHumanMode);

            if (useHumanMode) {
                stopQrCamera();
            }
        });
    });

    humanCodeForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const accessCode = humanCodeInput.value.trim();

        if (!accessCode) {
            lookupResult.textContent = 'Escribe el código humano para buscar el registro.';
            return;
        }

        lookupResult.textContent = 'Buscando registro...';

        try {
            const response = await fetch(`/api/registrations/access/${encodeURIComponent(accessCode)}`, {
                headers: {
                    Accept: 'application/json',
                },
            });

            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message || 'No encontramos ese código.');
            }

            renderLookup(payload.data);
        } catch (error) {
            lookupResult.textContent = error instanceof Error ? error.message : 'No pudimos validar ese código.';
        }
    });

    registrationSearch.addEventListener('input', updateTableFilter);
    registrationStatus.addEventListener('change', updateTableFilter);

    copyButtons.forEach((button) => {
        button.addEventListener('click', async () => {
            const accessCode = button.dataset.accessCode || '';

            try {
                await navigator.clipboard.writeText(accessCode);
                scanResult.textContent = `Código ${accessCode} copiado al portapapeles.`;
            } catch {
                scanResult.textContent = `No pudimos copiar el código ${accessCode}.`;
            }
        });
    });

    cancelForms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            const confirmMessage = form.dataset.confirmMessage || '¿Seguro que deseas cancelar este registro?';

            if (!window.confirm(confirmMessage)) {
                event.preventDefault();
            }
        });
    });

    attendanceConfirmClose.addEventListener('click', closeAttendanceConfirmModal);
    attendanceConfirmCancel.addEventListener('click', closeAttendanceConfirmModal);
    attendanceConfirmSubmit.addEventListener('click', () => {
        if (attendanceConfirmResolve) {
            attendanceConfirmResolve(true);
            attendanceConfirmResolve = null;
        }

        attendanceConfirmBackdrop.classList.add('hidden');
        attendanceConfirmBackdrop.setAttribute('aria-hidden', 'true');
    });

    attendanceConfirmBackdrop.addEventListener('click', (event) => {
        if (event.target === attendanceConfirmBackdrop) {
            closeAttendanceConfirmModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !attendanceConfirmBackdrop.classList.contains('hidden')) {
            closeAttendanceConfirmModal();
        }
    });

    updateTableFilter();
}

document.addEventListener('DOMContentLoaded', bindEvents);
