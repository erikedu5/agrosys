<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Venta</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            color: #222;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 10px;
        }

        th, td {
            text-align: left;
            padding: 6px;
            border: 1px solid #ddd;
        }

        th {
            background-color: #f5f5f5;
        }

        .meta {
            margin-top: 15px;
        }
    </style>
</head>
<body>

    <div style="text-align: center">
        <div>{{ $empresa->nombre }}</div>
        <div><strong>Email:</strong> {{ $empresa->email }}</div>
        <div><strong>Telefono:</strong> {{ $empresa->telefono }}</div>
        <div><strong>RFC:</strong> {{ $empresa->rfc }}</div>
    </div>

    <div class="meta">
        <div><strong>Sucursal:</strong> {{ optional($sucursalUser)->nombre ?? 'N/A' }}</div>
        <div><strong>Fecha de reporte:</strong> {{ $fechaInicio }} al {{ $fechaFin }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Id venta</th>
                <th>Fecha de venta</th>
                <th>Tipo de venta</th>
                <th>Nombre del producto</th>
                <th>Cantidad</th>
                <th>Precio unitario</th>
                <th>Total</th>
                <th>Cliente</th>
                <th>Stock anterior</th>
                <th>Nuevo stock</th>
                <th>Usuario</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($ventas as $venta)
                @foreach ($venta->productos as $producto)
                    @php
                        $precioUnitario = $producto->cantidad ? ($producto->total_productos / $producto->cantidad) : 0;
                        $clienteNombre = optional($venta->cliente)->nombre ?? 'Cliente público';
                        $usuarioNombre = optional($venta->usuario)->name ?? 'N/D';
                        $stockAnterior = is_numeric($producto->stock_anterior) ? number_format($producto->stock_anterior, 2) : 'N/D';
                        $stockNuevo = is_numeric($producto->stock_nuevo) ? number_format($producto->stock_nuevo, 2) : 'N/D';
                    @endphp
                    <tr>
                        <td>{{ $venta->id }}</td>
                        <td>{{ $venta->created_at }}</td>
                        <td>{{ $venta->tipo_venta }}</td>
                        <td>{{ $producto->detail->nombre }}</td>
                        <td>{{ number_format($producto->cantidad, 2) }}</td>
                        <td>${{ number_format($precioUnitario, 2) }}</td>
                        <td>${{ number_format($producto->total_productos, 2) }}</td>
                        <td>{{ $clienteNombre }}</td>
                        <td>{{ $stockAnterior }}</td>
                        <td>{{ $stockNuevo }}</td>
                        <td>{{ $usuarioNombre }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4"><strong>Sumatoria total de las ventas</strong></td>
                <td><strong>${{ number_format($montoCredito + $montoContado, 2) }}</strong></td>
                <td colspan="4"></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
