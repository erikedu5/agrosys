<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class AltaInventario extends Model
{
    use HasFactory;

    public const EVENTO_ALTA = 'alta_inventario';
    public const EVENTO_RESETEO = 'reseteo_cero';
    public const EVENTO_VENTA = 'venta';
    public const EVENTO_TRANSFERENCIA_SALIDA = 'transferencia_salida';
    public const EVENTO_TRANSFERENCIA_ENTRADA = 'transferencia_entrada';
    public const EVENTO_DOSIFICACION_SALIDA = 'dosificacion_salida';
    public const EVENTO_DOSIFICACION_ENTRADA = 'dosificacion_entrada';

    /** Etiquetas del cardex por tipo de evento. */
    public const ETIQUETAS = [
        self::EVENTO_ALTA => 'Alta de inventario',
        self::EVENTO_RESETEO => 'Reseteo a cero',
        self::EVENTO_VENTA => 'Venta',
        self::EVENTO_TRANSFERENCIA_ENTRADA => 'Entrada por transferencia',
        self::EVENTO_TRANSFERENCIA_SALIDA => 'Salida por transferencia',
        self::EVENTO_DOSIFICACION_SALIDA => 'Salida por dosificación',
        self::EVENTO_DOSIFICACION_ENTRADA => 'Entrada por dosificación',
    ];

    protected $fillable = [
        'cantidad_actual',
        'cantidad_nueva',
        'id_usuario',
        'id_producto',
        'id_sucursal',
        'tipo_evento',
    ];

    protected $casts = [
        'cantidad_actual' => 'float',
        'cantidad_nueva' => 'float',
        'tipo_evento' => 'string',
    ];

    protected $attributes = [
        'tipo_evento' => self::EVENTO_ALTA,
    ];

    protected static function booted(): void
    {
        // Respaldo del bloqueo por auditoría: corre dentro de la transacción del llamador.
        static::creating(fn (AltaInventario $movimiento) => app(\App\Services\Auditoria\InventoryFreeze::class)->guardLedger($movimiento));
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursales::class, 'id_sucursal');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    protected function getCreatedAtAttribute()
    {
        return $this->attributes['created_at'];
    }

    protected function getUpdatedAtAttribute()
    {
        return $this->attributes['updated_at'];
    }
}
