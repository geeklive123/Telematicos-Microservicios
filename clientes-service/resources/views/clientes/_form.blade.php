@php
    $isEditing = isset($cliente);
@endphp

<div class="form-grid">
    <div class="field">
        <label for="nombre">Nombre</label>
        <input id="nombre" name="nombre" type="text" maxlength="100" value="{{ old('nombre', $cliente->nombre ?? '') }}" required autofocus>
        @error('nombre')
            <span class="field-error">{{ $message }}</span>
        @enderror
    </div>

    <div class="field">
        <label for="apellido">Apellido</label>
        <input id="apellido" name="apellido" type="text" maxlength="100" value="{{ old('apellido', $cliente->apellido ?? '') }}" required>
        @error('apellido')
            <span class="field-error">{{ $message }}</span>
        @enderror
    </div>

    <div class="field field-full">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" maxlength="150" value="{{ old('email', $cliente->email ?? '') }}" required>
        @error('email')
            <span class="field-error">{{ $message }}</span>
        @enderror
    </div>

    <div class="field field-full">
        <label for="telefono">TelÃ©fono <span class="optional">Opcional</span></label>
        <input id="telefono" name="telefono" type="tel" maxlength="30" value="{{ old('telefono', $cliente->telefono ?? '') }}">
        @error('telefono')
            <span class="field-error">{{ $message }}</span>
        @enderror
    </div>

    <div class="field field-full">
        <label for="direccion">DirecciÃ³n <span class="optional">Opcional</span></label>
        <textarea id="direccion" name="direccion" rows="4">{{ old('direccion', $cliente->direccion ?? '') }}</textarea>
        @error('direccion')
            <span class="field-error">{{ $message }}</span>
        @enderror
    </div>

    <div class="field field-full">
        <input name="activo" type="hidden" value="0">
        <label class="switch-field" for="activo">
            <input id="activo" name="activo" type="checkbox" value="1" @checked((bool) old('activo', $cliente->activo ?? true))>
            <span>
                <strong>Cliente activo</strong>
                <small>Permite identificar clientes disponibles para operaciones futuras.</small>
            </span>
        </label>
        @error('activo')
            <span class="field-error">{{ $message }}</span>
        @enderror
    </div>
</div>

<div class="form-actions">
    <a class="button button-secondary" href="{{ route('clientes.index') }}">Cancelar</a>
    <button class="button button-primary" type="submit">{{ $isEditing ? 'Guardar cambios' : 'Crear cliente' }}</button>
</div>
