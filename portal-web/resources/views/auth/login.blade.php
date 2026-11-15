@extends('layouts.app')

@section('title', 'Acceso al sistema')

@section('content')
    <section class="login-card card" aria-labelledby="login-title">
        <div class="login-intro">
            <p class="eyebrow">Tienda PC</p>
            <h1 id="login-title">Acceso al sistema</h1>
            <p class="muted">Ingrese sus credenciales para administrar los pedidos.</p>
        </div>
        <form method="POST" action="{{ route('login.submit') }}" class="login-form">
            @csrf
            <div class="field">
                <label for="email">Correo electrónico</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
            </div>
            <div class="field">
                <label for="password">Contraseña</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </div>
            <button type="submit" class="button button-primary">Iniciar sesión</button>
        </form>
    </section>
@endsection
