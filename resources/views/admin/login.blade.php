<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Admin | {{ config('app.name', 'Laravel') }}</title>
        @fonts
        @vite(['resources/css/app.css'])
    </head>
    <body>
        <div class="min-h-screen bg-slate-950 px-6 py-10 text-slate-100">
            <div class="mx-auto flex min-h-[calc(100vh-5rem)] max-w-md items-center">
                <form method="POST" action="{{ route('admin.login.store') }}" class="w-full rounded-[2rem] border border-white/10 bg-white/5 p-8 shadow-2xl shadow-slate-950/50 backdrop-blur-xl">
                    @csrf
                    <p class="text-[11px] uppercase tracking-[0.4em] text-slate-400">Panel admin</p>
                    <h1 class="mt-3 text-3xl font-semibold text-white">Iniciar sesión</h1>
                    <p class="mt-3 text-sm leading-6 text-slate-300">Usa el usuario local para validar accesos y revisar la asistencia.</p>

                    <div class="mt-6 space-y-4">
                        <div>
                            <label class="mb-2 block text-sm text-slate-300" for="email">Correo</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-300/40" required>
                            @error('email')
                                <p class="mt-2 text-sm text-rose-200">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-2 block text-sm text-slate-300" for="password">Contraseña</label>
                            <input id="password" name="password" type="password" class="w-full rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-300/40" required>
                            @error('password')
                                <p class="mt-2 text-sm text-rose-200">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <button type="submit" class="mt-6 w-full rounded-2xl bg-gradient-to-r from-slate-100 to-slate-300 px-4 py-3 font-semibold text-slate-950 transition hover:opacity-90">
                        Entrar
                    </button>
                </form>
            </div>
        </div>
    </body>
</html>
