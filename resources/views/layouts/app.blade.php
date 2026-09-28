<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Productos') | Tienda PC</title>
    <style>
        :root {
            --navy: #17324d;
            --navy-dark: #10263a;
            --blue: #246b9e;
            --gray-50: #f7f9fb;
            --gray-100: #edf1f5;
            --gray-300: #cbd5df;
            --gray-600: #5d6b78;
            --gray-800: #27333f;
            --white: #ffffff;
            --green: #18794e;
            --green-soft: #def7e9;
            --red: #b42318;
            --red-soft: #fee4e2;
            --amber: #a15c00;
            --amber-soft: #fff1cf;
            --shadow: 0 12px 30px rgba(16, 38, 58, 0.08);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            color: var(--gray-800);
            background: var(--gray-50);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            line-height: 1.5;
        }

        .site-header {
            color: var(--white);
            background: linear-gradient(135deg, var(--navy-dark), var(--navy));
            border-bottom: 4px solid var(--blue);
        }

        .header-inner, .container {
            width: min(1120px, calc(100% - 32px));
            margin: 0 auto;
        }

        .header-inner { padding: 28px 0; }
        .brand { margin: 0; font-size: 1.7rem; letter-spacing: -0.02em; }
        .subtitle { margin: 3px 0 0; color: #c8d8e7; font-size: 0.98rem; }
        .main-nav { display: flex; flex-wrap: wrap; gap: 18px; margin-top: 18px; }
        .main-nav a { color: #dce8f2; font-weight: 650; text-decoration: none; }
        .main-nav a:hover { color: var(--white); }
        .container { padding: 34px 0 48px; }

        .page-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 20px;
        }

        h2 { margin: 0; color: var(--navy); font-size: 1.55rem; }
        .muted { margin: 4px 0 0; color: var(--gray-600); }

        .card {
            overflow: hidden;
            background: var(--white);
            border: 1px solid var(--gray-100);
            border-radius: 12px;
            box-shadow: var(--shadow);
        }

        .card-body { padding: 26px; }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 840px; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid var(--gray-100); }
        th { color: var(--navy); background: #f3f6f9; font-size: 0.78rem; letter-spacing: 0.04em; text-transform: uppercase; }
        tbody tr:hover { background: #fafcfd; }
        tbody tr:last-child td { border-bottom: 0; }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 9px 15px;
            color: var(--white);
            background: var(--blue);
            border: 1px solid transparent;
            border-radius: 7px;
            cursor: pointer;
            font: inherit;
            font-weight: 650;
            text-decoration: none;
        }

        .btn:hover { filter: brightness(0.94); }
        .btn-secondary { color: var(--navy); background: var(--white); border-color: var(--gray-300); }
        .btn-danger { background: var(--red); }
        .btn-sm { min-height: 34px; padding: 6px 10px; font-size: 0.88rem; }
        .actions { display: flex; align-items: center; gap: 8px; }
        .actions form { margin: 0; }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 9px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .badge-success { color: var(--green); background: var(--green-soft); }
        .badge-muted { color: var(--gray-600); background: var(--gray-100); }
        .stock-ok { color: var(--green); font-weight: 700; }
        .stock-low { color: var(--amber); font-weight: 700; }
        .stock-empty { color: var(--red); font-weight: 700; }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }

        .summary-card {
            padding: 18px;
            background: var(--white);
            border: 1px solid var(--gray-100);
            border-left: 4px solid var(--blue);
            border-radius: 10px;
            box-shadow: var(--shadow);
        }

        .summary-label { color: var(--gray-600); font-size: 0.86rem; font-weight: 650; }
        .summary-value { display: block; margin-top: 4px; color: var(--navy); font-size: 1.75rem; font-weight: 750; }
        .filters { margin-bottom: 20px; }
        .filter-grid { display: grid; grid-template-columns: 2fr repeat(4, minmax(130px, 1fr)); gap: 14px; align-items: end; }
        .filter-actions { display: flex; gap: 10px; margin-top: 18px; }
        select {
            width: 100%;
            padding: 10px 12px;
            color: var(--gray-800);
            background: var(--white);
            border: 1px solid var(--gray-300);
            border-radius: 7px;
            font: inherit;
        }
        select:focus { outline: 3px solid rgba(36, 107, 158, 0.15); border-color: var(--blue); }

        .pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 16px;
            border-top: 1px solid var(--gray-100);
        }
        .pagination-info { color: var(--gray-600); font-size: 0.9rem; }
        .pagination-links { display: flex; flex-wrap: wrap; gap: 6px; }
        .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            min-height: 36px;
            padding: 6px 10px;
            color: var(--navy);
            background: var(--white);
            border: 1px solid var(--gray-300);
            border-radius: 6px;
            text-decoration: none;
        }
        .page-link:hover { border-color: var(--blue); }
        .page-link-active { color: var(--white); background: var(--blue); border-color: var(--blue); }
        .page-link-disabled { color: #9aa5af; background: var(--gray-50); }

        .alert {
            margin-bottom: 20px;
            padding: 13px 16px;
            border: 1px solid #a8dfc3;
            border-radius: 8px;
            color: var(--green);
            background: var(--green-soft);
        }
        .alert-error { color: var(--red); background: var(--red-soft); border-color: #f3aaa5; }

        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 7px; }
        .form-group-full { grid-column: 1 / -1; }
        label { color: var(--navy); font-weight: 650; }
        input, textarea {
            width: 100%;
            padding: 10px 12px;
            color: var(--gray-800);
            background: var(--white);
            border: 1px solid var(--gray-300);
            border-radius: 7px;
            font: inherit;
        }
        input:focus, textarea:focus { outline: 3px solid rgba(36, 107, 158, 0.15); border-color: var(--blue); }
        textarea { min-height: 110px; resize: vertical; }
        .checkbox-row { flex-direction: row; align-items: center; gap: 10px; }
        .checkbox-row input { width: 18px; height: 18px; }
        .field-error { color: var(--red); font-size: 0.86rem; }
        .form-actions { display: flex; gap: 10px; margin-top: 26px; padding-top: 20px; border-top: 1px solid var(--gray-100); }
        .empty-state { padding: 54px 20px; color: var(--gray-600); text-align: center; }
        .catalog-list { display: grid; gap: 14px; margin-top: 20px; }
        .catalog-item { padding: 18px; background: var(--white); border: 1px solid var(--gray-100); border-radius: 10px; box-shadow: var(--shadow); }
        .catalog-form { display: grid; grid-template-columns: 1.4fr 2fr auto auto; align-items: end; gap: 14px; }
        .catalog-actions { display: flex; gap: 8px; align-items: center; }
        .usage-count { color: var(--gray-600); font-size: 0.86rem; white-space: nowrap; }

        @media (max-width: 720px) {
            .page-heading { align-items: flex-start; flex-direction: column; }
            .summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .filter-grid { grid-template-columns: 1fr; }
            .pagination { align-items: flex-start; flex-direction: column; }
            .catalog-form { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr; }
            .form-group-full { grid-column: auto; }
            .card-body { padding: 20px; }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <h1 class="brand">Tienda PC</h1>
            <p class="subtitle">Administración de Productos</p>
            <nav class="main-nav" aria-label="Navegación principal">
                <a href="{{ route('productos.index') }}">Productos</a>
                <a href="{{ route('categorias.index') }}">Categorías</a>
                <a href="{{ route('marcas.index') }}">Marcas</a>
            </nav>
        </div>
    </header>

    <main class="container">
        @if (session('success'))
            <div class="alert" role="status">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-error" role="alert">{{ session('error') }}</div>
        @endif

        @yield('content')
    </main>
</body>
</html>
