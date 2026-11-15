<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireWebAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if(config('session.driver') === 'cookie', 503, 'La sesión requiere almacenamiento en el servidor.');

        if (! $request->session()->has('auth_token')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
