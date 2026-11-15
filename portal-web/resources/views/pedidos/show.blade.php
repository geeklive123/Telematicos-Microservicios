@extends('layouts.app')
@section('title', 'Detalle de pedido')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Detalle</p><h1>Pedido #{{ $pedido['id'] }}</h1><p class="muted">Estado: {{ ucfirst($pedido['estado']) }}</p></div><div class="heading-actions"><a class="button button-secondary" href="{{ route('pedidos.index') }}">Volver</a><a class="button button-primary" href="{{ route('pedidos.edit', $pedido['id']) }}">Editar pedido</a></div></div>
<section class="card summary-grid"><div class="summary-item"><span>Cliente</span><strong>{{ $pedido['cliente_id'] }}</strong></div><div class="summary-item"><span>Fecha</span><strong>{{ $pedido['fecha'] }}</strong></div><div class="summary-item"><span>Método de pago</span><strong>{{ ucfirst($pedido['metodo_pago']) }}</strong></div><div class="summary-item"><span>Total</span><strong>Bs {{ number_format((float) $pedido['total'], 2, ',', '.') }}</strong></div></section>
<section class="card table-responsive"><table><thead><tr><th>Producto</th><th>Precio</th><th>Cantidad</th><th>Subtotal</th></tr></thead><tbody>@foreach($pedido['detalles'] ?? [] as $detalle)<tr><td>{{ $detalle['producto_nombre'] }}</td><td>{{ $detalle['precio_unitario'] }}</td><td>{{ $detalle['cantidad'] }}</td><td>{{ $detalle['subtotal'] }}</td></tr>@endforeach</tbody></table></section>
@endsection
