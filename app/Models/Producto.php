<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\SucursalService;

class Producto extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nombre',
        'id_clasificacion',
        'id_marca',
        'cantidad',
        'precio_unitario',
        'ieps',
        'precio_ieps',
        'tamano',
        'id_usuario',
        'ingrediente_activo',
        'barcode',
        'id_empresa',
    ];

    protected static function booted()
    {
        static::addGlobalScope('empresa', function ($builder) {
            if (app()->runningInConsole()) {
                return;
            }

            $empresaId = SucursalService::getEmpresaIdActiva();

            if ($empresaId) {
                $builder->where('productos.id_empresa', $empresaId);
            }
        });

        static::creating(function (Producto $producto) {
            if (!$producto->id_empresa) {
                $producto->id_empresa = SucursalService::getEmpresaIdActiva();
            }
        });
    }

    public function clasificacion()
    {
        return $this->belongsTo(CatClasificacion::class);
    }

    public function marca()
    {
        return $this->belongsTo(CatMarca::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function altasInventario()
    {
        return $this->hasMany(AltaInventario::class, 'id_producto');
    }

    public function ultimaAltaInventario()
    {
        return $this->hasOne(AltaInventario::class, 'id_producto')
                    ->latestOfMany(); // Laravel 8+ para obtener el más reciente
    }

    protected function getCreatedAtAttribute() {
        return $this->attributes['created_at'];
    }

    protected function getUpdatedAtAttribute() {
        return $this->attributes['updated_at'];
    }
}
