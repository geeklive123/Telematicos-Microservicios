<?php

namespace App\Http\Controllers;

use App\Exceptions\PedidoCreationException;
use App\Http\Requests\StorePedidoRequest;
use App\Http\Requests\UpdatePedidoRequest;
use App\Models\Pedido;
use App\Services\ClienteService;
use App\Services\PedidoService;
use App\Services\ProductoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PedidoWebController extends Controller
{
    public function index(): View
    {
        $pedidos = Pedido::query()
            ->withCount('detalles')
            ->latest('id')
            ->paginate(10);

        return view('pedidos.index', compact('pedidos'));
    }

    public function create(ClienteService $clienteService, ProductoService $productoService): View
    {
        $clientes = [];
        $productos = [];
        $errores = [];

        try {
            $clientes = $clienteService->listarActivos(session('auth_token'));
        } catch (PedidoCreationException $exception) {
            $errores[] = $exception->getMessage();
        }

        try {
            $productos = $productoService->listarActivos(session('auth_token'));
        } catch (PedidoCreationException $exception) {
            $errores[] = $exception->getMessage();
        }

        if ($errores !== []) {
            session()->flash('error', implode(' ', array_unique($errores)));
        }

        return view('pedidos.create', compact('clientes', 'productos'));
    }

    public function store(StorePedidoRequest $request, PedidoService $pedidoService): RedirectResponse
    {
        try {
            $pedido = $pedidoService->create($request->validated(), $request->session()->get('auth_token'));
        } catch (PedidoCreationException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('pedidos.show', $pedido)
            ->with('success', 'Pedido creado correctamente.');
    }

    public function show(Pedido $pedido): View
    {
        return view('pedidos.show', [
            'pedido' => $pedido->load('detalles'),
        ]);
    }

    public function edit(Pedido $pedido): View
    {
        return view('pedidos.edit', compact('pedido'));
    }

    public function update(UpdatePedidoRequest $request, Pedido $pedido, PedidoService $pedidoService): RedirectResponse
    {
        try {
            $pedido = $pedidoService->update($pedido, $request->validated(), $request->session()->get('auth_token'));
        } catch (PedidoCreationException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('pedidos.show', $pedido)
            ->with('success', $pedido->wasChanged('estado') && $pedido->estado === 'cancelado'
                ? 'Pedido cancelado correctamente y stock repuesto.'
                : 'Pedido actualizado correctamente.');
    }
}
