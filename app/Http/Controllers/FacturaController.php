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

class FacturaController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $request->validate([
            'fechaInicio' => 'required',
            'fechaFin' => 'required',
        ]);

        $fechaInicio = new DateTime($request->fechaInicio);
        $fechaInicio->setTime(0, 0, 0);
        $fechaInicio->format('Y-m-d h:i:s a');

        $fechaFin = new DateTime($request->fechaFin);
        $fechaFin->setTime(23, 59, 59);
        $fechaFin->format('Y-m-d h:i:s a');

        $facturas = Factura::select('facturas.*')
            ->where('ventas.created_at', '>=', $fechaInicio)
            ->where('ventas.created_at', '<=', $fechaFin)
            ->where('clientes.id_sucursal', SucursalService::getSucursalActiva())
            ->join('ventas', 'facturas.id_venta', 'ventas.id')
            ->join('clientes', 'facturas.id_cliente', 'clientes.id')
            ->latest()
            ->paginate(10);

        foreach ($facturas as $factura) {
            $factura->cliente = Clientes::find($factura->id_cliente);
            $factura->venta = Venta::find($factura->id_venta);
        }

        return Inertia::render('Factura/Factura', [
            'facturas' => $facturas,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'fechaInicio' => 'required',
            'fechaFin' => 'required',
        ]);

        $fechaInicio = new DateTime($request->fechaInicio);
        $fechaInicio->setTime(0, 0, 0);
        $fechaInicio->format('Y-m-d h:i:s a');

        $fechaFin = new DateTime($request->fechaFin);
        $fechaFin->setTime(23, 59, 59);
        $fechaFin->format('Y-m-d h:i:s a');

        $factura = Factura::find($request->id);
        $factura->facturaCompleta = true;
        $factura->save();

        return redirect()->route('facturas.index', [
            'fechaInicio' => $request->fechaInicio,
            'fechaFin' => $request->fechaFin,
        ]);
    }
}
