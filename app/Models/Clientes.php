<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Clientes extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'tipo_precio',
        'limite_credito',
        'plazo_credito_dias',
        'adeudo_total',
        'abono_total',
        'balance',
        'id_sucursal',
        'requiereFactura',
        'rfc',
        'facturapi_customer_id',
        'regimen_fiscal',
        'codigo_postal',
        'uso_cfdi',
        'email_facturacion',
        'activo'
    ];

    protected $casts = [
        'requiereFactura' => 'boolean',
        'activo' => 'boolean',
    ];

    protected function getCreatedAtAttribute() {
        return $this->attributes['created_at'];
    }

    protected function getUpdatedAtAttribute() {
        return $this->attributes['updated_at'];
    }

    /** Fecha de la venta a crédito sin pagar más antigua, si ya rebasó el plazo de crédito. */
    public function creditoVencidoDesde(): ?Carbon
    {
        if ($this->plazo_credito_dias === null) {
            return null;
        }
        $fecha = Venta::where('id_cliente', $this->id)->where('tipo_venta', 'Credito')
            ->where('venta_pagada', false)->min('created_at');

        return $fecha && Carbon::parse($fecha)->addDays((int) $this->plazo_credito_dias)->isPast() ? Carbon::parse($fecha) : null;
    }

    /**
     * Motivo por el que el cliente no puede comprar a crédito, o null si puede.
     * $nuevoCredito: saldo (en pesos) que la venta agregaría a su adeudo.
     */
    public function restriccionCredito(float $nuevoCredito = 0): ?string
    {
        if ($desde = $this->creditoVencidoDesde()) {
            return "El cliente rebasó su plazo de crédito de {$this->plazo_credito_dias} días (adeudo desde el {$desde->format('d/m/Y')}).";
        }
        if ($this->limite_credito !== null) {
            $limite = (float) $this->limite_credito;
            $saldo = max(0, (float) $this->balance);
            if (round($saldo + $nuevoCredito, 2) > $limite || ($nuevoCredito <= 0 && $saldo >= $limite)) {
                return 'El cliente excede su límite de crédito de $'.number_format($limite, 2).' (adeudo actual $'.number_format($saldo, 2).').';
            }
        }

        return null;
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursales::class, 'id_sucursal');
    }
}
