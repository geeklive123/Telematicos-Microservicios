<tr class="product-row" data-subtotal="0">
    <td>
        <select class="producto-select" name="detalles[{{ $index }}][producto_id]" required>
            <option value="" data-price="0" data-stock="0">Seleccione producto</option>
            @foreach ($productos as $producto)
                <option
                    value="{{ $producto['id'] }}"
                    data-price="{{ (float) $producto['precio'] }}"
                    data-stock="{{ (int) $producto['stock'] }}"
                    @selected((string) ($detalle['producto_id'] ?? '') === (string) $producto['id'])
                    @disabled((int) $producto['stock'] <= 0)
                >
                    {{ $producto['nombre'] }} - Bs {{ number_format((float) $producto['precio'], 2, ',', '.') }} - Stock: {{ (int) $producto['stock'] }}{{ (int) $producto['stock'] <= 0 ? ' (sin stock)' : '' }}
                </option>
            @endforeach
        </select>
    </td>
    <td class="precio-value">Bs 0,00</td>
    <td>
        <input class="cantidad-input quantity" type="number" name="detalles[{{ $index }}][cantidad]" min="1" value="{{ $detalle['cantidad'] ?? 1 }}" required>
    </td>
    <td><strong class="subtotal-value">Bs 0,00</strong></td>
    <td><button class="remove-row icon-button" type="button" aria-label="Eliminar producto">Ã—</button></td>
</tr>
