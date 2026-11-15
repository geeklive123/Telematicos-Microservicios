<?php

namespace App\Services;

use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class RemoteException extends RuntimeException
{
    public function __construct(public readonly int $status)
    {
        parent::__construct('Servicio temporalmente no disponible');
    }

    public function render(Request $request): Response
    {
        if ($this->status === 401) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Tu sesión expiró. Inicia sesión nuevamente.');
        }

        return back()->withInput($request->except('password'))->with('error', $this->status >= 500 ? 'Servicio temporalmente no disponible' : 'No se pudo completar la operación. Verifica los datos o tus permisos.');
    }

    public function report(): bool
    {
        return false;
    }
}
