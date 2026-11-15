<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePedidoRequest;
use App\Http\Requests\UpdatePedidoRequest;
use App\Models\Pedido;
use App\Services\PedidoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class PedidoController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Pedido::with('detalles')->get());
    }

    public function store(StorePedidoRequest $request, PedidoService $pedidoService): JsonResponse
    {
        $pedido = $pedidoService->create($request->validated(), $request->bearerToken());

        return response()->json($pedido, Response::HTTP_CREATED);
    }

    public function show(Pedido $pedido): JsonResponse
    {
        return response()->json($pedido->load('detalles'));
    }

    public function update(UpdatePedidoRequest $request, Pedido $pedido, PedidoService $pedidoService): JsonResponse
    {
        $pedido = $pedidoService->update($pedido, $request->validated(), $request->bearerToken());

        return response()->json($pedido->load('detalles'));
    }

    public function destroy(Pedido $pedido): JsonResponse|Response
    {
        if ($pedido->detalles()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar un pedido que tiene detalles.',
            ], Response::HTTP_CONFLICT);
        }

        $pedido->delete();

        return response()->noContent();
    }
}
