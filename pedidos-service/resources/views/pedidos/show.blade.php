@extends('layouts.app')

@section('title', 'Pedido #'.$pedido->id)

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">Detalle de pedido</p>
            <h1>Pedido #{{ $pedido->id }}</h1>
            <p class="muted">Información histórica de la venta.</p>
        </div>
        <div class="heading-actions">
            <a class="button button-secondary" href="{{ route('pedidos.index') }}">Volver</a>
            <a class="button button-primary" href="{{ route('pedidos.edit', $pedido) }}">Editar estado</a>
        </div>
    </div>

    <section class="card summary-grid">
        <div class="summary-item"><span>ID pedido</span><strong>#{{ $pedido->id }}</strong></div>
        <div class="summary-item"><span>Cliente ID</span><strong>{{ $pedido->cliente_id }}</strong></div>
        <div class="summary-item"><span>Fecha</span><strong>{{ $pedido->fecha->format('d/m/Y H:i') }}</strong></div>
        <div class="summary-item"><span>Estado</span><strong><span class="badge badge-{{ $pedido->estado }}">{{ ucfirst($pedido->estado) }}</span></strong></div>
        <div class="summary-item"><span>Método de pago</span><strong>{{ ucfirst($pedido->metodo_pago) }}</strong></div>
        <div class="summary-item"><span>Subtotal</span><strong>Bs {{ number_format((float) $pedido->subtotal, 2, ',', '.') }}</strong></div>
        <div class="summary-item summary-total"><span>Total</span><strong>Bs {{ number_format((float) $pedido->total, 2, ',', '.') }}</strong></div>
    </section>

    <section class="card">
        <div class="card-title-row">
            <h2>Productos del pedido</h2>
            <span class="muted">{{ $pedido->detalles->count() }} detalle(s)</span>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Precio unitario</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pedido->detalles as $detalle)
                        <tr>
                            <td>{{ $detalle->producto_nombre }}</td>
                            <td>Bs {{ number_format((float) $detalle->precio_unitario, 2, ',', '.') }}</td>
                            <td>{{ $detalle->cantidad }}</td>
                            <td><strong>Bs {{ number_format((float) $detalle->subtotal, 2, ',', '.') }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
