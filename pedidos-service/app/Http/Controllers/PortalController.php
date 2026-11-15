<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Portal\PortalService;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function dashboard(PortalService $portal): View
    {
        $clientes = $portal->listar('clientes', 'clientes', session('auth_token'));
        $productos = $portal->listar('productos', 'productos', session('auth_token'));

        return view('portal.dashboard', [
            'totalClientes' => $clientes === null ? null : count($clientes),
            'productosActivos' => $productos === null ? null : count(array_filter($productos, fn ($producto) => (bool) ($producto['activo'] ?? false))),
            'pedidosRecientes' => Pedido::query()->where('created_at', '>=', now()->subDays(30))->count(),
            'servicios' => [
                'Auth Service' => $portal->authDisponible(),
                'Productos Service' => $productos !== null,
                'Clientes Service' => $clientes !== null,
                'Pedidos Service' => true,
            ],
        ]);
    }

    public function clientes(PortalService $portal): View
    {
        return view('portal.clientes', ['clientes' => $portal->listar('clientes', 'clientes', session('auth_token'))]);
    }

    public function productos(PortalService $portal): View
    {
        return view('portal.productos', ['productos' => $portal->listar('productos', 'productos', session('auth_token'))]);
    }
}
