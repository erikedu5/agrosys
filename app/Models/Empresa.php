<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Empresa extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'email',
        'rfc',
        'aviso',
        'numero_sucursales',
        'mostrar_campos_precio',
        'ventas_bloqueadas',
        'motivo_bloqueo',
    ];

    protected $casts = [
        'mostrar_campos_precio' => 'boolean',
        'ventas_bloqueadas' => 'boolean',
    ];
}
