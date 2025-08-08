<!doctype html>
<html>

<head>
    <meta charset="utf-8" />
    <style>
        @page {
            margin: 6px;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;

            width: {
                    {
                    $ticketWidthMm ?? 80
                }
            }

            mm;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .line {
            border-bottom: 1px dashed #000;
            margin: 4px 0;
        }

        .row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }

        .small {
            font-size: 10px;
        }

        .mt {
            margin-top: 6px;
        }

        .truncate {
            max-width: 50mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
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
        @if(!empty($empresa->direccion))<div class="small">{{ $empresa->direccion }}</div>@endif
        @if(!empty($empresa->telefono))<div class="small">Tel: {{ $empresa->telefono }}</div>@endif
        @if(!empty($empresa->rfc))<div class="small">RFC: {{ $empresa->rfc }}</div>@endif
        <div class="small">Vendedor: {{ $usuario->name ?? '' }}</div>
    </div>

    <div class="line"></div>
    <div class="small">Cliente: <span class="bold">{{ $cliente->nombre ?? 'Público general' }}</span></div>
    <div class="small">Fecha: {{ \Carbon\Carbon::parse($venta->created_at)->format('Y-m-d H:i') }}</div>
    <div class="small">Tipo: {{ $venta->tipo_venta }}</div>
    <div class="small">Folio: {{ $venta->id }}</div>

    <div class="line"></div>
    <div class="bold small">Productos</div>
    @foreach ($productos as $p)
    <div class="row mt">
        <span class="truncate">{{ $p->detail->nombre }}</span>
        <span class="right">$ {{ number_format($p->total_productos, 2) }}</span>
    </div>
    <div class="small">{{ $p->cantidad }} x $ {{ number_format($p->total_productos / max($p->cantidad,1), 2) }}</div>
    @endforeach

    <div class="line"></div>
    <div class="row bold">
        <span>Total</span>
        <span>$ {{ number_format($venta->total, 2) }}</span>
    </div>

    @foreach($abonos as $abono)
    <div class="row small mt">
        <span>Abono {{ \Carbon\Carbon::parse($abono->created_at)->format('Y-m-d') }}</span>
        <span class="bold">$ {{ number_format($abono->cantidad_abonada, 2) }}</span>
    </div>
    @endforeach

    @if(isset($cliente->balance))
    <div class="row small mt">
        <span>Saldo</span>
        <span class="bold">$ {{ number_format($cliente->balance, 2) }}</span>
    </div>
    @endif

    <div class="line"></div>
    @if(!empty($empresa->aviso))
    <div class="center small">{{ $empresa->aviso }}</div>
    @else
    <div class="center small">Gracias por su compra</div>
    @endif
</body>

</html>