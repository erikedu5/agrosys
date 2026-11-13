<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de cortes de caja</title>
    <style>
        @page { margin: 12mm 10mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #000; }
        h1 { font-size: 16px; margin: 0; }
        h2 { font-size: 12px; margin: 14px 0 4px; padding: 4px 6px; background: #222; color: #fff; }
        .empresa { font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .meta { font-size: 9.5px; margin-top: 2px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #777; padding: 4px 4px; }
        th { background: #e2e2e2; font-size: 8px; text-align: center; }
        td.num { text-align: right; white-space: nowrap; }
        tr.total td { font-weight: bold; background: #efefef; }
        tr.general td { font-weight: bold; background: #d6d6d6; font-size: 9.5px; }
        .alerta { font-weight: bold; }
        .nota { font-size: 8px; color: #333; }
        .vacio { padding: 6px; border: 1px solid #777; }
        .resumen td { font-size: 10px; }
        .pie { margin-top: 10px; font-size: 8px; color: #444; }
    </style>
</head>
<body>
@php
    $m = fn ($valor) => $valor === null ? '—' : '$ '.number_format((float) $valor, 2);
    $columnas = ['fondo', 'contado', 'credito', 'abonoInicial', 'abonos', 'entradas', 'salidas', 'reembolsos', 'esperado', 'contadoCaja', 'diferencia'];
@endphp
    <div class="empresa">{{ $titulo }}</div>
    <h1>Reporte de cortes de caja</h1>
    <div class="meta">
        <strong>Periodo:</strong> del {{ $fechaInicio }} al {{ $fechaFin }} (cajas abiertas en esas fechas) ·
        <strong>Alcance:</strong> {{ $todas ? 'Todas las sucursales' : ($grupos[0]['sucursal']->nombre ?? '') }}
    </div>

    @if ($todas)
        <h2>Resumen por sucursal</h2>
        <table class="resumen">
            <thead>
                <tr>
                    <th>Sucursal</th><th>Cortes</th><th>Vendido contado</th><th>Vendido crédito</th><th>Abonos iniciales</th>
                    <th>Abonos clientes</th><th>Efectivo esperado</th><th>Efectivo contado</th><th>Diferencia</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($grupos as $grupo)
                    <tr>
                        <td>{{ $grupo['sucursal']->nombre }}</td>
                        <td class="num">{{ count($grupo['filas']) }}</td>
                        <td class="num">{{ $m($grupo['subtotal']['contado']) }}</td>
                        <td class="num">{{ $m($grupo['subtotal']['credito']) }}</td>
                        <td class="num">{{ $m($grupo['subtotal']['abonoInicial']) }}</td>
                        <td class="num">{{ $m($grupo['subtotal']['abonos']) }}</td>
                        <td class="num">{{ $m($grupo['subtotal']['esperado']) }}</td>
                        <td class="num">{{ $m($grupo['subtotal']['contadoCaja']) }}</td>
                        <td class="num {{ abs($grupo['subtotal']['diferencia']) > 0.004 ? 'alerta' : '' }}">{{ $m($grupo['subtotal']['diferencia']) }}</td>
                    </tr>
                @endforeach
                <tr class="general">
                    <td>Total general</td>
                    <td class="num">{{ collect($grupos)->sum(fn ($grupo) => count($grupo['filas'])) }}</td>
                    <td class="num">{{ $m($totalGeneral['contado']) }}</td>
                    <td class="num">{{ $m($totalGeneral['credito']) }}</td>
                    <td class="num">{{ $m($totalGeneral['abonoInicial']) }}</td>
                    <td class="num">{{ $m($totalGeneral['abonos']) }}</td>
                    <td class="num">{{ $m($totalGeneral['esperado']) }}</td>
                    <td class="num">{{ $m($totalGeneral['contadoCaja']) }}</td>
                    <td class="num">{{ $m($totalGeneral['diferencia']) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    @foreach ($grupos as $grupo)
        <h2>{{ $grupo['sucursal']->nombre }}</h2>
        @if (count($grupo['filas']) === 0)
            <div class="vacio">Sin cortes de caja en el periodo.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Cajero</th><th>Apertura</th><th>Cierre</th><th>Fondo inicial</th><th>Vendido contado</th><th>Vendido crédito</th>
                        <th>Abonos iniciales</th><th>Abonos clientes</th><th>Entradas</th><th>Salidas</th><th>Reembolsos</th>
                        <th>Efectivo esperado</th><th>Efectivo contado</th><th>Diferencia</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($grupo['filas'] as $fila)
                        <tr>
                            <td>{{ $fila['cajero'] }}</td>
                            <td>{{ $fila['apertura'] }}</td>
                            <td>{{ $fila['cierre'] ?? 'Caja abierta' }}</td>
                            @foreach ($columnas as $columna)
                                <td class="num {{ $columna === 'diferencia' && abs((float) $fila[$columna]) > 0.004 ? 'alerta' : '' }}">{{ $m($fila[$columna]) }}</td>
                            @endforeach
                        </tr>
                        @if ($fila['notas'])
                            <tr><td colspan="14" class="nota">Observaciones: {{ $fila['notas'] }}</td></tr>
                        @endif
                    @endforeach
                    <tr class="total">
                        <td colspan="3">Subtotal {{ $grupo['sucursal']->nombre }} ({{ count($grupo['filas']) }} cortes)</td>
                        @foreach ($columnas as $columna)
                            <td class="num">{{ $m($grupo['subtotal'][$columna]) }}</td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        @endif
    @endforeach

    <div class="pie">
        Efectivo esperado = fondo inicial + vendido de contado + abonos iniciales + abonos de clientes + entradas − salidas − reembolsos.
        El crédito por cobrar no entra a la caja. Generado el {{ $generado }} por {{ $usuario }}.
    </div>
</body>
</html>
