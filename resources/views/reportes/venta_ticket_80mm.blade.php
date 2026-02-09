<!doctype html>
<html>

<head>
    <meta charset="utf-8" />
    <style>
        @page {margin: 4px; padding: 10px {{$ticketWidthMm == 80 ? 10 : 5 }}px;}
        body {
            font-family: Consolas, "Courier New", monospace;
            font-size: {{$ticketWidthMm == 80 ? 14 : 11 }}px
            width: {{ $ticketWidthMm ?? 58 }}mm;
        }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .small { font-size: {{$ticketWidthMm == 80 ? 11 : 10 }}px; }
        hr { border: none; border-top: 1px dashed #000; margin: 4px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 4px 0; vertical-align: top; }
    </style>
    <title>Ticket de Venta</title>
    <script>
        setTimeout(function() {
            window.print && window.print();
        }, 300);
    </script>
</head>

<body>
    <div class="center">
        <div class="bold">{{ $empresa->nombre ?? 'Empresa' }}</div>
        @if(isset($sucursal) && !empty($sucursal->direccion))
            <div class="small">{{ $sucursal->direccion }}</div>
        @elseif(!empty($empresa->direccion))
            <div class="small">{{ $empresa->direccion }}</div>
        @endif
        @if(!empty($empresa->telefono))<div class="small">Tel: {{ $empresa->telefono }}</div>@endif
        @if(!empty($empresa->rfc))<div class="small">RFC: {{ $empresa->rfc }}</div>@endif
    </div>

    <hr>
    <div class="small">Fecha: {{ \Carbon\Carbon::parse($venta->created_at)->format('Y-m-d H:i') }}</div>
    <div class="small">Folio: {{ $venta->id }}</div>
    <div class="small">Tipo: {{ $venta->tipo_venta }}</div>
    <div class="small">Cliente: <span class="bold">{{ $cliente->nombre ?? 'Público general' }}</span></div>
    <div class="small">Vendedor: {{ $usuario->name ?? '' }}</div>

    <hr>
    <div class="bold small">Productos</div>
    <table>
        @foreach ($productos as $p)
            <tr>
                <td colspan="2" class="small">{{ $p->detail->nombre }}</td>
            </tr>
            <tr>
                <td class="small">{{ $p->cantidad }} x $ {{ number_format($p->total_productos / max($p->cantidad,1), 2) }}</td>
                <td class="right small">$ {{ number_format($p->total_productos, 2) }}</td>
            </tr>
        @endforeach
    </table>

    <hr>
    <table>
        <tr class="bold">
            <td>Total</td>
            <td class="right">$ {{ number_format($venta->total, 2) }}</td>
        </tr>
    </table>

    @foreach($abonos as $abono)
        <table class="small">
            <tr>
                <td>Abono {{ \Carbon\Carbon::parse($abono->created_at)->format('Y-m-d') }}</td>
                <td class="right bold">$ {{ number_format($abono->cantidad_abonada, 2) }}</td>
            </tr>
        </table>
    @endforeach

    @if(isset($cliente->balance))
        <table class="small">
            <tr>
                <td>Saldo</td>
                <td class="right bold">$ {{ number_format($cliente->balance, 2) }}</td>
            </tr>
        </table>
    @endif

    <hr>
    @if(!empty($empresa->aviso))
        <div class="center small">{{ $empresa->aviso }}</div>
    @else
        <div class="center small">Gracias por su compra</div>
    @endif

    <br>
    <div style="width: 100%; border-bottom: dotted #000;"></div>
</body>

</html>
