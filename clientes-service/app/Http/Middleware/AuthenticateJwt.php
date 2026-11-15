<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthenticateJwt
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if (! is_string($token) || $token === '') {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        try {
            $payload = app('tymon.jwt')->setToken($token)->getPayload();
            $subject = $payload->get('sub');
            $role = $payload->get('role');
            if ((! is_string($subject) && ! is_int($subject)) || (string) $subject === '' || ! is_string($role) || $role === '') {
                return response()->json(['message' => 'No autenticado.'], 401);
            }
            $request->attributes->set('auth_user_id', $subject);
            $request->attributes->set('auth_role', $role);
        } catch (JWTException $exception) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        return $next($request);
    }
}
