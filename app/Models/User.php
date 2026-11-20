<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    public const TIPO_AUDITOR = 'auditor';

    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use SoftDeletes;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'tipo',
        'id_sucursal',
        'id_empresa',
        'acceso_hasta',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'acceso_hasta' => 'date',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];


    protected function getCreatedAtAttribute()
    {
        return $this->attributes['created_at'];
    }

    protected function getUpdatedAtAttribute()
    {
        return $this->attributes['updated_at'];
    }

    /**
     * Relación con Sucursal
     */
    public function sucursal()
    {
        return $this->belongsTo(Sucursales::class, 'id_sucursal');
    }

    /**
     * Relación con Empresa
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    /**
     * Obtener todas las sucursales de la empresa del usuario
     */
    public function sucursalesEmpresa()
    {
        if ($this->tipo === 'adminEmpresa' && $this->id_empresa) {
            return Sucursales::where('id_empresa', $this->id_empresa)->get();
        }
        return collect();
    }

    /**
     * Verificar si el usuario es administrador de empresa
     */
    public function isAdminEmpresa()
    {
        return $this->tipo === 'adminEmpresa';
    }

    /**
     * Verificar si el usuario es super administrador
     */
    public function isSuperAdmin()
    {
        return $this->tipo === 'superAdmin';
    }

    /**
     * Verificar si el usuario es auditor externo
     */
    public function isAuditor(): bool
    {
        return $this->tipo === self::TIPO_AUDITOR;
    }

    public function alcancesAuditor()
    {
        return $this->hasMany(AuditorAlcance::class, 'id_usuario');
    }

    /** Vigencia del acceso externo: null = sin vencimiento. */
    public function accesoVigente(): bool
    {
        return $this->acceso_hasta === null || $this->acceso_hasta->copy()->endOfDay()->isFuture();
    }
}
