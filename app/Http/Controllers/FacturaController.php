<?php

namespace App\Http\Controllers;

use App\Models\Clientes;
use App\Models\Factura;
use App\Models\Venta;
use App\Services\SucursalService;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;

class FacturaController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Sin fechas (o con fechas inválidas) se muestra la última semana.
        [$fechaInicio, $fechaFin] = $this->rango($request);

        $facturas = Factura::select('facturas.*')
            ->join('ventas', 'facturas.id_venta', 'ventas.id')
            ->join('clientes', 'facturas.id_cliente', 'clientes.id')
            ->whereBetween('ventas.created_at', [$fechaInicio, $fechaFin])
            ->where('ventas.id_sucursal', SucursalService::getSucursalActiva())
            ->orderByDesc('facturas.id')
            ->paginate(10)
            ->withQueryString();

        foreach ($facturas as $factura) {
            $factura->cliente = Clientes::find($factura->id_cliente);
            $factura->venta = Venta::find($factura->id_venta);
        }

        return Inertia::render('Factura/Factura', [
            'facturas' => $facturas,
            'filtros' => ['fechaInicio' => $fechaInicio->format('Y-m-d'), 'fechaFin' => $fechaFin->format('Y-m-d')],
        ]);
    }

    /** Rango [inicio 00:00, fin 23:59] tomado de la petición; por defecto los últimos 7 días. */
    private function rango(Request $request): array
    {
        $parse = function ($valor, string $default): DateTime {
            try {
                $texto = trim((string) $valor);
                if ($texto === '') {
                    return new DateTime($default);
                }

                // yyyy-mm-dd (con o sin hora) o la fecha que serializa el navegador: "Fri Nov 13 2026 ... (zona)".
                return new DateTime(preg_match('/^\d{4}-\d{2}-\d{2}/', $texto, $iso) ? $iso[0] : substr(preg_replace('/\s*\(.*\)$/', '', $texto), 0, 15));
            } catch (\Throwable) {
                return new DateTime($default);
            }
        };
        $inicio = $parse($request->input('fechaInicio'), '-6 days')->setTime(0, 0, 0);
        $fin = $parse($request->input('fechaFin'), 'today')->setTime(23, 59, 59);

        return $inicio <= $fin ? [$inicio, $fin] : [(clone $fin)->setTime(0, 0, 0), (clone $inicio)->setTime(23, 59, 59)];
    }

    /**
     * Excel de ventas a facturar: ventas con forma de pago capturada (cliente requiere factura) o con factura registrada.
     */
    public function export(Request $request)
    {
        [$inicio, $fin] = $this->rango($request);

        $rows = Venta::query()
            ->join('clientes', 'ventas.id_cliente', '=', 'clientes.id')
            ->leftJoin('facturas', 'facturas.id_venta', '=', 'ventas.id')
            ->where('ventas.id_sucursal', SucursalService::getSucursalActiva())
            ->whereBetween('ventas.created_at', [$inicio, $fin])
            ->where(fn ($query) => $query->whereNotNull('ventas.forma_pago')->orWhereNotNull('facturas.id'))
            ->orderBy('ventas.id')
            ->get(['ventas.id', 'ventas.created_at as fecha', 'ventas.tipo_venta', 'ventas.forma_pago', 'ventas.total',
                'clientes.nombre', 'clientes.rfc', 'facturas.id as factura_id', 'facturas.facturaCompleta', 'facturas.facturapi_uuid'])
            ->map(fn ($row) => [
                $row->id,
                $row->fecha,
                $row->nombre,
                $row->rfc,
                $row->tipo_venta,
                $row->forma_pago ? $row->forma_pago.' - '.(Venta::FORMAS_PAGO[$row->forma_pago] ?? '') : 'Sin definir',
                (float) $row->total,
                $row->facturaCompleta ? 'Facturada' : 'No facturada',
                $row->facturapi_uuid,
            ])->all();

        $export = new class($rows) implements FromArray, ShouldAutoSize, WithHeadings
        {
            public function __construct(private array $rows) {}

            public function array(): array
            {
                return $this->rows;
            }

            public function headings(): array
            {
                return ['Id venta', 'Fecha de venta', 'Cliente', 'RFC', 'Tipo de venta', 'Forma de pago', 'Total', 'Estatus', 'Folio fiscal (UUID)'];
            }
        };

        return Excel::download($export, 'facturas_'.$inicio->format('Ymd').'_'.$fin->format('Ymd').'.xlsx');
    }

    public function update(Request $request)
    {
        $request->validate(['id' => 'required|integer']);

        // Iniciar logging del evento
        $logger = \App\Services\EventLogger::start('ACTUALIZAR_FACTURA', [
            'factura_id' => $request->id,
            'fecha_inicio' => $request->fechaInicio,
            'fecha_fin' => $request->fechaFin,
        ]);

        try {
            $factura = Factura::whereKey($request->id)
                ->whereIn('id_venta', Venta::where('id_sucursal', SucursalService::getSucursalActiva())->select('id'))
                ->first();

            if (!$factura) {
                $logger->warning('Factura no encontrada', [
                    'factura_id' => $request->id,
                ]);
                return redirect()->route('facturas.index', [
                    'fechaInicio' => $request->fechaInicio,
                    'fechaFin' => $request->fechaFin,
                ])->with('error', 'Factura no encontrada.');
            }

            $logger->step('Factura cargada', [
                'venta_id' => $factura->id_venta,
                'cliente_id' => $factura->id_cliente,
                'estado_anterior' => $factura->facturaCompleta,
            ]);

            $factura->facturaCompleta = true;
            if (in_array($factura->factura_status, [null, 'pendiente', 'sin_configuracion', 'error'], true)) {
                $factura->factura_status = 'manual';
            }
            $factura->save();

            $logger->success([
                'factura_id' => $factura->id,
                'factura_completa' => true,
            ]);

            return redirect()->route('facturas.index', [
                'fechaInicio' => $request->fechaInicio,
                'fechaFin' => $request->fechaFin,
            ]);
        } catch (\Exception $e) {
            $logger->error($e, [
                'factura_id' => $request->id,
            ]);

            return redirect()->route('facturas.index', [
                'fechaInicio' => $request->fechaInicio,
                'fechaFin' => $request->fechaFin,
            ])->with('error', 'Error al actualizar factura: ' . $e->getMessage());
        }
    }
}
