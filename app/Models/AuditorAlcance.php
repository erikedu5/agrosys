<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Alcance de un auditor externo; id_sucursal null = todas las sucursales de la empresa. */
class AuditorAlcance extends Model
{
    protected $table = 'auditor_alcances';

    protected $fillable = ['id_usuario', 'id_empresa', 'id_sucursal'];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursales::class, 'id_sucursal');
    }
}
