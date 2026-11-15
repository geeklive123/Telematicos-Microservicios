<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RemoteApi
{
    public function request(string $service, string $method, string $path, array $data = []): mixed
    {
        $url = config("services.{$service}.url");
        if (! is_string($url) || $url === '') {
            Log::warning('Servicio remoto sin configurar', ['servicio' => $service, 'endpoint' => $path, 'status' => null]);
            throw new RemoteException(503);
        }
        try {
            $response = Http::acceptJson()->withoutRedirecting()->connectTimeout(2)->timeout(5)
                ->withToken(session('auth_token', ''))->send($method, rtrim($url, '/').$path, $method === 'GET' ? ['query' => $data] : ['json' => $data]);
        } catch (ConnectionException $e) {
            Log::warning('Conexión remota fallida', ['servicio' => $service, 'endpoint' => $path, 'status' => null]);
            throw new RemoteException(503);
        }
        if (! $response->successful()) {
            Log::warning('Solicitud remota fallida', ['servicio' => $service, 'endpoint' => $path, 'status' => $response->status()]);
            throw new RemoteException($response->status());
        }

        return $response->json();
    }

    public function collection(string $service): array
    {
        $payload = $this->request($service, 'GET', '/api/'.$service);
        $items = $payload['data'] ?? $payload;
        if (! is_array($items) || ! array_is_list($items)) {
            throw new RemoteException(503);
        }

        return $items;
    }

    public function resource(string $service, int $id): array
    {
        $payload = $this->request($service, 'GET', "/api/{$service}/{$id}");
        $data = $payload['data'] ?? $payload;
        if (! is_array($data)) {
            throw new RemoteException(503);
        }

        return $data;
    }

    public function health(string $service): bool
    {
        try {
            $this->request($service, 'GET', '/api/health');

            return true;
        } catch (RemoteException $e) {
            return false;
        }
    }
}
