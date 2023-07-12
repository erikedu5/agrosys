<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnfermedadesTipoFlor extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_tipo_flor',
        'id_enfermedad',
    ];

    public function tipoFlor() 
    {
        return $this->belongsTo(CatTipoFlor::class);
    }

    public function enfermedad() 
    {
        return $this->belongsTo(CatEnfermedades::class);
    }
    
    protected function getCreatedAtAttribute() {
        return $this->attributes['created_at'];
    }

    protected function getUpdatedAtAttribute() {
        return $this->attributes['updated_at'];
    }
}
