<?php

namespace App\Portal;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class RemoteFailure
{
    public static function response(string $service, string $endpoint, Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        Log::warning('Solicitud a servicio remoto fallida.', [
            'servicio' => $service,
            'endpoint' => $endpoint,
            'status' => $response->status(),
        ]);

        if ($response->status() === 401) {
            throw new RemoteAuthenticationException;
        }
    }

    public static function connection(string $service, string $endpoint): void
    {
        Log::warning('Conexion con servicio remoto fallida.', [
            'servicio' => $service,
            'endpoint' => $endpoint,
            'status' => null,
        ]);
    }
}
