<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComprasProductos extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_compra',
        'id_producto',
        'cantidad',
        'precio',
        'cantidad_disponible'
    ];

    protected function getCreatedAtAttribute()
    {
        return $this->attributes['created_at'];
    }

    protected function getUpdatedAtAttribute()
    {
        return $this->attributes['updated_at'];
    }
}
