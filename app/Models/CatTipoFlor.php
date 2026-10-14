<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatTipoFlor extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre'
    ];

    protected function getCreatedAtAttribute() {
        return $this->attributes['created_at'];
    }

    protected function getUpdatedAtAttribute() {
        return $this->attributes['updated_at'];
    }

    public function enfermedades_tipo_flor() 
    {
        return $this->hasMany(EnfermedadesTipoFlor::class, 'id_tipo_flor');
    }
}
