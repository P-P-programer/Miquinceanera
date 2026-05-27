<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->role !== 'admin') {
            abort(403, 'No tienes permisos para acceder a este panel.');
        }

        return $next($request);
    }
}
