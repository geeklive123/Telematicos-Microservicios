<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'integer', 'min:1'],
            'estado' => ['required', 'string', 'in:pendiente,pagado,completado'],
            'metodo_pago' => ['required', 'string', 'in:efectivo,qr,tarjeta'],
            'fecha' => ['required', 'date'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'integer', 'min:1'],
            'detalles.*.cantidad' => ['required', 'integer', 'min:1'],
        ];
    }
}
