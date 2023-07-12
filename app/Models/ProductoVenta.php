<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoVenta extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_producto',
        'id_venta',
        'cantidad',
        'total_productos',
    ];

    public function producto() 
    {
        return $this->belongsTo(Producto::class);
    }

    public function venta() 
    {
        return $this->belongsTo(Venta::class);
    }

    protected function getCreatedAtAttribute() {
        return $this->attributes['created_at'];
    }

    protected function getUpdatedAtAttribute() {
        return $this->attributes['updated_at'];
    }
}
