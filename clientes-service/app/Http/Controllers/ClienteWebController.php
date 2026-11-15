<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Models\Cliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClienteWebController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('buscar', ''));
        $status = (string) $request->query('estado', 'todos');

        $clientes = Cliente::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('nombre', 'like', "%{$search}%")
                        ->orWhere('apellido', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status === 'activos', fn ($query) => $query->where('activo', true))
            ->when($status === 'inactivos', fn ($query) => $query->where('activo', false))
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return view('clientes.index', [
            'clientes' => $clientes,
            'totalClientes' => Cliente::query()->count(),
            'clientesActivos' => Cliente::query()->where('activo', true)->count(),
            'clientesInactivos' => Cliente::query()->where('activo', false)->count(),
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('clientes.create');
    }

    public function store(StoreClienteRequest $request): RedirectResponse
    {
        Cliente::create($request->validated());

        return to_route('clientes.index')->with('success', 'Cliente creado correctamente.');
    }

    public function edit(Cliente $cliente): View
    {
        return view('clientes.edit', compact('cliente'));
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        $cliente->update($request->validated());

        return to_route('clientes.index')->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Cliente $cliente): RedirectResponse
    {
        $cliente->delete();

        return to_route('clientes.index')->with('success', 'Cliente eliminado correctamente.');
    }
}
