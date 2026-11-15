@extends('layouts.app')
@section('title', ucfirst($tipo))
@section('content')
<div class="page-heading"><div><p class="eyebrow">Administración</p><h1>{{ ucfirst($tipo) }}</h1><p class="muted">Consulta y administra tus registros.</p></div><a class="button button-primary" href="{{ route('portal.crear', $tipo) }}">Crear {{ $tipo === 'clientes' ? 'cliente' : 'producto' }}</a></div>
@if ($items === null)<div class="alert alert-error">Servicio temporalmente no disponible</div>@else
<div class="card table-responsive"><table><thead><tr><th>Nombre</th>@if($tipo === 'clientes')<th>Apellido</th><th>Email</th><th>Teléfono</th><th>Dirección</th>@else<th>Categoría</th><th>Marca</th><th>Precio</th><th>Stock</th><th>SKU</th>@endif<th>Estado</th><th>Acciones</th></tr></thead><tbody>
@forelse($items as $item)<tr><td>{{ $item['nombre'] ?? '—' }}</td>@if($tipo === 'clientes')<td>{{ $item['apellido'] ?? '—' }}</td><td>{{ $item['email'] ?? '—' }}</td><td>{{ $item['telefono'] ?? '—' }}</td><td>{{ $item['direccion'] ?? '—' }}</td>@else<td>{{ data_get($item, 'categoria.nombre', '—') }}</td><td>{{ data_get($item, 'marca.nombre', '—') }}</td><td>Bs {{ number_format((float) ($item['precio'] ?? 0), 2, ',', '.') }}</td><td>{{ $item['stock'] ?? '—' }}</td><td>{{ $item['sku'] ?? '—' }}</td>@endif<td><span class="badge {{ ($item['activo'] ?? false) ? 'badge-completado' : 'badge-cancelado' }}">{{ ($item['activo'] ?? false) ? 'Activo' : 'Inactivo' }}</span></td><td><a class="link" href="{{ route('portal.editar', [$tipo, $item['id']]) }}">Editar</a></td></tr>@empty<tr><td class="empty-state" colspan="8">No hay registros.</td></tr>@endforelse
</tbody></table></div>@endif
@endsection
