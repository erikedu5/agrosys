<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatEnfermedades extends Model
{
    use HasFactory;

    protected function getCreatedAtAttribute() {
        return $this->attributes['created_at'];
    }

    protected function getUpdatedAtAttribute() {
        return $this->attributes['updated_at'];
    }
    
    protected $fillable = [
        'nombre',
        'descripcion'
    ];
}
