<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = ['event_id', 'pedido_id', 'cliente_id', 'message', 'status'];
}
