<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    use HasFactory;

    protected $fillable = [
        'facturaCompleta',
        'id_venta',
        'id_cliente',
        'facturapi_invoice_id',
        'facturapi_uuid',
        'facturapi_pdf_url',
        'facturapi_xml_url',
        'factura_status',
        'factura_error',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Clientes::class);
    }

    protected function getCreatedAtAttribute() {
        return $this->attributes['created_at'];
    }

    protected function getUpdatedAtAttribute() {
        return $this->attributes['updated_at'];
    }
}
