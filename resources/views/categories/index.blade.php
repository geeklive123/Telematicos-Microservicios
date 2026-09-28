@extends('layouts.app')

@section('title', 'Categorías')

@section('content')
    <div class="page-heading">
        <div>
            <h2>Categorías</h2>
            <p class="muted">Administra las categorías disponibles para los productos.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h2>Nueva categoría</h2>
            <form method="POST" action="{{ route('categorias.store') }}">
                @csrf
                <div class="form-grid" style="margin-top: 18px">
                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input id="nombre" name="nombre" type="text" maxlength="100" value="{{ old('nombre') }}" required>
                        @error('nombre') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <input id="descripcion" name="descripcion" type="text" value="{{ old('descripcion') }}">
                        @error('descripcion') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group form-group-full checkbox-row">
                        <input name="activo" type="hidden" value="0">
                        <input id="activo" name="activo" type="checkbox" value="1" @checked((bool) old('activo', true))>
                        <label for="activo">Activa</label>
                    </div>
                </div>
                <div class="form-actions">
                    <button class="btn" type="submit">Crear categoría</button>
                </div>
            </form>
        </div>
    </div>

    <div class="catalog-list">
        @forelse ($categories as $category)
            <div class="catalog-item">
                <form class="catalog-form" method="POST" action="{{ route('categorias.update', $category) }}">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="category-name-{{ $category->id }}">Nombre</label>
                        <input id="category-name-{{ $category->id }}" name="nombre" type="text" maxlength="100" value="{{ $category->nombre }}" required>
                    </div>
                    <div class="form-group">
                        <label for="category-description-{{ $category->id }}">Descripción</label>
                        <input id="category-description-{{ $category->id }}" name="descripcion" type="text" value="{{ $category->descripcion }}">
                    </div>
                    <div class="form-group checkbox-row">
                        <input name="activo" type="hidden" value="0">
                        <input id="category-active-{{ $category->id }}" name="activo" type="checkbox" value="1" @checked($category->activo)>
                        <label for="category-active-{{ $category->id }}">Activa</label>
                    </div>
                    <div class="catalog-actions">
                        <span class="usage-count">{{ $category->products_count }} productos</span>
                        <button class="btn btn-secondary btn-sm" type="submit">Guardar</button>
                    </div>
                </form>
                <form method="POST" action="{{ route('categorias.destroy', $category) }}" onsubmit="return confirm('¿Eliminar esta categoría?')" style="margin-top: 10px">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
                </form>
            </div>
        @empty
            <div class="empty-state">No hay categorías registradas.</div>
        @endforelse
    </div>
@endsection
