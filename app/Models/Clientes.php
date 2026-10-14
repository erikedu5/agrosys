<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clientes extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'porcentaje_descuento',
        'adeudo_total',
        'abono_total',
        'balance',
        'id_sucursal',
        'requiereFactura',
        'rfc',
        'facturapi_customer_id',
        'regimen_fiscal',
        'codigo_postal',
        'uso_cfdi',
        'email_facturacion',
        'activo'
    ];

    protected $casts = [
        'requiereFactura' => 'boolean',
        'activo' => 'boolean',
    ];

    protected function getCreatedAtAttribute() {
        return $this->attributes['created_at'];
    }

    protected function getUpdatedAtAttribute() {
        return $this->attributes['updated_at'];
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursales::class, 'id_sucursal');
    }
}
