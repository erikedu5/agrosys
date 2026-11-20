<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Captura append-only; corregir = anular + capturar de nuevo. */
class AuditoriaConteo extends Model
{
    protected $table = 'auditoria_conteos';

    protected $fillable = ['id_auditoria_item', 'id_usuario', 'ronda', 'cantidad', 'ubicacion', 'corte_id', 'anulado_at', 'anulado_por'];

    protected $casts = [
        'anulado_at' => 'datetime',
        'ronda' => 'integer',
        'corte_id' => 'integer',
    ];

    public function item()
    {
        return $this->belongsTo(AuditoriaItem::class, 'id_auditoria_item');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario')->withTrashed();
    }
}
