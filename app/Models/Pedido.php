<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_sucursal',
        'id_producto',
        'cantidad',
        'nombre_solicitante',
        'numero_solicitante',
        'completado',
    ];

    protected $casts = [
        'completado' => 'boolean',
    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursales::class, 'id_sucursal');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto')->withTrashed();
    }
}
