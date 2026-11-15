<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'cliente_id',
        'subtotal',
        'total',
        'estado',
        'metodo_pago',
        'fecha',
    ];

    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePedido::class);
    }

    /** @return list<string> */
    public function estadosPermitidos(): array
    {
        return match ($this->estado) {
            'pendiente' => ['pendiente', 'pagado', 'cancelado'],
            'pagado' => ['pagado', 'completado', 'cancelado'],
            'completado' => ['completado'],
            'cancelado' => ['cancelado'],
            default => [],
        };
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'fecha' => 'datetime',
        ];
    }
}
