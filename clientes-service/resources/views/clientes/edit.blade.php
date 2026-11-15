@extends('layouts.app')

@section('title', 'Editar cliente')

@section('content')
    <div class="page-heading compact-heading">
        <div>
            <p class="eyebrow">Clientes</p>
            <h1>Editar cliente</h1>
            <p class="page-description">Actualiza la informaciÃ³n de {{ $cliente->nombre }} {{ $cliente->apellido }}.</p>
        </div>
    </div>

    <section class="panel form-panel">
        <form method="POST" action="{{ route('clientes.update', $cliente) }}">
            @csrf
            @method('PUT')
            @include('clientes._form')
        </form>
    </section>
@endsection
