<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de clientes con adeudo</title>
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
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }

        th {
            background-color: #f5f5f5;
        }

        .section-title {
            margin-top: 25px;
            font-size: 14px;
        }

        .totals {
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div style="text-align: center">
        @if ($empresa)
            <div>{{ $empresa->nombre }}</div>
            <div>{{ $empresa->direccion }}</div>
            <div><strong>Email:</strong> {{ $empresa->email }}</div>
            <div><strong>Telefono:</strong> {{ $empresa->telefono }}</div>
            <div><strong>RFC:</strong> {{ $empresa->rfc }}</div>
        @else
            <div><strong>Reporte de clientes con adeudo por sucursal</strong></div>
        @endif
    </div>

    <div style="margin-top: 15px;">
        <div><strong>Generado:</strong> {{ $generadoEn }}</div>
        <div><strong>Usuario:</strong> {{ optional($usuario)->name }}</div>
        @if ($sucursalSeleccionada)
            <div><strong>Sucursal seleccionada:</strong> {{ $sucursalSeleccionada->nombre }}</div>
        @endif
    </div>

    @if (!$hayClientesConAdeudo)
        <p style="margin-top: 20px;">No se encontraron clientes con adeudo en las sucursales consultadas.</p>
    @endif

    @foreach ($datos as $registro)
        <div class="section-title">
            <strong>Sucursal:</strong> {{ $registro['sucursal']->nombre }}
        </div>
        <div>
            {{ $registro['sucursal']->direccion }}<br>
            <strong>Telefono:</strong> {{ $registro['sucursal']->telefono }} |
            <strong>Email:</strong> {{ $registro['sucursal']->email }}
        </div>

        @if ($registro['clientes']->isEmpty())
            <p style="margin-top: 10px;">Sin clientes con adeudo en esta sucursal.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Adeudo original</th>
                        <th>Total abonado</th>
                        <th>Saldo pendiente</th>
                        <th>Actualizado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($registro['clientes'] as $cliente)
                        <tr>
                            <td>{{ $cliente->id }}</td>
                            <td>{{ $cliente->nombre }}</td>
                            <td>${{ number_format((float) $cliente->adeudo_total, 2) }}</td>
                            <td>${{ number_format((float) $cliente->abono_total, 2) }}</td>
                            <td>${{ number_format((float) $cliente->balance, 2) }}</td>
                            <td>
                                @if ($cliente->updated_at)
                                    {{ \Carbon\Carbon::parse($cliente->updated_at)->format('d/m/Y H:i') }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="totals">
                <strong>Total clientes con adeudo en sucursal:</strong> {{ $registro['totalClientes'] }}<br>
                <strong>Total adeudo original:</strong> ${{ number_format($registro['totalAdeudoOriginal'], 2) }}<br>
                <strong>Total abonado:</strong> ${{ number_format($registro['totalAbonos'], 2) }}<br>
                <strong>Saldo pendiente:</strong> ${{ number_format($registro['totalAdeudo'], 2) }}
            </div>
        @endif
    @endforeach

    <hr style="margin-top: 30px;">
    <div class="totals">
        <strong>Total general de clientes con adeudo:</strong> {{ $totalGeneralClientes }}<br>
        <strong>Adeudo original acumulado:</strong> ${{ number_format($totalGeneralAdeudoOriginal, 2) }}<br>
        <strong>Total abonado acumulado:</strong> ${{ number_format($totalGeneralAbonos, 2) }}<br>
        <strong>Saldo pendiente acumulado:</strong> ${{ number_format($totalGeneralAdeudo, 2) }}
    </div>
</body>
</html>
