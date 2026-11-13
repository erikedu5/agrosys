<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    use HasFactory;

    /** Formas de pago (claves SAT) para ventas de clientes que requieren factura. */
    public const FORMAS_PAGO = [
        '01' => 'Efectivo',
        '03' => 'Transferencia electrónica',
        '04' => 'Tarjeta de crédito',
        '28' => 'Tarjeta de débito',
        '02' => 'Cheque nominativo',
        '99' => 'Por definir',
    ];

    protected $fillable = [
        'forma_pago',
        'cash_session_id',
        'total',
        'id_usuario',
        'id_cliente',
        'tipo_venta',
        'venta_pagada',
        'fecha_pago',
        'id_sucursal',
        'client_sale_id',
        'operation_id',
        'device_id',
        'occurred_at',
    ];

    protected $casts = ['occurred_at' => 'datetime'];

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function ventas()
    {
        return $this->hasMany(ProductoVenta::class);
    }

    protected function getCreatedAtAttribute() {
        return $this->attributes['created_at'];
    }

    protected function getUpdatedAtAttribute() {
        return $this->attributes['updated_at'];
    }
}
