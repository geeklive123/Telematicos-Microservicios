@extends('layouts.app')

@section('title', 'Editar producto')

@section('content')
    <div class="page-heading">
        <div>
            <h2>Editar producto</h2>
            <p class="muted">Actualiza la información de {{ $product->nombre }}.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('productos.update', $product) }}">
                @csrf
                @method('PUT')
                @include('products._form', ['submitLabel' => 'Guardar cambios'])
            </form>
        </div>
    </div>
@endsection
