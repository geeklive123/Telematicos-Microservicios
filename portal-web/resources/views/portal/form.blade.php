@extends('layouts.app')
@section('title', $item ? 'Editar' : 'Crear')
@section('content')
<div class="page-heading"><div><p class="eyebrow">{{ ucfirst($tipo) }}</p><h1>{{ $item ? 'Editar' : 'Crear' }} {{ $tipo === 'clientes' ? 'cliente' : 'producto' }}</h1></div><a class="button button-secondary" href="{{ route('portal.'.$tipo) }}">Volver</a></div>
<form class="card form-section narrow-form" method="POST" action="{{ $item ? route('portal.actualizar', [$tipo, $item['id']]) : route('portal.guardar', $tipo) }}">@csrf @if($item) @method('PUT') @endif
<div class="form-grid"><div class="field"><label for="nombre">Nombre</label><input id="nombre" name="nombre" value="{{ old('nombre', $item['nombre'] ?? '') }}" required></div>
@if($tipo === 'clientes')
@foreach(['apellido'=>'Apellido','email'=>'Email','telefono'=>'Teléfono','direccion'=>'Dirección'] as $key=>$label)<div class="field"><label for="{{ $key }}">{{ $label }}</label><input id="{{ $key }}" name="{{ $key }}" type="{{ $key === 'email' ? 'email' : 'text' }}" value="{{ old($key, $item[$key] ?? '') }}" @required(in_array($key, ['apellido','email']))></div>@endforeach
@else
<div class="field"><label for="descripcion">Descripción</label><input id="descripcion" name="descripcion" value="{{ old('descripcion', $item['descripcion'] ?? '') }}"></div>
@foreach(['categoria_id'=>'ID de categoría','marca_id'=>'ID de marca','precio'=>'Precio','stock'=>'Stock','sku'=>'SKU'] as $key=>$label)<div class="field"><label for="{{ $key }}">{{ $label }}</label><input id="{{ $key }}" name="{{ $key }}" type="{{ $key === 'sku' ? 'text' : 'number' }}" @if($key !== 'sku') min="{{ in_array($key, ['categoria_id','marca_id']) ? 1 : 0 }}" step="{{ $key === 'precio' ? '0.01' : '1' }}" @endif value="{{ old($key, $item[$key] ?? '') }}" @required(in_array($key, ['categoria_id','precio','stock']))></div>@endforeach
<p class="muted field-wide">Introduce los identificadores de categoría y marca existentes en Productos.</p>
@endif
<div class="field"><label for="activo">Estado</label><select id="activo" name="activo"><option value="1" @selected((string) old('activo', (int) ($item['activo'] ?? 1)) === '1')>Activo</option><option value="0" @selected((string) old('activo', (int) ($item['activo'] ?? 1)) === '0')>Inactivo</option></select></div></div>
<div class="form-actions"><button class="button button-primary">Guardar</button></div></form>
@endsection
