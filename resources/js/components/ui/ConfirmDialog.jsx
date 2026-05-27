import React from 'react';

export default function ConfirmDialog({
    open,
    title,
    description,
    confirmLabel = 'Confirmar',
    cancelLabel = 'Cancelar',
    onConfirm,
    onCancel,
}) {
    if (!open) {
        return null;
    }

    return (
        <div className="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/70 px-4 backdrop-blur-sm">
            <div className="w-full max-w-lg rounded-[2rem] border border-white/10 bg-slate-950 p-6 shadow-2xl shadow-slate-950/80">
                <p className="text-[11px] uppercase tracking-[0.35em] text-slate-400">Confirmación requerida</p>
                <h4 className="mt-3 text-2xl font-semibold text-white">{title}</h4>
                <p className="mt-3 text-sm leading-6 text-slate-300">{description}</p>
                <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        className="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-medium text-white transition hover:bg-white/10"
                        onClick={onCancel}
                    >
                        {cancelLabel}
                    </button>
                    <button
                        type="button"
                        className="rounded-2xl bg-gradient-to-r from-slate-100 to-slate-300 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:opacity-90"
                        onClick={onConfirm}
                    >
                        {confirmLabel}
                    </button>
                </div>
            </div>
        </div>
    );
}
