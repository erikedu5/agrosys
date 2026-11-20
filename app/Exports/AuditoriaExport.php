<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/** Reporte de una auditoría: hojas Resumen y Detalle. */
class AuditoriaExport implements WithMultipleSheets
{
    public function __construct(private array $data)
    {
    }

    public function sheets(): array
    {
        $a = $this->data['auditoria'];
        $r = $this->data['resumen'];
        $costos = $this->data['ver_costos'];
        $resumen = [
            ['Folio', $a['folio']],
            ['Estado', $a['status_label']],
            ['Empresa', $a['empresa']],
            [$a['es_bodega'] ? 'Bodega' : 'Sucursal', $a['sucursal']],
            ['Alcance', $a['alcance']],
            ['Auditores', implode(', ', $a['auditores'])],
            ['Inicio de conteo', $a['iniciada_at']],
            ['Cierre de conteo', $a['conteo_cerrado_at']],
            ['Finalizada', $a['finalizada_at']],
            ['Renglones', $r['renglones']],
            ['Contados', $r['contados']],
            ['Exactitud %', $r['exactitud']],
            ['Unidades faltantes', $r['unidades_faltantes']],
            ['Unidades sobrantes', $r['unidades_sobrantes']],
            ['Unidades neto', $r['unidades_neto']],
        ];
        if ($costos) {
            array_push($resumen, ['Valor faltante', $r['valor_faltante']], ['Valor sobrante', $r['valor_sobrante']], ['Valor neto', $r['valor_neto']]);
        }

        $headings = ['Marca', 'SKU', 'Producto', 'Tamaño', 'Sistema inicio', 'Movimientos durante', 'Ventas offline tardías',
            'Esperado', 'Contado R1', 'Contado vigente', 'Ronda', 'Diferencia', 'Tránsito salida', 'Tránsito entrada', 'No esperado', 'Comentario'];
        if ($costos) {
            array_push($headings, 'Costo unitario', 'Valor diferencia');
        }
        $detalle = [];
        foreach ($this->data['marcas'] as $grupo) {
            foreach ($grupo['renglones'] as $row) {
                $line = [$row['marca'], $row['sku'], $row['nombre'], $row['tamano'], $row['sistema_inicio'], $row['movimientos_durante'],
                    $row['ventas_offline_tardias'], $row['esperado'], $row['contado_r1'], $row['contado'], $row['ronda'], $row['diferencia'],
                    $row['transito_salida'], $row['transito_entrada'], $row['no_esperado'] ? 'Sí' : 'No', $row['comentario']];
                if ($costos) {
                    array_push($line, $row['costo_unitario'], $row['valor_diferencia']);
                }
                $detalle[] = $line;
            }
        }

        return [
            new AuditoriaSheet('Resumen', ['Concepto', 'Valor'], $resumen),
            new AuditoriaSheet('Detalle', $headings, $detalle),
        ];
    }
}
