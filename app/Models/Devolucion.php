<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Devolucion extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * Eloquent would otherwise look for a `devolucions` table
     * due to the english pluralization rules. Explicitly defining
     * the table name ensures queries target the existing
     * `devoluciones` table created by the migration.
     */
    protected $table = 'devoluciones';

    protected $fillable = [
        'id_venta', 'id_usuario', 'id_sucursal', 'total_devuelto', 'observaciones'
    ];

    public function detalles()
    {
        return $this->hasMany(DevolucionDetalle::class, 'id_devolucion');
    }
}

