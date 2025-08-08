<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Venta</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            text-align: left;
            padding: 8px;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>

    <div>
        <div style=" text-align: center"> {{ $empresa->nombre}} </div>
        <div style=" text-align: center"> {{ $empresa->direccion}} </div>
        <div style=" text-align: center"><strong>Email: </strong> {{ $empresa->email}} </div>
        <div style=" text-align: center"><strong>Telefono: </strong> {{ $empresa->telefono}} </div>
        <div style=" text-align: center"><strong>RFC: </strong> {{ $empresa->rfc}} </div>
        <div style=" text-align: center"><strong>Vendedor: </strong> {{ $usuario->name}} </div>
    </div>

    <br><br>

    <div style="margin-bottom:25px;"><strong>Cliente:</strong> {{ $cliente->nombre }}</div>
    <div style="margin-bottom:25px;"><strong>Fecha de Venta:</strong> {{ $venta->created_at }}</div>
    <div style="margin-bottom:25px;"><strong>Tipo de venta:</strong> {{ $venta->tipo_venta }}</div>
    <div style="margin-bottom:25px;"><strong>Folio: </strong> {{ $venta->id }}</div>

    <div>
        <table>
            <thead>
                <tr>
                    <th>Id</th>
                    <th>Nombre del producto</th>
                    <th>Cantidad</th>
                    <th>Precio unitario</th>
                    <th>Importe</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($productos as $producto)
                <tr>
                    <td class=" px-4 py-2"> {{ $producto->detail->id }} </td>
                    <td class="px-4 py-2"> {{ $producto->detail->nombre }} </td>
                    <td class="px-4 py-2"> {{ $producto->cantidad }} </td>
                    <td class="px-4 py-2"> {{ $producto->total_productos/$producto->cantidad }} </td>
                    <td class="px-4 py-2"> {{ $producto->total_productos }} </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <br>
    <div style="margin-bottom:25px; text-align: right"><strong>Total de la venta:</strong> {{ $venta->total }}</div>
    <br>

    @foreach($abonos as $abono)
    <div style="margin-bottom:25px; text-align: right">Se abono: <strong> ${{ $abono->cantidad_abonada }} </strong> el dia {{$abono->created_at }}</div>
    <br>
    @endforeach

    <div style="margin-bottom:25px; text-align: right">Tiene un adeudo de ${{ $cliente->balance }}</div>
    <br>

    <div style=" text-align: center"> {{ $empresa->aviso}} </div>
</body>

</html>