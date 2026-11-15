@extends('layouts.app')

@section('title', 'Productos')

@section('content')
    <div class="page-heading">
        <div>
            <h2>Productos</h2>
            <p class="muted">Gestiona el catálogo, precios y disponibilidad.</p>
        </div>
        <a class="btn" href="{{ route('productos.create') }}">Nuevo producto</a>
    </div>

    <section class="summary-grid" aria-label="Resumen de productos">
        <div class="summary-card">
            <span class="summary-label">Total de productos</span>
            <strong class="summary-value">{{ $summary->total ?? 0 }}</strong>
        </div>
        <div class="summary-card">
            <span class="summary-label">Productos activos</span>
            <strong class="summary-value">{{ $summary->activos ?? 0 }}</strong>
        </div>
        <div class="summary-card">
            <span class="summary-label">Sin stock</span>
            <strong class="summary-value">{{ $summary->sin_stock ?? 0 }}</strong>
        </div>
        <div class="summary-card">
            <span class="summary-label">Bajo stock</span>
            <strong class="summary-value">{{ $summary->bajo_stock ?? 0 }}</strong>
        </div>
    </section>

    <section class="card filters" aria-label="Filtros de productos">
        <div class="card-body">
            <form method="GET" action="{{ route('productos.index') }}">
                <div class="filter-grid">
                    <div class="form-group">
                        <label for="q">Buscar</label>
                        <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Nombre o SKU">
                    </div>

                    <div class="form-group">
                        <label for="categoria_id">Categoría</label>
                        <select id="categoria_id" name="categoria_id">
                            <option value="">Todas</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) request('categoria_id') === (string) $category->id)>{{ $category->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="marca_id">Marca</label>
                        <select id="marca_id" name="marca_id">
                            <option value="">Todas</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" @selected((string) request('marca_id') === (string) $brand->id)>{{ $brand->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado">
                            <option value="">Todos</option>
                            <option value="activo" @selected(request('estado') === 'activo')>Activos</option>
                            <option value="inactivo" @selected(request('estado') === 'inactivo')>Inactivos</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="stock">Stock</label>
                        <select id="stock" name="stock">
                            <option value="">Todos</option>
                            <option value="con_stock" @selected(request('stock') === 'con_stock')>Con stock</option>
                            <option value="sin_stock" @selected(request('stock') === 'sin_stock')>Sin stock</option>
                            <option value="bajo_stock" @selected(request('stock') === 'bajo_stock')>Bajo stock</option>
                        </select>
                    </div>
                </div>

                <div class="filter-actions">
                    <button class="btn" type="submit">Aplicar filtros</button>
                    <a class="btn btn-secondary" href="{{ route('productos.index') }}">Limpiar</a>
                </div>
            </form>
        </div>
    </section>

    <div class="card">
        @if ($products->isEmpty())
            <div class="empty-state">
                <strong>No se encontraron productos.</strong>
                <p>Prueba con otros criterios o limpia los filtros.</p>
            </div>
        @else
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Marca</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>SKU</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            <tr>
                                <td><strong>{{ $product->nombre }}</strong></td>
                                <td>{{ $product->category->nombre }}</td>
                                <td>{{ $product->brand?->nombre ?: '—' }}</td>
                                <td>Bs {{ number_format((float) $product->precio, 2, '.', ',') }}</td>
                                <td>
                                    @if ($product->stock === 0)
                                        <span class="stock-empty">Sin stock</span>
                                    @elseif ($product->stock <= 5)
                                        <span class="stock-low">{{ $product->stock }} · Bajo stock</span>
                                    @else
                                        <span class="stock-ok">{{ $product->stock }} · Disponible</span>
                                    @endif
                                </td>
                                <td>{{ $product->sku ?: '—' }}</td>
                                <td>
                                    <span class="badge {{ $product->activo ? 'badge-success' : 'badge-muted' }}">
                                        {{ $product->activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a class="btn btn-secondary btn-sm" href="{{ route('productos.edit', $product) }}">Editar</a>
                                        <form method="POST" action="{{ route('productos.destroy', $product) }}" onsubmit="return confirm('¿Confirmas que deseas eliminar este producto?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @include('products._pagination')
        @endif
    </div>
@endsection
