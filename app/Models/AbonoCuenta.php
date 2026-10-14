<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbonoCuenta extends Model
{
    use HasFactory;

    protected $fillable = [
        'cantidad_abonada',
        'id_usuario',
        'id_cliente',
        'cuenta_pagada',
        'is_active',
        'id_sucursal'
    ];


    protected function getCreatedAtAttribute() {
        return $this->attributes['created_at'];
    }

    protected function getUpdatedAtAttribute() {
        return $this->attributes['updated_at'];
    }
}
