@extends('layouts.app')

@section('title', 'Marcas')

@section('content')
    <div class="page-heading">
        <div>
            <h2>Marcas</h2>
            <p class="muted">Administra las marcas disponibles para los productos.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h2>Nueva marca</h2>
            <form method="POST" action="{{ route('marcas.store') }}">
                @csrf
                <div class="form-grid" style="margin-top: 18px">
                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input id="nombre" name="nombre" type="text" maxlength="100" value="{{ old('nombre') }}" required>
                        @error('nombre') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group checkbox-row">
                        <input name="activo" type="hidden" value="0">
                        <input id="activo" name="activo" type="checkbox" value="1" @checked((bool) old('activo', true))>
                        <label for="activo">Activa</label>
                    </div>
                </div>
                <div class="form-actions">
                    <button class="btn" type="submit">Crear marca</button>
                </div>
            </form>
        </div>
    </div>

    <div class="catalog-list">
        @forelse ($brands as $brand)
            <div class="catalog-item">
                <form class="catalog-form" method="POST" action="{{ route('marcas.update', $brand) }}">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="brand-name-{{ $brand->id }}">Nombre</label>
                        <input id="brand-name-{{ $brand->id }}" name="nombre" type="text" maxlength="100" value="{{ $brand->nombre }}" required>
                    </div>
                    <div class="form-group checkbox-row">
                        <input name="activo" type="hidden" value="0">
                        <input id="brand-active-{{ $brand->id }}" name="activo" type="checkbox" value="1" @checked($brand->activo)>
                        <label for="brand-active-{{ $brand->id }}">Activa</label>
                    </div>
                    <span class="usage-count">{{ $brand->products_count }} productos</span>
                    <button class="btn btn-secondary btn-sm" type="submit">Guardar</button>
                </form>
                <form method="POST" action="{{ route('marcas.destroy', $brand) }}" onsubmit="return confirm('¿Eliminar esta marca?')" style="margin-top: 10px">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
                </form>
            </div>
        @empty
            <div class="empty-state">No hay marcas registradas.</div>
        @endforelse
    </div>
@endsection
