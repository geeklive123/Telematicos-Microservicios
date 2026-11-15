@extends('layouts.app')
@section('title', 'Pedidos')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Gestión comercial</p><h1>Pedidos</h1><p class="muted">Consulta y administra los pedidos registrados.</p></div><a class="button button-primary" href="{{ route('pedidos.create') }}">Crear pedido</a></div>
@if($pedidos === null)<div class="alert alert-error">Servicio temporalmente no disponible</div>@else
<div class="card table-responsive"><table><thead><tr><th>Pedido</th><th>Cliente</th><th>Fecha</th><th>Estado</th><th>Total</th><th>Acciones</th></tr></thead><tbody>@forelse($pedidos as $pedido)<tr><td>#{{ $pedido['id'] }}</td><td>{{ $pedido['cliente_id'] }}</td><td>{{ $pedido['fecha'] }}</td><td>{{ ucfirst($pedido['estado']) }}</td><td>Bs {{ number_format((float) $pedido['total'], 2, ',', '.') }}</td><td><a class="link" href="{{ route('pedidos.show', $pedido['id']) }}">Ver detalle</a></td></tr>@empty<tr><td colspan="6" class="empty-state">No hay pedidos registrados.</td></tr>@endforelse</tbody></table></div>@endif
@endsection
