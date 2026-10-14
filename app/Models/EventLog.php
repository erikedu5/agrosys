<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'event_type',
        'status',
        'user_id',
        'sucursal_id',
        'initial_context',
        'steps',
        'steps_count',
        'result',
        'error_message',
        'error_file',
        'error_line',
        'error_trace',
        'duration',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'initial_context' => 'array',
        'steps' => 'array',
        'result' => 'array',
        'duration' => 'float',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Relación con el usuario que ejecutó el evento
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación con la sucursal donde se ejecutó
     */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursales::class, 'sucursal_id');
    }

    /**
     * Scope para filtrar por tipo de evento
     */
    public function scopeOfType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope para filtrar por estado
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para eventos exitosos
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope para eventos con error
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'error');
    }

    /**
     * Scope para eventos de un usuario
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para eventos de una sucursal
     */
    public function scopeBySucursal($query, int $sucursalId)
    {
        return $query->where('sucursal_id', $sucursalId);
    }

    /**
     * Scope para eventos recientes
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Obtener duración formateada
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration) {
            return 'N/A';
        }

        if ($this->duration < 1) {
            return round($this->duration * 1000) . ' ms';
        }

        return round($this->duration, 2) . ' s';
    }

    /**
     * Verificar si el evento fue exitoso
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Verificar si el evento falló
     */
    public function hasFailed(): bool
    {
        return $this->status === 'error';
    }

    /**
     * Verificar si tiene warnings
     */
    public function hasWarnings(): bool
    {
        return $this->status === 'warning';
    }
}
