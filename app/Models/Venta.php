<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    use HasFactory;

    protected $fillable = [
        'total',
        'id_usuario',
        'id_cliente',
        'tipo_venta',
        'venta_pagada',
        'fecha_pago',
    ];

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
