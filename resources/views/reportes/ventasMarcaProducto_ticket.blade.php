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
    <title>Ventas por Producto/Marca</title>
    <script>
        setTimeout(function(){ window.print && window.print(); }, 300);
    </script>
</head>
<body>
    <div class="center">
        <div class="bold">{{ $empresa->nombre ?? 'Empresa' }}</div>
        <div class="small">VENTAS POR {{ strtoupper($nameFilter) }}</div>
    </div>
    <div class="line"></div>
    <div class="row"><span class="bold">Vendidos</span><span class="bold">{{ $productosVendidos }}</span></div>
    <div class="line"></div>
    <div class="bold small">Ventas</div>
    @foreach($ventas as $v)
        <div class="mt">
            <div class="row"><span>#{{ $v->id }}</span><span>$ {{ number_format($v->total, 2) }}</span></div>
            <div class="small">{{ \Carbon\Carbon::parse($v->created_at)->format('Y-m-d H:i') }}</div>
        </div>
    @endforeach
    <div class="line"></div>
    <div class="center small">Generado por Agrosys</div>
</body>
</html>
