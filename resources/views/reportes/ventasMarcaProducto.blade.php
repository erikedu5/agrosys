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

        th, td {
        text-align: left;
        padding: 8px;
        }

        tr:nth-child(even) {background-color: #f2f2f2;}
    </style>
</head>
<body>

    <div>
        <div style=" text-align: center"> {{ $empresa->nombre}} </div>
        <div style=" text-align: center"> {{ $empresa->direccion}} </div>
        <div style=" text-align: center"><strong>Email: </strong> {{ $empresa->email}} </div>
        <div style=" text-align: center"><strong>Telefono: </strong> {{ $empresa->telefono}} </div>
        <div style=" text-align: center"><strong>RFC: </strong> {{ $empresa->rfc}} </div>
    </div>

    <br><br>
    <div style=" text-align: left"> Se vendieron <strong>  {{ $productosVendidos }} </strong> productos del filtro <strong> {{ $nameFilter }} </strong>  dentro del rango de fechas </div>
    <br><br>

    @foreach ($ventas as $venta)

    <hr class="my-6">
    <div style="margin-bottom:25px;"><strong>Vendedor:</strong> {{ $venta->usuario->name }}</div>
    <div style="margin-bottom:25px;"><strong>Cliente:</strong> {{ $venta->cliente->nombre }}</div>
    <div style="margin-bottom:25px;"><strong>Fecha de Venta:</strong> {{ $venta->created_at }}</div>
    <div style="margin-bottom:25px;"><strong>Tipo de venta:</strong> {{ $venta->tipo_venta }}</div>

    <div>
        <table>
            <thead>
                <tr>
                    <th>Id</th>
                    <th>Nombre del producto</th>
                    <th>Marca</th>
                    <th>Cantidad</th>
                    <th>Precio unitario</th>
                    <th>Importe</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($venta->productos as $producto)
                    <tr>
                    <td class="px-4 py-2">  {{ $producto->detail->id }} </td>
                    <td class="px-4 py-2">  {{ $producto->detail->nombre }} </td>
                    <td class="px-4 py-2">  {{ $producto->detail->marca->nombre }} </td>
                    <td class="px-4 py-2">  {{ $producto->cantidad }} </td>
                    <td class="px-4 py-2">  {{ $producto->total_productos/$producto->cantidad }} </td>
                    <td class="px-4 py-2">  {{ $producto->total_productos }} </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <br>
    <div style="margin-bottom:25px; text-align: right"><strong>Total de la venta:</strong> {{ $venta->total }}</div>
    @endforeach
</body>
</html>
