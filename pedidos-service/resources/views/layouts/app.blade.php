<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'AdministraciÃ³n de Pedidos') | Tienda PC</title>
    <link rel="stylesheet" href="{{ asset('css/pedidos.css') }}">
</head>
<body>
    <header class="site-header">
        <div class="container header-content">
            <a class="brand" href="{{ route('pedidos.index') }}">Tienda PC</a>
            <span class="header-subtitle">AdministraciÃ³n de Pedidos</span>
        </div>
    </header>

    <main class="container page-content">
        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-error" role="alert">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error" role="alert">
                <strong>Revise los datos ingresados:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container">Pedidos Service Â· Tienda de Componentes de PC</div>
    </footer>

    @stack('scripts')
</body>
</html>
