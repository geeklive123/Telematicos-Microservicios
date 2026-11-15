<?php

namespace App\Services;

use App\Exceptions\PedidoCreationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ClienteService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listarActivos(#[\SensitiveParameter] ?string $token = null): array
    {
        try {
            $response = Http::acceptJson()->withToken($token ?? '')
                ->timeout(5)
                ->get($this->url('/api/clientes'));
        } catch (ConnectionException $exception) {
            throw new PedidoCreationException(
                'No se pudieron cargar los clientes porque el servicio no está disponible.',
                503,
                $exception,
            );
        }

        if (! $response->successful()) {
            throw new PedidoCreationException(
                'No se pudieron cargar los clientes porque el servicio no está disponible.',
                503,
            );
        }

        $clientes = $this->collection($response, 'clientes');

        if ($clientes === null) {
            throw new PedidoCreationException('El servicio de clientes devolvió una respuesta inválida.', 503);
        }

        return array_values(array_filter(
            $clientes,
            fn (mixed $cliente): bool => is_array($cliente)
                && isset($cliente['id'])
                && (bool) ($cliente['activo'] ?? false),
        ));
    }

    public function validarClienteActivo(int $clienteId, #[\SensitiveParameter] ?string $token = null): void
    {
        try {
            $response = Http::acceptJson()->withToken($token ?? '')
                ->timeout(5)
                ->get($this->url("/api/clientes/{$clienteId}"));
        } catch (ConnectionException $exception) {
            throw new PedidoCreationException(
                'El servicio de clientes no está disponible.',
                503,
                $exception,
            );
        }

        if ($response->notFound()) {
            throw new PedidoCreationException('El cliente seleccionado no existe.', 422);
        }

        if (! $response->successful()) {
            throw new PedidoCreationException('El servicio de clientes no está disponible.', 503);
        }

        $cliente = $this->resource($response, 'cliente');

        if (! is_array($cliente) || ! array_key_exists('activo', $cliente)) {
            throw new PedidoCreationException('El servicio de clientes no está disponible.', 503);
        }

        if (! (bool) $cliente['activo']) {
            throw new PedidoCreationException('El cliente seleccionado está inactivo.', 422);
        }
    }

    private function url(string $path): string
    {
        $baseUrl = config('services.clientes.url');

        if (! is_string($baseUrl) || $baseUrl === '') {
            throw new PedidoCreationException('El servicio de clientes no está disponible.', 503);
        }

        return rtrim($baseUrl, '/').$path;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resource(Response $response, string $key): ?array
    {
        $resource = $response->json('data') ?? $response->json($key) ?? $response->json();

        return is_array($resource) ? $resource : null;
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function collection(Response $response, string $key): ?array
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            return null;
        }

        $items = $payload['data'] ?? $payload[$key] ?? $payload;

        if (is_array($items) && isset($items['data']) && is_array($items['data'])) {
            $items = $items['data'];
        }

        return is_array($items) && array_is_list($items) ? $items : null;
    }
}
