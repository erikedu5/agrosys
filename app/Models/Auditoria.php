<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    public const STATUS_BORRADOR = 'borrador';
    public const STATUS_EN_CONTEO = 'en_conteo';
    public const STATUS_EN_REVISION = 'en_revision';
    public const STATUS_FINALIZADA = 'finalizada';
    public const STATUS_CANCELADA = 'cancelada';

    /** Estados que impiden abrir otra auditoría en la misma sucursal. */
    public const ACTIVAS = [self::STATUS_BORRADOR, self::STATUS_EN_CONTEO, self::STATUS_EN_REVISION];

    /** Estados que bloquean movimientos no-venta de la sucursal. */
    public const BLOQUEANTES = [self::STATUS_EN_CONTEO, self::STATUS_EN_REVISION];

    public const ETIQUETAS = [
        self::STATUS_BORRADOR => 'Borrador',
        self::STATUS_EN_CONTEO => 'En conteo',
        self::STATUS_EN_REVISION => 'En revisión',
        self::STATUS_FINALIZADA => 'Finalizada',
        self::STATUS_CANCELADA => 'Cancelada',
    ];

    public const ALCANCE_TOTAL = 'total';
    public const ALCANCE_MARCAS = 'marcas';
    public const ALCANCE_CLASIFICACIONES = 'clasificaciones';
    public const ALCANCE_PRODUCTOS = 'productos';
    public const ALCANCES = [self::ALCANCE_TOTAL, self::ALCANCE_MARCAS, self::ALCANCE_CLASIFICACIONES, self::ALCANCE_PRODUCTOS];

    protected $fillable = [
        'folio', 'id_empresa', 'id_sucursal', 'status', 'alcance_tipo', 'alcance_filtros', 'conteo_ciego',
        'corte_inicio_id', 'iniciada_at', 'conteo_cerrado_at', 'finalizada_at', 'cancelada_at', 'motivo_cancelacion',
        'id_usuario_crea', 'id_usuario_finaliza', 'id_usuario_cancela', 'notas',
    ];

    protected $casts = [
        'alcance_filtros' => 'array',
        'conteo_ciego' => 'boolean',
        'corte_inicio_id' => 'integer',
        'iniciada_at' => 'datetime',
        'conteo_cerrado_at' => 'datetime',
        'finalizada_at' => 'datetime',
        'cancelada_at' => 'datetime',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa')->withTrashed();
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursales::class, 'id_sucursal')->withTrashed();
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'id_usuario_crea')->withTrashed();
    }

    public function auditores()
    {
        return $this->belongsToMany(User::class, 'auditoria_auditores', 'id_auditoria', 'id_usuario')->withTimestamps();
    }

    public function items()
    {
        return $this->hasMany(AuditoriaItem::class, 'id_auditoria');
    }

    public function esActiva(): bool
    {
        return in_array($this->status, self::ACTIVAS, true);
    }

    public function bloquea(): bool
    {
        return in_array($this->status, self::BLOQUEANTES, true);
    }
}
