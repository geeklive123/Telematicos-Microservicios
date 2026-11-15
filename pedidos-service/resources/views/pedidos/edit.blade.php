@extends('layouts.app')

@section('title', 'Editar pedido #'.$pedido->id)

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">Actualización</p>
            <h1>Editar pedido #{{ $pedido->id }}</h1>
            <p class="muted">Solo puede modificar el estado, método de pago y fecha.</p>
        </div>
        <a class="button button-secondary" href="{{ route('pedidos.show', $pedido) }}">Volver al detalle</a>
    </div>

    <form method="POST" action="{{ route('pedidos.update', $pedido) }}" class="card form-section narrow-form">
        @csrf
        @method('PUT')

        <div class="readonly-note">
            <span>Cliente ID</span>
            <strong>{{ $pedido->cliente_id }}</strong>
            <span>Total</span>
            <strong>Bs {{ number_format((float) $pedido->total, 2, ',', '.') }}</strong>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="estado">Estado</label>
                <select id="estado" name="estado" required>
                    @foreach ($pedido->estadosPermitidos() as $estado)
                        <option value="{{ $estado }}" @selected(old('estado', $pedido->estado) === $estado)>{{ ucfirst($estado) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="metodo_pago">Método de pago</label>
                <select id="metodo_pago" name="metodo_pago" required>
                    @foreach (['efectivo', 'qr', 'tarjeta'] as $metodo)
                        <option value="{{ $metodo }}" @selected(old('metodo_pago', $pedido->metodo_pago) === $metodo)>{{ ucfirst($metodo) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field field-wide">
                <label for="fecha">Fecha</label>
                <input id="fecha" type="datetime-local" name="fecha" value="{{ old('fecha', $pedido->fecha->format('Y-m-d\TH:i')) }}" required>
            </div>
        </div>

        <div class="form-actions">
            <a class="button button-secondary" href="{{ route('pedidos.show', $pedido) }}">Cancelar</a>
            <button class="button button-primary" type="submit">Guardar cambios</button>
        </div>
    </form>
@endsection
