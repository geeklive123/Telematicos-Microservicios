<?php

namespace App\Portal;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PortalService
{
    /** @return list<array<string, mixed>>|null */
    public function listar(string $servicio, string $recurso, #[\SensitiveParameter] ?string $token): ?array
    {
        $url = config("services.{$servicio}.url");
        if (! is_string($url) || $url === '') {
            return null;
        }
        try {
            $items = [];
            $page = 1;
            do {
                $response = Http::acceptJson()->withoutRedirecting()->withToken($token ?? '')
                    ->connectTimeout(2)->timeout(4)->get(rtrim($url, '/')."/api/{$recurso}", $page > 1 ? ['page' => $page] : []);
                RemoteFailure::response($servicio, "/api/{$recurso}", $response);
                if (! $response->successful()) {
                    return null;
                }
                $payload = $response->json();
                if (! is_array($payload)) {
                    return null;
                }
                $data = $payload['data'] ?? $payload[$recurso] ?? $payload;
                $pagination = is_array($data) && isset($data['data']) ? $data : $payload;
                $data = $data['data'] ?? $data;
                if (! is_array($data) || ! array_is_list($data)) {
                    return null;
                }
                $items = array_merge($items, array_values(array_filter($data, 'is_array')));
                $lastPage = (int) ($pagination['last_page'] ?? 1);
                $page++;
                if ($page > 100 && $page <= $lastPage) {
                    return null;
                }
            } while ($page <= $lastPage);

            return $items;
        } catch (ConnectionException $exception) {
            RemoteFailure::connection($servicio, "/api/{$recurso}");

            return null;
        }
    }

    public function authDisponible(): bool
    {
        $url = config('services.auth.url');
        if (! is_string($url) || $url === '') {
            return false;
        }
        try {
            $response = Http::withoutRedirecting()->connectTimeout(2)->timeout(4)->get(rtrim($url, '/').'/api/health');
            if (! $response->successful()) {
                Log::warning('Consulta de salud remota fallida.', ['servicio' => 'auth', 'endpoint' => '/api/health', 'status' => $response->status()]);
            }

            return $response->successful();
        } catch (ConnectionException $exception) {
            RemoteFailure::connection('auth', '/api/health');

            return false;
        }
    }
}
