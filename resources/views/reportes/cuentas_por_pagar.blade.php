<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cuentas por pagar</title>
    <style>
        @page { margin: 14mm 12mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #000; }
        h1 { font-size: 17px; margin: 0; }
        h2 { font-size: 12px; margin: 16px 0 4px; padding: 4px 6px; background: #222; color: #fff; }
        .empresa { font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .meta { margin-top: 2px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #777; padding: 4px 5px; }
        th { background: #e2e2e2; font-size: 9px; text-align: center; }
        td.num { text-align: right; white-space: nowrap; }
        td.centro { text-align: center; white-space: nowrap; }
        tr.total td { font-weight: bold; background: #efefef; }
        tr.vencida td { background: #f3f3f3; }
        .alerta { font-weight: bold; }
        .cajas { margin: 12px 0 2px; }
        .cajas td { width: 33.33%; border: 1.5px solid #000; padding: 8px 10px; }
        .cajas span { display: block; font-size: 9px; text-transform: uppercase; }
        .cajas strong { display: block; font-size: 16px; }
        .vacio { padding: 6px; border: 1px solid #777; }
        .pie { margin-top: 10px; font-size: 8px; color: #444; }
    </style>
</head>
<body>
@php $m = fn ($valor) => '$ '.number_format((float) $valor, 2); @endphp
    <div class="empresa">{{ $titulo }}</div>
    <h1>Cuentas por pagar a proveedores</h1>
    <div class="meta">Compras con saldo pendiente al {{ $generado }}</div>

    <table class="cajas">
        <tr>
            <td><span>Saldo total por pagar</span><strong>{{ $m($totalSaldo) }}</strong></td>
            <td><span>Saldo vencido</span><strong>{{ $m($totalVencido) }}</strong></td>
            <td><span>Compras con adeudo</span><strong>{{ $totalCompras }}</strong></td>
        </tr>
    </table>

    @if ($totalCompras === 0)
        <div class="vacio" style="margin-top: 12px;">No hay compras a proveedores con saldo pendiente.</div>
    @else
        <h2>Resumen por proveedor</h2>
        <table>
            <thead><tr><th>Proveedor</th><th>Compras</th><th>Próximo vencimiento</th><th>Saldo vencido</th><th>Saldo por pagar</th></tr></thead>
            <tbody>
                @foreach ($proveedores as $proveedor)
                    <tr>
                        <td>{{ $proveedor['proveedor'] }}</td>
                        <td class="centro">{{ $proveedor['compras'] }}</td>
                        <td class="centro">{{ $proveedor['proximoVencimiento'] ?? '—' }}</td>
                        <td class="num {{ $proveedor['vencido'] > 0 ? 'alerta' : '' }}">{{ $m($proveedor['vencido']) }}</td>
                        <td class="num">{{ $m($proveedor['saldo']) }}</td>
                    </tr>
                @endforeach
                <tr class="total"><td colspan="3">Total</td><td class="num">{{ $m($totalVencido) }}</td><td class="num">{{ $m($totalSaldo) }}</td></tr>
            </tbody>
        </table>

        @foreach ($grupos as $grupo)
            <h2>{{ $grupo['nombre'] }}</h2>
            @if ($grupo['filas']->isEmpty())
                <div class="vacio">Sin compras con saldo pendiente.</div>
            @else
                <table>
                    <thead>
                        <tr><th>Compra</th><th>Proveedor</th><th>Fecha compra</th><th>Vence</th><th>Días vencido</th><th>Total compra</th><th>Abonado</th><th>Saldo</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($grupo['filas'] as $fila)
                            <tr class="{{ $fila['diasVencido'] > 0 ? 'vencida' : '' }}">
                                <td class="centro">#{{ $fila['id'] }}</td>
                                <td>{{ $fila['proveedor'] }}</td>
                                <td class="centro">{{ $fila['fechaCompra'] }}</td>
                                <td class="centro">{{ $fila['vence'] ?? '—' }}</td>
                                <td class="centro {{ $fila['diasVencido'] > 0 ? 'alerta' : '' }}">{{ $fila['diasVencido'] > 0 ? $fila['diasVencido'] : '—' }}</td>
                                <td class="num">{{ $m($fila['total']) }}</td>
                                <td class="num">{{ $m($fila['abonado']) }}</td>
                                <td class="num alerta">{{ $m($fila['saldo']) }}</td>
                            </tr>
                        @endforeach
                        <tr class="total">
                            <td colspan="5">Subtotal {{ $grupo['nombre'] }} ({{ $grupo['filas']->count() }} compras · vencido {{ $m($grupo['vencido']) }})</td>
                            <td class="num">{{ $m($grupo['total']) }}</td>
                            <td class="num">{{ $m($grupo['abonado']) }}</td>
                            <td class="num">{{ $m($grupo['saldo']) }}</td>
                        </tr>
                    </tbody>
                </table>
            @endif
        @endforeach
    @endif

    <div class="pie">Saldo = crédito pendiente registrado en la compra. Vencido = compras cuya fecha de crédito ya pasó. Generado el {{ $generado }} por {{ $usuario }}.</div>
</body>
</html>
