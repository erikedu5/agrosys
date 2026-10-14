<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transferencia extends Model
{
    use HasFactory;

    protected $fillable = [
        'folio',
        'id_sucursal_origen',
        'id_sucursal_destino',
        'id_usuario_envia',
        'id_usuario_recibe',
        'fecha_envio',
        'fecha_recepcion',
        'notas',
        'status',
    ];

    protected $casts = [
        'fecha_envio' => 'datetime',
        'fecha_recepcion' => 'datetime',
    ];

    public function sucursalOrigen()
    {
        return $this->belongsTo(Sucursales::class, 'id_sucursal_origen');
    }

    public function sucursalDestino()
    {
        return $this->belongsTo(Sucursales::class, 'id_sucursal_destino');
    }

    public function usuarioEnvia()
    {
        return $this->belongsTo(User::class, 'id_usuario_envia');
    }

    public function usuarioRecibe()
    {
        return $this->belongsTo(User::class, 'id_usuario_recibe');
    }

    public function detalles()
    {
        return $this->hasMany(TransferenciaDetalle::class, 'id_transferencia');
    }
}
