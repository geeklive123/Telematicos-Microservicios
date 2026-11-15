@extends('layouts.app')

@section('title', 'Crear pedido')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">Nuevo registro</p>
            <h1>Crear pedido</h1>
            <p class="muted">Seleccione el cliente y agregue uno o mÃ¡s productos.</p>
        </div>
        <a class="button button-secondary" href="{{ route('pedidos.index') }}">Volver al listado</a>
    </div>

    <form method="POST" action="{{ route('pedidos.store') }}" id="pedido-form">
        @csrf
        <input type="hidden" name="estado" value="pendiente">

        <section class="card form-section">
            <div class="section-heading">
                <span class="step">1</span>
                <div>
                    <h2>Datos del pedido</h2>
                    <p class="muted">La fecha, el cliente y la forma de pago de la venta.</p>
                </div>
            </div>

            <div class="form-grid">
                <div class="field field-wide">
                    <label for="cliente_id">Cliente</label>
                    <select id="cliente_id" name="cliente_id" required @disabled($clientes === [])>
                        <option value="">Seleccione cliente</option>
                        @foreach ($clientes as $cliente)
                            <option value="{{ $cliente['id'] }}" @selected((string) old('cliente_id') === (string) $cliente['id'])>
                                {{ trim(($cliente['nombre'] ?? '').' '.($cliente['apellido'] ?? '')) }} - {{ $cliente['email'] ?? 'Sin email' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="fecha">Fecha</label>
                    <input id="fecha" type="datetime-local" name="fecha" value="{{ old('fecha', now()->format('Y-m-d\TH:i')) }}" required>
                </div>

                <div class="field">
                    <label for="metodo_pago">MÃ©todo de pago</label>
                    <select id="metodo_pago" name="metodo_pago" required>
                        <option value="efectivo" @selected(old('metodo_pago', 'efectivo') === 'efectivo')>Efectivo</option>
                        <option value="qr" @selected(old('metodo_pago') === 'qr')>QR</option>
                        <option value="tarjeta" @selected(old('metodo_pago') === 'tarjeta')>Tarjeta</option>
                    </select>
                </div>
            </div>
        </section>

        <section class="card form-section">
            <div class="section-heading section-heading-actions">
                <div class="section-heading-copy">
                    <span class="step">2</span>
                    <div>
                        <h2>Productos</h2>
                        <p class="muted">Los precios mostrados son referenciales y se validarÃ¡n al guardar.</p>
                    </div>
                </div>
                <button class="button button-secondary" type="button" id="agregar-producto" @disabled($productos === [])>Agregar producto</button>
            </div>

            <div class="table-responsive">
                <table class="product-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Precio</th>
                            <th>Cantidad</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="detalle-rows">
                        @foreach (old('detalles', [['producto_id' => '', 'cantidad' => 1]]) as $index => $detalle)
                            @include('pedidos.partials.product-row', ['index' => $index, 'detalle' => $detalle])
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="order-total">
                <span>Total visual</span>
                <strong id="total-general">Bs 0,00</strong>
            </div>
        </section>

        <div class="form-actions">
            <a class="button button-secondary" href="{{ route('pedidos.index') }}">Cancelar</a>
            <button class="button button-primary" type="submit" @disabled($clientes === [] || $productos === [])>Guardar pedido</button>
        </div>
    </form>

    <template id="producto-row-template">
        @include('pedidos.partials.product-row', ['index' => '__INDEX__', 'detalle' => ['producto_id' => '', 'cantidad' => 1]])
    </template>
@endsection

@push('scripts')
    <script>
        (() => {
            const rows = document.getElementById('detalle-rows');
            const template = document.getElementById('producto-row-template');
            const addButton = document.getElementById('agregar-producto');
            const totalElement = document.getElementById('total-general');
            let nextIndex = {{ count(old('detalles', [['producto_id' => '', 'cantidad' => 1]])) }};

            const money = value => new Intl.NumberFormat('es-BO', {
                style: 'currency', currency: 'BOB', minimumFractionDigits: 2
            }).format(value).replace('BOB', 'Bs');

            function updateRow(row) {
                const select = row.querySelector('.producto-select');
                const quantity = row.querySelector('.cantidad-input');
                const option = select.options[select.selectedIndex];
                const price = Number(option?.dataset.price || 0);
                const stock = Number(option?.dataset.stock || 0);
                const amount = Math.max(0, Number(quantity.value || 0));

                quantity.max = stock > 0 ? stock : '';
                row.querySelector('.precio-value').textContent = money(price);
                row.querySelector('.subtotal-value').textContent = money(price * amount);
                row.dataset.subtotal = String(price * amount);
                updateTotal();
            }

            function updateTotal() {
                const total = [...rows.querySelectorAll('.product-row')]
                    .reduce((sum, row) => sum + Number(row.dataset.subtotal || 0), 0);
                totalElement.textContent = money(total);
            }

            function bindRow(row) {
                row.querySelector('.producto-select').addEventListener('change', () => updateRow(row));
                row.querySelector('.cantidad-input').addEventListener('input', () => updateRow(row));
                row.querySelector('.remove-row').addEventListener('click', () => {
                    if (rows.children.length > 1) {
                        row.remove();
                        updateTotal();
                    }
                });
                updateRow(row);
            }

            rows.querySelectorAll('.product-row').forEach(bindRow);

            addButton?.addEventListener('click', () => {
                const wrapper = document.createElement('tbody');
                wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(nextIndex++));
                const row = wrapper.firstElementChild;
                rows.appendChild(row);
                bindRow(row);
            });
        })();
    </script>
@endpush
