<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = ['event_id', 'pedido_id', 'amount', 'reference', 'status'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }
}
