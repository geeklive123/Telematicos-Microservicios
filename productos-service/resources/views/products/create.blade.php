@extends('layouts.app')

@section('title', 'Nuevo producto')

@section('content')
    <div class="page-heading">
        <div>
            <h2>Nuevo producto</h2>
            <p class="muted">Completa la información para agregarlo al catálogo.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('productos.store') }}">
                @csrf
                @include('products._form', ['submitLabel' => 'Crear producto'])
            </form>
        </div>
    </div>
@endsection
