@extends('layouts.app')

@section('title', 'Pedidos')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">GestiÃ³n comercial</p>
            <h1>Pedidos</h1>
            <p class="muted">Consulte y administre los pedidos registrados.</p>
        </div>
        <a class="button button-primary" href="{{ route('pedidos.create') }}">Crear pedido</a>
    </div>

    <section class="card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>MÃ©todo de pago</th>
                        <th>Detalles</th>
                        <th class="actions-column">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pedidos as $pedido)
                        <tr>
                            <td><strong>#{{ $pedido->id }}</strong></td>
                            <td>{{ $pedido->cliente_id }}</td>
                            <td>{{ $pedido->fecha->format('d/m/Y H:i') }}</td>
                            <td>Bs {{ number_format((float) $pedido->total, 2, ',', '.') }}</td>
                            <td><span class="badge badge-{{ $pedido->estado }}">{{ ucfirst($pedido->estado) }}</span></td>
                            <td>{{ ucfirst($pedido->metodo_pago) }}</td>
                            <td>{{ $pedido->detalles_count }}</td>
                            <td>
                                <div class="table-actions">
                                    <a class="link" href="{{ route('pedidos.show', $pedido) }}">Ver detalle</a>
                                    <a class="link" href="{{ route('pedidos.edit', $pedido) }}">Editar estado</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty-state">AÃºn no hay pedidos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($pedidos->hasPages())
            <nav class="pagination" aria-label="PaginaciÃ³n de pedidos">
                <span>PÃ¡gina {{ $pedidos->currentPage() }} de {{ $pedidos->lastPage() }}</span>
                <div>
                    @if ($pedidos->onFirstPage())
                        <span class="button button-secondary is-disabled">Anterior</span>
                    @else
                        <a class="button button-secondary" href="{{ $pedidos->previousPageUrl() }}">Anterior</a>
                    @endif

                    @if ($pedidos->hasMorePages())
                        <a class="button button-secondary" href="{{ $pedidos->nextPageUrl() }}">Siguiente</a>
                    @else
                        <span class="button button-secondary is-disabled">Siguiente</span>
                    @endif
                </div>
            </nav>
        @endif
    </section>
@endsection
