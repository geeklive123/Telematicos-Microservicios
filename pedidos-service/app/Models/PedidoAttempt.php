<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoAttempt extends Model
{
    protected $fillable = [
        'attempt_uuid', 'cliente_id', 'status', 'error_code', 'error_message', 'pedido_id',
        'payload', 'resolved_details', 'stock_operations', 'claimed',
    ];

    protected function casts(): array
    {
        return ['payload' => 'array', 'resolved_details' => 'array', 'stock_operations' => 'array', 'claimed' => 'boolean'];
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }
}
