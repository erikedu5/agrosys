<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de ganancias diarias</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            text-align: left;
            padding: 8px;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div style="text-align: center; font-weight: bold; font-size: 1.2rem;">
        {{ $empresa->nombre }}
    </div>
    <div style="text-align: center;">{{ $empresa->direccion }}</div>
    <div style="text-align: center;"><strong>Email:</strong> {{ $empresa->email }}</div>
    <div style="text-align: center;"><strong>Teléfono:</strong> {{ $empresa->telefono }}</div>
    <div style="text-align: center;"><strong>RFC:</strong> {{ $empresa->rfc }}</div>

    <br>

    <div><strong>Reporte de ganancias diarias</strong></div>
    <div>Rango del {{ $fechaInicio }} al {{ $fechaFin }}</div>
    <div>
        Sucursal:
        @if ($incluyeTodasSucursales)
            Todas las sucursales de la empresa
        @else
            {{ $sucursal->nombre }}
        @endif
    </div>
    <br>
    <div><strong>Ganancia total del periodo:</strong> $ {{ number_format($gananciaTotal, 2) }}</div>
    <div><strong>Total de unidades vendidas:</strong> {{ $totalUnidades }}</div>

    <br>

    <h3>Resumen de ventas</h3>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Sucursal</th>
                <th>Cliente</th>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Ganancia por producto</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($detallesGanancia as $detalle)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($detalle->fecha)->format('d/m/Y') }}</td>
                    <td>{{ $detalle->sucursal_nombre }}</td>
                    <td>{{ $detalle->cliente_nombre }}</td>
                    <td>{{ $detalle->producto_nombre }}</td>
                    <td>{{ (int) $detalle->cantidad }}</td>
                    <td>$ {{ number_format($detalle->ganancia_linea, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No se registraron ventas en el rango seleccionado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
