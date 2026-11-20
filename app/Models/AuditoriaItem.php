<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditoriaItem extends Model
{
    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_CONTADO = 'contado';
    public const ESTADO_RECONTEO = 'reconteo';
    public const ESTADO_CONCILIADO = 'conciliado';

    protected $table = 'auditoria_items';

    protected $fillable = [
        'id_auditoria', 'id_producto', 'descripcion_libre', 'nombre', 'tamano', 'barcode', 'id_marca', 'marca_nombre',
        'clasificacion_nombre', 'costo_unitario', 'cantidad_sistema_inicio', 'ronda', 'cantidad_contada', 'contado_r1',
        'contado_at', 'corte_conteo_id', 'movimientos_durante', 'desglose_movimientos', 'ventas_offline_tardias',
        'esperado', 'diferencia', 'transito_salida', 'transito_entrada', 'estado', 'no_esperado', 'comentario',
    ];

    protected $attributes = [
        'ronda' => 1,
        'estado' => self::ESTADO_PENDIENTE,
        'no_esperado' => false,
    ];

    protected $casts = [
        'contado_at' => 'datetime',
        'desglose_movimientos' => 'array',
        'no_esperado' => 'boolean',
        'ronda' => 'integer',
        'corte_conteo_id' => 'integer',
    ];

    public function auditoria()
    {
        return $this->belongsTo(Auditoria::class, 'id_auditoria');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto')->withoutGlobalScope('empresa')->withTrashed();
    }

    public function conteos()
    {
        return $this->hasMany(AuditoriaConteo::class, 'id_auditoria_item');
    }

    /** SKU de reporte: código de barras o, si no existe, el id del producto. */
    public function getSkuAttribute(): string
    {
        $barcode = trim((string) $this->barcode);

        return $barcode !== '' ? $barcode : ($this->id_producto ? (string) $this->id_producto : 'S/R');
    }
}
