@extends('layouts.app')
@section('title', 'Clientes')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Directorio</p><h1>Clientes</h1><p class="muted">Consulta la información de tus clientes.</p></div></div>
@if ($clientes === null)<div class="alert alert-error" role="alert">El servicio de clientes no está disponible temporalmente.</div>@else
<div class="card table-responsive"><table><thead><tr><th>Nombre</th><th>Apellido</th><th>Email</th><th>Teléfono</th><th>Dirección</th><th>Estado</th></tr></thead><tbody>@forelse ($clientes as $cliente)<tr><td>{{ $cliente['nombre'] ?? '—' }}</td><td>{{ $cliente['apellido'] ?? '—' }}</td><td>{{ $cliente['email'] ?? '—' }}</td><td>{{ $cliente['telefono'] ?? '—' }}</td><td>{{ $cliente['direccion'] ?? '—' }}</td><td><span class="badge {{ ($cliente['activo'] ?? false) ? 'badge-completado' : 'badge-cancelado' }}">{{ ($cliente['activo'] ?? false) ? 'Activo' : 'Inactivo' }}</span></td></tr>@empty<tr><td colspan="6" class="empty-state">No hay clientes registrados.</td></tr>@endforelse</tbody></table></div>@endif
@endsection
