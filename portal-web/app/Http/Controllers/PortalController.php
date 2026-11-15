<?php

namespace App\Http\Controllers;

use App\Services\RemoteApi;
use App\Services\RemoteException;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    public function __construct(private readonly RemoteApi $api) {}

    private function optional(string $service): ?array
    {
        try {
            return $this->api->collection($service);
        } catch (RemoteException $e) {
            if ($e->status === 401) {
                throw $e;
            }

            return null;
        }
    }

    public function dashboard()
    {
        $clientes = $this->optional('clientes');
        $productos = $this->optional('productos');
        $pedidos = $this->optional('pedidos');

        return view('portal.dashboard', [
            'totalClientes' => $clientes === null ? null : count($clientes),
            'totalProductos' => $productos === null ? null : count($productos),
            'totalPedidos' => $pedidos === null ? null : count($pedidos),
            'servicios' => ['Auth Service' => $this->api->health('auth'), 'Clientes Service' => $this->api->health('clientes'), 'Productos Service' => $this->api->health('productos'), 'Pedidos Service' => $this->api->health('pedidos')],
        ]);
    }

    public function clientes()
    {
        return view('portal.listado', ['tipo' => 'clientes', 'items' => $this->optional('clientes')]);
    }

    public function productos()
    {
        return view('portal.listado', ['tipo' => 'productos', 'items' => $this->optional('productos')]);
    }

    public function pedidos()
    {
        return view('pedidos.index', ['pedidos' => $this->optional('pedidos')]);
    }

    public function crear(string $tipo)
    {
        return view('portal.form', ['tipo' => $tipo, 'item' => null]);
    }

    public function editar(string $tipo, int $id)
    {
        return view('portal.form', ['tipo' => $tipo, 'item' => $this->api->resource($tipo, $id)]);
    }

    private function datos(Request $request, string $tipo): array
    {
        return $request->validate($tipo === 'clientes' ? [
            'nombre' => 'required|string|max:100', 'apellido' => 'required|string|max:100', 'email' => 'required|email|max:150',
            'telefono' => 'nullable|string|max:30', 'direccion' => 'nullable|string', 'activo' => 'required|boolean',
        ] : [
            'nombre' => 'required|string|max:150', 'descripcion' => 'nullable|string', 'categoria_id' => 'required|integer|min:1',
            'marca_id' => 'nullable|integer|min:1', 'precio' => 'required|numeric|min:0', 'stock' => 'required|integer|min:0', 'sku' => 'nullable|string|max:100', 'activo' => 'required|boolean',
        ]);
    }

    public function guardar(Request $request, string $tipo)
    {
        $this->api->request($tipo, 'POST', "/api/{$tipo}", $this->datos($request, $tipo));

        return redirect()->route('portal.'.$tipo)->with('success', 'Registro creado correctamente.');
    }

    public function actualizar(Request $request, string $tipo, int $id)
    {
        $this->api->request($tipo, 'PUT', "/api/{$tipo}/{$id}", $this->datos($request, $tipo));

        return redirect()->route('portal.'.$tipo)->with('success', 'Registro actualizado correctamente.');
    }

    public function crearPedido()
    {
        return view('pedidos.create', ['clientes' => $this->optional('clientes'), 'productos' => $this->optional('productos')]);
    }

    public function guardarPedido(Request $request)
    {
        $data = $request->validate(['cliente_id' => 'required|integer|min:1', 'metodo_pago' => 'required|in:efectivo,qr,tarjeta', 'fecha' => 'required|date', 'detalles' => 'required|array|min:1', 'detalles.*.producto_id' => 'required|integer|min:1', 'detalles.*.cantidad' => 'required|integer|min:1']);
        $data['estado'] = 'pendiente';
        $pedido = $this->api->request('pedidos', 'POST', '/api/pedidos', $data);

        return redirect()->route('pedidos.show', $pedido['id'])->with('success', 'Pedido creado correctamente.');
    }

    public function verPedido(int $id)
    {
        return view('pedidos.show', ['pedido' => $this->api->resource('pedidos', $id)]);
    }

    public function editarPedido(int $id)
    {
        return view('pedidos.edit', ['pedido' => $this->api->resource('pedidos', $id)]);
    }

    public function actualizarPedido(Request $request, int $id)
    {
        $data = $request->validate(['estado' => 'required|in:pendiente,pagado,completado,cancelado', 'metodo_pago' => 'nullable|in:efectivo,qr,tarjeta', 'fecha' => 'nullable|date']);
        $this->api->request('pedidos', 'PUT', "/api/pedidos/{$id}", array_filter($data, fn ($value) => $value !== null));

        return redirect()->route('pedidos.show', $id)->with('success', 'Pedido actualizado correctamente.');
    }
}
