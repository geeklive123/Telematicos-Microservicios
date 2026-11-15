@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">Directorio</p>
            <h1>Clientes</h1>
            <p class="page-description">Consulta y administra los datos de tus clientes.</p>
        </div>
        <a class="button button-primary" href="{{ route('clientes.create') }}">Nuevo cliente</a>
    </div>

    <section class="summary-grid" aria-label="Resumen de clientes">
        <article class="summary-card">
            <span>Total de clientes</span>
            <strong>{{ $totalClientes }}</strong>
        </article>
        <article class="summary-card summary-card-active">
            <span>Clientes activos</span>
            <strong>{{ $clientesActivos }}</strong>
        </article>
        <article class="summary-card summary-card-inactive">
            <span>Clientes inactivos</span>
            <strong>{{ $clientesInactivos }}</strong>
        </article>
    </section>

    <section class="panel">
        <form class="filters" method="GET" action="{{ route('clientes.index') }}">
            <div class="field filter-search">
                <label for="buscar">Buscar cliente</label>
                <input id="buscar" name="buscar" type="search" value="{{ $search }}" placeholder="Nombre, apellido o email">
            </div>

            <div class="field">
                <label for="estado">Estado</label>
                <select id="estado" name="estado">
                    <option value="todos" @selected($status === 'todos')>Todos</option>
                    <option value="activos" @selected($status === 'activos')>Activos</option>
                    <option value="inactivos" @selected($status === 'inactivos')>Inactivos</option>
                </select>
            </div>

            <div class="filter-actions">
                <button class="button button-primary" type="submit">Aplicar filtros</button>
                <a class="button button-secondary" href="{{ route('clientes.index') }}">Limpiar</a>
            </div>
        </form>
    </section>

    <section class="panel table-panel">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Email</th>
                        <th>TelÃ©fono</th>
                        <th>DirecciÃ³n</th>
                        <th>Estado</th>
                        <th class="actions-column">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clientes as $cliente)
                        <tr>
                            <td class="cell-strong">{{ $cliente->nombre }}</td>
                            <td>{{ $cliente->apellido }}</td>
                            <td><a class="email-link" href="mailto:{{ $cliente->email }}">{{ $cliente->email }}</a></td>
                            <td>{{ $cliente->telefono ?: 'â€”' }}</td>
                            <td>{{ $cliente->direccion ?: 'â€”' }}</td>
                            <td>
                                <span class="badge {{ $cliente->activo ? 'badge-active' : 'badge-inactive' }}">
                                    {{ $cliente->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <a class="button button-small button-secondary" href="{{ route('clientes.edit', $cliente) }}">Editar</a>
                                    <form method="POST" action="{{ route('clientes.destroy', $cliente) }}" onsubmit="return confirm('Â¿Seguro que deseas eliminar este cliente?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="button button-small button-danger" type="submit">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="empty-state" colspan="7">No se encontraron clientes con los filtros seleccionados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('components.pagination', ['paginator' => $clientes])
    </section>
@endsection
