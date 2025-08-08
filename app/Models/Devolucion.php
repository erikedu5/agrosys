<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Devolucion extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_venta', 'id_usuario', 'id_sucursal', 'total_devuelto', 'observaciones'
    ];

    public function detalles()
    {
        return $this->hasMany(DevolucionDetalle::class, 'id_devolucion');
    }
}

