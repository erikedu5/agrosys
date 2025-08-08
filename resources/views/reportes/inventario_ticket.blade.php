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
        .truncate { max-width: 56mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    </style>
    <title>Inventario Ticket</title>
    <script>
        setTimeout(function(){ window.print && window.print(); }, 300);
    </script>
</head>
<body>
    <div class="center">
        <div class="bold">{{ $empresa->nombre ?? 'Empresa' }}</div>
        <div class="small">REPORTE DE INVENTARIO</div>
    </div>
    <div class="line"></div>
    @foreach($inventario as $p)
        <div class="row mt">
            <span class="truncate">{{ $p->nombre }} @if($p->marca) - {{ $p->marca->nombre }} @endif</span>
            <span class="bold">{{ $p->cantidad }}</span>
        </div>
    @endforeach
    <div class="line"></div>
    <div class="center small">Generado por Agrosys</div>
</body>
</html>
