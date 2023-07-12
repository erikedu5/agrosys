<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolucionEnfermedad extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_producto',
        'id_enfermedad_tipo_flor',
        'dosis_bomba_ml',
        'dosis_tambo_ml',
    ];

    public function producto() 
    {
        return $this->belongsTo(Producto::class);
    }

    public function enfermedadFlor() 
    {
        return $this->belongsTo(EnfermedadesTipoFlor::class);
    }

    protected function getCreatedAtAttribute() {
        return $this->attributes['created_at'];
    }

    protected function getUpdatedAtAttribute() {
        return $this->attributes['updated_at'];
    }
}
