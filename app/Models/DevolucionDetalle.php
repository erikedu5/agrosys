<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DevolucionDetalle extends Model
{
    use HasFactory;

    /**
     * Explicitly map the model to the correct table name.
     */
    protected $table = 'devolucion_detalles';

    protected $fillable = [
        'id_devolucion', 'id_producto', 'cantidad', 'total'
    ];
}

