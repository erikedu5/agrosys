<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferenciaDetalle extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_transferencia',
        'id_producto',
        'cantidad',
        'lote_origen_id'
    ];

    public function transferencia()
    {
        return $this->belongsTo(Transferencia::class, 'id_transferencia');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto');
    }

    public function loteOrigen()
    {
        return $this->belongsTo(ComprasProductos::class, 'lote_origen_id');
    }
}
