<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();
        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            return response()->json(['message' => 'Credenciales incorrectas.'], 401);
        }
        if (! $user->activo) {
            return response()->json(['message' => 'El usuario estÃ¡ inactivo.'], 403);
        }

        return $this->tokenResponse(auth('api')->login($user), $user);
    }

    public function me(): JsonResponse
    {
        return response()->json(auth('api')->user()->only(['id', 'name', 'email', 'role', 'activo']));
    }

    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return response()->json(['message' => 'SesiÃ³n cerrada.']);
    }

    public function refresh(): JsonResponse
    {
        try {
            $token = auth('api')->refresh();
            $user = auth('api')->setToken($token)->user();
            if (! $user || ! $user->activo) {
                auth('api')->setToken($token)->invalidate();

                return response()->json(['message' => 'No autenticado.'], 401);
            }

            return $this->tokenResponse($token, $user);
        } catch (JWTException $exception) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }
    }

    private function tokenResponse(string $token, User $user): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => $user->only(['id', 'name', 'email', 'role']),
        ]);
    }
}
