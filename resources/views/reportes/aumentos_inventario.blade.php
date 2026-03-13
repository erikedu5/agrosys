<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Aumentos de Inventario</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #1a1a1a; }

        .header { text-align: center; margin-bottom: 16px; }
        .header .empresa-nombre { font-size: 15px; font-weight: bold; margin-bottom: 2px; }
        .header .empresa-info { font-size: 10px; color: #555; }

        .report-title { text-align: center; margin-bottom: 8px; }
        .report-title h2 { font-size: 13px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .report-title p  { font-size: 10px; color: #555; margin-top: 2px; }

        .meta { display: table; width: 100%; margin-bottom: 12px; font-size: 10px; color: #555; }
        .meta .left  { display: table-cell; text-align: left; }
        .meta .right { display: table-cell; text-align: right; }

        table {
            border-collapse: collapse;
            width: 100%;
        }
        thead tr {
            background-color: #2d6a4f;
            color: #ffffff;
        }
        thead th {
            padding: 6px 8px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        tbody tr:nth-child(even) { background-color: #f0f7f4; }
        tbody tr:nth-child(odd)  { background-color: #ffffff; }
        tbody td {
            padding: 5px 8px;
            font-size: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        .num { text-align: right; }
        .empty-msg {
            text-align: center;
            padding: 20px;
            color: #888;
            font-style: italic;
        }
        .footer { margin-top: 14px; font-size: 9px; color: #999; text-align: right; }
    </style>
</head>
<body>

    {{-- Encabezado empresa --}}
    <div class="header">
        @if($empresa)
            <div class="empresa-nombre">{{ $empresa->nombre }}</div>
            <div class="empresa-info">{{ $empresa->direccion }}</div>
            @if($empresa->telefono)
                <div class="empresa-info">Tel: {{ $empresa->telefono }}</div>
            @endif
            @if($empresa->rfc)
                <div class="empresa-info">RFC: {{ $empresa->rfc }}</div>
            @endif
        @endif
        @if($sucursalUser && $sucursalUser->nombre !== optional($empresa)->nombre)
            <div class="empresa-info" style="margin-top:2px;">Sucursal: {{ $sucursalUser->nombre }}</div>
        @endif
    </div>

    {{-- Título del reporte --}}
    <div class="report-title">
        <h2>Reporte de Aumentos de Inventario</h2>
        <p>Período: {{ $fechaInicioTexto }} &mdash; {{ $fechaFinTexto }}</p>
    </div>

    {{-- Tabla de aumentos --}}
    @if($aumentos->isEmpty())
        <div class="empty-msg">No se registraron aumentos de inventario en el período seleccionado.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Producto</th>
                    <th>Marca</th>
                    <th>Tamaño</th>
                    <th class="num">Stock Anterior</th>
                    <th class="num">Cantidad Agregada</th>
                    <th class="num">Stock Nuevo</th>
                    <th>Usuario</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                @foreach($aumentos as $i => $alta)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ optional($alta->producto)->nombre ?? 'N/D' }}</td>
                        <td>{{ optional(optional($alta->producto)->marca)->nombre ?? 'N/D' }}</td>
                        <td>{{ optional($alta->producto)->tamano ?? '-' }}</td>
                        <td class="num">{{ number_format((float)$alta->cantidad_actual, 2) }}</td>
                        <td class="num" style="color:#1a7a3f; font-weight:bold;">
                            +{{ number_format($alta->cantidad_agregada, 2) }}
                        </td>
                        <td class="num">{{ number_format((float)$alta->cantidad_nueva, 2) }}</td>
                        <td>{{ optional($alta->usuario)->name ?? 'N/D' }}</td>
                        <td>{{ \Carbon\Carbon::parse($alta->created_at)->format('d/m/Y H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="footer">
            Total de registros: {{ $aumentos->count() }} &nbsp;|&nbsp;
            Generado: {{ now()->format('d/m/Y H:i:s') }}
        </div>
    @endif

</body>
</html>
