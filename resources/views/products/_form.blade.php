<div class="form-grid">
    <div class="form-group">
        <label for="nombre">Nombre</label>
        <input id="nombre" name="nombre" type="text" maxlength="150" value="{{ old('nombre', $product->nombre ?? '') }}" required>
        @error('nombre') <span class="field-error">{{ $message }}</span> @enderror
    </div>

    <div class="form-group">
        <label for="categoria_id">Categoría</label>
        <select id="categoria_id" name="categoria_id" required>
            <option value="">Seleccione categoría</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('categoria_id', $product->categoria_id ?? '') === (string) $category->id)>
                    {{ $category->nombre }}{{ $category->activo ? '' : ' (inactiva)' }}
                </option>
            @endforeach
        </select>
        @error('categoria_id') <span class="field-error">{{ $message }}</span> @enderror
    </div>

    <div class="form-group">
        <label for="marca_id">Marca</label>
        <select id="marca_id" name="marca_id">
            <option value="">Seleccione marca</option>
            @foreach ($brands as $brand)
                <option value="{{ $brand->id }}" @selected((string) old('marca_id', $product->marca_id ?? '') === (string) $brand->id)>
                    {{ $brand->nombre }}{{ $brand->activo ? '' : ' (inactiva)' }}
                </option>
            @endforeach
        </select>
        @error('marca_id') <span class="field-error">{{ $message }}</span> @enderror
    </div>

    <div class="form-group">
        <label for="sku">SKU</label>
        <input id="sku" name="sku" type="text" maxlength="100" value="{{ old('sku', $product->sku ?? '') }}">
        @error('sku') <span class="field-error">{{ $message }}</span> @enderror
    </div>

    <div class="form-group">
        <label for="precio">Precio</label>
        <input id="precio" name="precio" type="number" min="0" step="0.01" value="{{ old('precio', $product->precio ?? 0) }}" required>
        @error('precio') <span class="field-error">{{ $message }}</span> @enderror
    </div>

    <div class="form-group">
        <label for="stock">Stock</label>
        <input id="stock" name="stock" type="number" min="0" step="1" value="{{ old('stock', $product->stock ?? 0) }}" required>
        @error('stock') <span class="field-error">{{ $message }}</span> @enderror
    </div>

    <div class="form-group form-group-full">
        <label for="descripcion">Descripción</label>
        <textarea id="descripcion" name="descripcion">{{ old('descripcion', $product->descripcion ?? '') }}</textarea>
        @error('descripcion') <span class="field-error">{{ $message }}</span> @enderror
    </div>

    <div class="form-group form-group-full checkbox-row">
        <input name="activo" type="hidden" value="0">
        <input id="activo" name="activo" type="checkbox" value="1" @checked((bool) old('activo', $product->activo ?? true))>
        <label for="activo">Producto activo</label>
        @error('activo') <span class="field-error">{{ $message }}</span> @enderror
    </div>
</div>

<div class="form-actions">
    <button class="btn" type="submit">{{ $submitLabel }}</button>
    <a class="btn btn-secondary" href="{{ route('productos.index') }}">Cancelar</a>
</div>
