<?php

namespace App\Services;

use App\Exceptions\PedidoCreationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ProductoService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listarActivos(#[\SensitiveParameter] ?string $token = null): array
    {
        try {
            $response = Http::acceptJson()->withToken($token ?? '')
                ->timeout(5)
                ->get($this->url('/api/productos'));
        } catch (ConnectionException $exception) {
            throw new PedidoCreationException(
                'No se pudieron cargar los productos porque el servicio no estÃ¡ disponible.',
                503,
                $exception,
            );
        }

        if (! $response->successful()) {
            throw new PedidoCreationException(
                'No se pudieron cargar los productos porque el servicio no estÃ¡ disponible.',
                503,
            );
        }

        $productos = $this->collection($response, 'productos');

        if ($productos === null) {
            throw new PedidoCreationException('El servicio de productos devolviÃ³ una respuesta invÃ¡lida.', 503);
        }

        return array_values(array_filter(
            $productos,
            fn (mixed $producto): bool => is_array($producto)
                && isset($producto['id'], $producto['nombre'], $producto['precio'], $producto['stock'])
                && (bool) ($producto['activo'] ?? false),
        ));
    }

    /**
     * @return array{id: int, nombre: string, precio: float, stock: int, activo: bool}
     */
    public function obtenerProductoDisponible(int $productoId, int $cantidad, #[\SensitiveParameter] ?string $token = null): array
    {
        try {
            $response = Http::acceptJson()->withToken($token ?? '')
                ->timeout(5)
                ->get($this->url("/api/productos/{$productoId}"));
        } catch (ConnectionException $exception) {
            throw new PedidoCreationException(
                'El servicio de productos no estÃ¡ disponible.',
                503,
                $exception,
            );
        }

        if ($response->notFound()) {
            throw new PedidoCreationException('Uno de los productos seleccionados no existe.', 422);
        }

        if (! $response->successful()) {
            throw new PedidoCreationException('El servicio de productos no estÃ¡ disponible.', 503);
        }

        $producto = $this->resource($response, 'producto');

        if (! $this->isValidProduct($producto, $productoId)) {
            throw new PedidoCreationException('El servicio de productos no estÃ¡ disponible.', 503);
        }

        if (! (bool) $producto['activo']) {
            throw new PedidoCreationException('Uno de los productos seleccionados estÃ¡ inactivo.', 422);
        }

        if ((int) $producto['stock'] < $cantidad) {
            throw new PedidoCreationException('Stock insuficiente para el producto seleccionado.', 409);
        }

        return [
            'id' => (int) $producto['id'],
            'nombre' => (string) $producto['nombre'],
            'precio' => round((float) $producto['precio'], 2),
            'stock' => (int) $producto['stock'],
            'activo' => (bool) $producto['activo'],
        ];
    }

    public function descontarStock(int $productoId, int $cantidad, string $key, string $reference, #[\SensitiveParameter] ?string $token = null): void
    {
        $payload = ['cantidad' => $cantidad, 'idempotency_key' => $key, 'source_type' => 'pedido', 'source_reference' => $reference];
        $uncertain = false;
        for ($try = 0; $try < 2; $try++) {
            try {
                $response = Http::acceptJson()->withToken($token ?? '')->timeout(5)->post($this->url("/api/productos/{$productoId}/descontar-stock"), $payload);
                break;
            } catch (ConnectionException $exception) {
                $uncertain = true;
                if ($try === 1) {
                    throw new PedidoCreationException('El servicio de productos no estÃ¡ disponible.', 503, $exception, true);
                }
            }
        }

        if ($uncertain && ! $response->successful()) {
            throw new PedidoCreationException('El resultado del descuento es incierto.', 503, null, true);
        }
        if ($response->notFound()) {
            throw new PedidoCreationException('Uno de los productos seleccionados no existe.', 422);
        }

        if (in_array($response->status(), [409, 422], true)) {
            throw new PedidoCreationException('Stock insuficiente para el producto seleccionado.', 409);
        }

        if (! $response->successful()) {
            throw new PedidoCreationException('El servicio de productos no estÃ¡ disponible.', 503, null, true);
        }
    }

    public function reponerStock(int $productoId, int $cantidad, string $key, string $sourceType, string $reference, #[\SensitiveParameter] ?string $token = null): void
    {
        try {
            $response = Http::acceptJson()->withToken($token ?? '')
                ->timeout(5)
                ->post($this->url("/api/productos/{$productoId}/reponer-stock"), [
                    'cantidad' => $cantidad,
                    'idempotency_key' => $key,
                    'source_type' => $sourceType,
                    'source_reference' => $reference,
                ]);
        } catch (ConnectionException $exception) {
            throw new PedidoCreationException(
                'El servicio de productos no estÃ¡ disponible.',
                503,
                $exception,
            );
        }

        if ($response->status() === 409) {
            throw new PedidoCreationException('El servicio de productos rechazÃ³ la reposiciÃ³n por un conflicto.', 409);
        }

        if (! $response->successful()) {
            throw new PedidoCreationException('El servicio de productos no estÃ¡ disponible.', 503);
        }
    }

    private function url(string $path): string
    {
        $baseUrl = config('services.productos.url');

        if (! is_string($baseUrl) || $baseUrl === '') {
            throw new PedidoCreationException('El servicio de productos no estÃ¡ disponible.', 503);
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

    /**
     * @param  array<string, mixed>|null  $producto
     */
    private function isValidProduct(?array $producto, int $requestedProductId): bool
    {
        return $producto !== null
            && array_key_exists('id', $producto)
            && array_key_exists('nombre', $producto)
            && array_key_exists('precio', $producto)
            && array_key_exists('stock', $producto)
            && array_key_exists('activo', $producto)
            && is_numeric($producto['id'])
            && (int) $producto['id'] === $requestedProductId
            && is_string($producto['nombre'])
            && $producto['nombre'] !== ''
            && mb_strlen($producto['nombre']) <= 150
            && is_numeric($producto['precio'])
            && (float) $producto['precio'] >= 0
            && is_numeric($producto['stock']);
    }
}
