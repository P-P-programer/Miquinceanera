<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use Illuminate\Http\RedirectResponse;

class RegistrationController extends Controller
{
    public function cancel(Registration $registration): RedirectResponse
    {
        if ($registration->status === 'cancelled') {
            return back()->with('status', 'Este registro ya estaba cancelado.');
        }

        $registration->update([
            'status' => 'cancelled',
        ]);

        return back()->with('status', 'Registro cancelado. El cupo quedó liberado sin borrar el historial.');
    }
}
