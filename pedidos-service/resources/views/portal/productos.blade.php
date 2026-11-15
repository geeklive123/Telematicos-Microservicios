@extends('layouts.app')
@section('title', 'Productos')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Catálogo</p><h1>Productos</h1><p class="muted">Consulta los productos y su disponibilidad.</p></div></div>
@if ($productos === null)<div class="alert alert-error" role="alert">El servicio de productos no está disponible temporalmente.</div>@else
<div class="card table-responsive"><table><thead><tr><th>Nombre</th><th>Categoría</th><th>Marca</th><th>Precio</th><th>Stock</th><th>SKU</th><th>Estado</th></tr></thead><tbody>
@forelse ($productos as $producto)
@php
$estado = !($producto['activo'] ?? false) ? 'Inactivo' : (($producto['stock'] ?? 0) <= 0 ? 'Sin stock' : (($producto['stock'] ?? 0) <= 5 ? 'Bajo stock' : 'Disponible'));
$categoria = data_get($producto, 'categoria.nombre', is_string($producto['categoria'] ?? null) ? $producto['categoria'] : '—');
$marca = data_get($producto, 'marca.nombre', is_string($producto['marca'] ?? null) ? $producto['marca'] : '—');
@endphp
<tr><td>{{ $producto['nombre'] ?? '—' }}</td><td>{{ $categoria }}</td><td>{{ $marca }}</td><td>Bs {{ number_format((float) ($producto['precio'] ?? 0), 2, ',', '.') }}</td><td>{{ $producto['stock'] ?? '—' }}</td><td>{{ $producto['sku'] ?? '—' }}</td><td><span class="badge {{ $estado === 'Disponible' ? 'badge-completado' : ($estado === 'Bajo stock' ? 'badge-pendiente' : 'badge-cancelado') }}">{{ $estado }}</span></td></tr>@empty<tr><td colspan="7" class="empty-state">No hay productos registrados.</td></tr>@endforelse</tbody></table></div>@endif
@endsection
