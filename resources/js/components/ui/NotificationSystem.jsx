import React, { createContext, useCallback, useContext, useMemo, useState } from 'react';

const NotificationContext = createContext(null);

export function NotificationProvider({ children }) {
    const [notifications, setNotifications] = useState([]);

    const dismiss = useCallback((id) => {
        setNotifications((current) => current.filter((notification) => notification.id !== id));
    }, []);

    const pushNotification = useCallback((notification) => {
        const id = crypto.randomUUID();

        setNotifications((current) => [
            ...current,
            {
                id,
                variant: 'info',
                title: '',
                message: '',
                timeout: 4500,
                ...notification,
            },
        ]);

        if (notification.timeout !== 0) {
            window.setTimeout(() => dismiss(id), notification.timeout ?? 4500);
        }

        return id;
    }, [dismiss]);

    const value = useMemo(() => ({ pushNotification, dismiss }), [pushNotification, dismiss]);

    return (
        <NotificationContext.Provider value={value}>
            {children}
            <div className="pointer-events-none fixed right-4 top-4 z-50 flex w-[min(24rem,calc(100vw-2rem))] flex-col gap-3">
                {notifications.map((notification) => (
                    <article
                        key={notification.id}
                        className={`pointer-events-auto rounded-2xl border p-4 shadow-2xl backdrop-blur-xl ${notificationClasses[notification.variant] ?? notificationClasses.info}`}
                    >
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                {notification.title ? (
                                    <p className="text-sm font-semibold text-white">{notification.title}</p>
                                ) : null}
                                <p className="mt-1 text-sm leading-6 text-slate-200">{notification.message}</p>
                            </div>
                            <button
                                type="button"
                                className="rounded-full border border-white/10 bg-white/5 px-2 py-1 text-xs text-slate-200 transition hover:bg-white/10"
                                onClick={() => dismiss(notification.id)}
                            >
                                Cerrar
                            </button>
                        </div>
                    </article>
                ))}
            </div>
        </NotificationContext.Provider>
    );
}

export function useNotifications() {
    const context = useContext(NotificationContext);

    if (!context) {
        throw new Error('useNotifications must be used within a NotificationProvider');
    }

    return context;
}

const notificationClasses = {
    info: 'border-cyan-300/20 bg-cyan-500/15',
    success: 'border-emerald-300/20 bg-emerald-500/15',
    error: 'border-rose-300/20 bg-rose-500/15',
    warning: 'border-amber-300/20 bg-amber-500/15',
};
