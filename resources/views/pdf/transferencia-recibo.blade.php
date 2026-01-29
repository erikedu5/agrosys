<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Transferencia</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
        }

        .header h1 {
            color: #1e40af;
            font-size: 24px;
            margin-bottom: 5px;
        }

        .header p {
            color: #64748b;
            font-size: 11px;
        }

        .info-section {
            margin-bottom: 25px;
        }

        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .info-row {
            display: table-row;
        }

        .info-cell {
            display: table-cell;
            padding: 8px;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
        }

        .info-label {
            font-weight: bold;
            width: 30%;
            background-color: #e0e7ff;
            color: #1e40af;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }

        .status-completado {
            background-color: #d1fae5;
            color: #065f46;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        thead {
            background-color: #1e40af;
            color: white;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
            border: 1px solid #e2e8f0;
        }

        th {
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }

        tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .totals {
            margin-top: 15px;
            text-align: right;
            font-size: 13px;
        }

        .totals strong {
            font-size: 15px;
            color: #1e40af;
        }

        .signatures {
            margin-top: 60px;
            display: table;
            width: 100%;
        }

        .signature-box {
            display: table-cell;
            width: 48%;
            text-align: center;
            padding: 10px;
        }

        .signature-line {
            border-top: 2px solid #333;
            margin-top: 40px;
            padding-top: 8px;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 10px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 5px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>RECIBO DE TRANSFERENCIA</h1>
        <p>Documento de Traspaso de Mercancía entre Sucursales</p>
    </div>

    <div class="info-section">
        <h3 class="section-title">Información de la Transferencia</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-cell info-label">Folio:</div>
                <div class="info-cell">{{ $transferencia->folio ?? 'N/A' }}</div>
                <div class="info-cell info-label">Estado:</div>
                <div class="info-cell">
                    <span class="status-badge status-completado">{{ strtoupper($transferencia->status) }}</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-cell info-label">Sucursal Origen:</div>
                <div class="info-cell">{{ $transferencia->sucursalOrigen->nombre ?? 'N/A' }}</div>
                <div class="info-cell info-label">Sucursal Destino:</div>
                <div class="info-cell">{{ $transferencia->sucursalDestino->nombre ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <div class="info-section">
        <h3 class="section-title">Información de Usuarios</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-cell info-label">Enviado por:</div>
                <div class="info-cell">{{ $transferencia->usuarioEnvia->name ?? 'N/A' }}</div>
                <div class="info-cell info-label">Fecha de Envío:</div>
                <div class="info-cell">
                    {{ $transferencia->fecha_envio ? $transferencia->fecha_envio->format('d/m/Y H:i') : 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-cell info-label">Recibido por:</div>
                <div class="info-cell">{{ $transferencia->usuarioRecibe->name ?? 'N/A' }}</div>
                <div class="info-cell info-label">Fecha de Recepción:</div>
                <div class="info-cell">
                    {{ $transferencia->fecha_recepcion ? $transferencia->fecha_recepcion->format('d/m/Y H:i') : 'N/A' }}
                </div>
            </div>
        </div>
    </div>

    @if($transferencia->notas)
        <div class="info-section">
            <h3 class="section-title">Observaciones</h3>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-cell" style="width: 100%;">{{ $transferencia->notas }}</div>
                </div>
            </div>
        </div>
    @endif

    <div class="info-section">
        <h3 class="section-title">Detalle de Productos Transferidos</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Producto</th>
                    <th style="text-align: center;">Cantidad</th>
                    <th style="text-align: center;">Lote Origen</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transferencia->detalles as $index => $detalle)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $detalle->producto->nombre ?? 'N/A' }}</td>
                        <td style="text-align: center;">{{ number_format($detalle->cantidad, 2) }}</td>
                        <td style="text-align: center;">
                            {{ $detalle->lote_origen_id ? '#' . $detalle->lote_origen_id : 'Inicial' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <strong>Total de Items Transferidos: {{ number_format($totalItems, 2) }}</strong>
        </div>
    </div>

    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line">
                <strong>{{ $transferencia->usuarioEnvia->name ?? '_____________________' }}</strong><br>
                Firma de quien Envía
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                <strong>{{ $transferencia->usuarioRecibe->name ?? '_____________________' }}</strong><br>
                Firma de quien Recibe
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Este es un documento generado automáticamente por el sistema de gestión.</p>
        <p>Fecha de impresión: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>

</html>