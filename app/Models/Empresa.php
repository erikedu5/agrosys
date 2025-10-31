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
        'enviar_facturas_automaticas',
        'motivo_bloqueo',
        'facturapi_api_key',
        'facturapi_sandbox',
    ];

    protected $casts = [
        'mostrar_campos_precio' => 'boolean',
        'ventas_bloqueadas' => 'boolean',
        'enviar_facturas_automaticas' => 'boolean',
        'facturapi_sandbox' => 'boolean',
    ];

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'empresa_producto', 'id_empresa', 'id_producto')
                    ->withTimestamps()
                    ->wherePivotNull('deleted_at');
    }
}
