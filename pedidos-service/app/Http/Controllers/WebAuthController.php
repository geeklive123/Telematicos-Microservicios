<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Throwable;

class WebAuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (session()->has('auth_token')) {
            return redirect()->route('pedidos.index');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        abort_if(config('session.driver') === 'cookie', 503, 'La sesión requiere almacenamiento en el servidor.');
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $url = config('services.auth.url');
        if (! is_string($url) || $url === '') {
            return back()->withErrors(['email' => 'El servicio de autenticación no está disponible.'])->onlyInput('email');
        }

        try {
            $response = Http::acceptJson()->withoutRedirecting()->connectTimeout(3)->timeout(5)
                ->post(rtrim($url, '/').'/api/auth/login', $credentials);
        } catch (ConnectionException $exception) {
            return back()->withErrors(['email' => 'El servicio de autenticación no está disponible.'])->onlyInput('email');
        }

        if (! $response->successful()) {
            $message = in_array($response->status(), [401, 403, 422], true)
                ? 'No se pudo iniciar sesión. Verifique sus credenciales y el estado de su cuenta.'
                : 'El servicio de autenticación no está disponible.';

            return back()->withErrors(['email' => $message])->onlyInput('email');
        }

        $token = $response->json('access_token');
        $user = $response->json('user');
        if (! is_string($token) || $token === '' || ! is_array($user) || ! is_string($user['name'] ?? null)) {
            return back()->withErrors(['email' => 'El servicio de autenticación devolvió una respuesta inválida.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->put('auth_token', $token);
        $request->session()->put('auth_user', Arr::only($user, ['id', 'name', 'email', 'role']));

        return redirect()->route('pedidos.index');
    }

    public function logout(Request $request): RedirectResponse
    {
        $token = $request->session()->get('auth_token');
        $url = config('services.auth.url');
        try {
            if (is_string($token) && $token !== '' && is_string($url) && $url !== '') {
                Http::acceptJson()->withoutRedirecting()->connectTimeout(3)->timeout(5)
                    ->withToken($token)->post(rtrim($url, '/').'/api/auth/logout');
            }
        } catch (Throwable $exception) {
            // La sesión local debe cerrarse aunque Auth no esté disponible.
        } finally {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('login');
    }
}
