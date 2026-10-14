<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Compras extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'proveedor',
        'fecha_compra',
        'total_compra',
        'status',
        'total_credito',
        'fecha_credito',
        'total_credito',
        'id_sucursal',
        'id_empresa',
        'idempotency_key',
        'request_hash',
    ];

    protected function getCreatedAtAttribute() {
        return $this->attributes['created_at'];
    }

    protected function getUpdatedAtAttribute() {
        return $this->attributes['updated_at'];
    }

    public function productos()
    {
        return $this->hasMany(ComprasProductos::class);
    }

    public function abonos()
    {
        return $this->hasMany(ComprasAbonos::class);
    }
}
