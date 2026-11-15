@extends('layouts.app')

@section('title', 'Nuevo cliente')

@section('content')
    <div class="page-heading compact-heading">
        <div>
            <p class="eyebrow">Clientes</p>
            <h1>Nuevo cliente</h1>
            <p class="page-description">Completa los datos para registrar un cliente.</p>
        </div>
    </div>

    <section class="panel form-panel">
        <form method="POST" action="{{ route('clientes.store') }}">
            @csrf
            @include('clientes._form')
        </form>
    </section>
@endsection
