<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AuditoriaHistoricoExport implements WithMultipleSheets
{
    public function __construct(private array $resultados, private bool $costos)
    {
    }

    public function sheets(): array
    {
        $headings = ['Folio', 'Fecha', 'Empresa', 'Sucursal/Bodega', 'Estado', 'Renglones', 'Exactitud %', 'Faltantes', 'Sobrantes', 'Neto'];
        if ($this->costos) {
            array_push($headings, 'Valor neto');
        }
        $rows = [];
        $detalle = [];
        foreach ($this->resultados as $a) {
            $t = $a['totales'];
            $line = [$a['folio'], $a['fecha'], $a['empresa'], $a['sucursal'], $a['status_label'], $t['renglones'], $t['exactitud'],
                $t['unidades_faltantes'], $t['unidades_sobrantes'], $t['unidades_neto']];
            if ($this->costos) {
                $line[] = $t['valor_neto'];
            }
            $rows[] = $line;
            foreach ($a['renglones'] as $r) {
                $detalle[] = [$a['folio'], $a['fecha'], $a['sucursal'], $r['marca'], $r['sku'], $r['nombre'], $r['esperado'], $r['contado'], $r['diferencia']];
            }
        }
        $sheets = [new AuditoriaSheet('Auditorías', $headings, $rows)];
        if ($detalle) {
            $sheets[] = new AuditoriaSheet('Detalle SKU', ['Folio', 'Fecha', 'Sucursal/Bodega', 'Marca', 'SKU', 'Producto', 'Esperado', 'Contado', 'Diferencia'], $detalle);
        }

        return $sheets;
    }
}
