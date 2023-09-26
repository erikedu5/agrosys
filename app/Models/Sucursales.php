<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sucursales extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'email',
        'es_matriz',
        'id_empresa',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
