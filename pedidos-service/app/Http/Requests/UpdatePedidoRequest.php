<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePedidoRequest extends FormRequest
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
            'estado' => ['sometimes', 'required', 'string', 'in:pendiente,pagado,cancelado,completado'],
            'metodo_pago' => ['sometimes', 'required', 'string', 'in:efectivo,qr,tarjeta'],
            'fecha' => ['sometimes', 'required', 'date'],
            'cliente_id' => ['prohibited'],
            'detalles' => ['prohibited'],
            'subtotal' => ['prohibited'],
            'total' => ['prohibited'],
        ];
    }
}
