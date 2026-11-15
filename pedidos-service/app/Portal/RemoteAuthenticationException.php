<?php

namespace App\Portal;

use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class RemoteAuthenticationException extends RuntimeException
{
    public function render(Request $request): Response
    {
        if ($request->is('api/*')) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $request->session()->forget(['auth_token', 'auth_user']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('error', 'Tu sesión expiró. Inicia sesión nuevamente.');
    }

    public function report(): bool
    {
        return false;
    }
}
