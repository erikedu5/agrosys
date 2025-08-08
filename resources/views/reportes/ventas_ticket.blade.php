<!doctype html>
<html>
<head>
    <meta charset="utf-8" />
    <style>
        @page { margin: 6px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; width: {{ $ticketWidthMm ?? 80 }}mm; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .line { border-bottom: 1px dashed #000; margin: 4px 0; }
        .row { display: flex; justify-content: space-between; }
        .small { font-size: 10px; }
        .mt { margin-top: 6px; }
    </style>
    <title>Reporte Ventas Ticket</title>
    <script type="text/php">
        if (isset($pdf)) { $pdf->get_canvas()->page_script(''); }
    </script>
    <script>
        setTimeout(function(){ window.print && window.print(); }, 300);
    </script>
    </head>
<body>
    <div class="center">
        <div class="bold">{{ $empresa->nombre ?? 'Empresa' }}</div>
        <div class="small">REPORTE DE VENTAS</div>
        <div class="small">{{ $fechaInicioFmt }} a {{ $fechaFinFmt }}</div>
    </div>
    <div class="line"></div>
    <div class="row"><span>Contado</span><span class="bold">$ {{ number_format($montoContado, 2) }}</span></div>
    <div class="row"><span>Crédito</span><span class="bold">$ {{ number_format($montoCredito, 2) }}</span></div>
    <div class="row bold"><span>Total</span><span>$ {{ number_format($montoContado + $montoCredito, 2) }}</span></div>
    <div class="line"></div>
    <div class="bold small">Detalle</div>
    @foreach($ventas as $v)
        <div class="mt">
            <div class="row"><span>#{{ $v->id }}</span><span>$ {{ number_format($v->total, 2) }}</span></div>
            <div class="small">{{ \Carbon\Carbon::parse($v->created_at)->format('Y-m-d H:i') }} - {{ $v->tipo_venta }}</div>
        </div>
    @endforeach
    <div class="line"></div>
    <div class="center small">Generado por Agrosys</div>
</body>
</html>
