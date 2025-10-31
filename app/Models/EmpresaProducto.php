<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmpresaProducto extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'empresa_producto';

    protected $fillable = [
        'id_empresa',
        'id_producto',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto');
    }
}

