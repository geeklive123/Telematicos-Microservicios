<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOperation extends Model
{
    protected $fillable = [
        'idempotency_key', 'product_id', 'type', 'quantity', 'stock_before',
        'stock_after', 'status', 'source_type', 'source_reference',
    ];

    protected function casts(): array
    {
        return ['product_id' => 'integer', 'quantity' => 'integer', 'stock_before' => 'integer', 'stock_after' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
