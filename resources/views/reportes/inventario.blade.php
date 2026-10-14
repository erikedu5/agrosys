<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Inventario</title>
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

    <div style=" text-align: center">
        <div> {{ $empresa->nombre}} </div>
        <div> {{ $empresa->direccion}} </div>
        <div><strong>Email: </strong> {{ $empresa->email}} </div>
        <div><strong>Telefono: </strong> {{ $empresa->telefono}} </div>
        <div><strong>RFC: </strong> {{ $empresa->rfc}} </div>
    </div>

    <br><br>
    <div style=" text-align: center">
        <div> <strong> Reporte de stock de inventario </strong> </div>
       
        @if ($clasificacion !== null || $marca !== null) 
        <div> Filtador por 
            @if ($clasificacion !== null) 
            <span> {{ $clasificacion->nombre }} </span> 
            @endif

            @if ($marca !== null) 
            <span> {{ $marca->nombre }} </span> 
            @endif
        </div>
        @endif

        <div>Con fecha de creación del reporte {{ $fechaCreacion }}</div>
    </div>
        <br><br>
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Id</th>
                    <th>Nombre del producto</th>
                    <th>Marca</th>
                    <th>Tamaño</th>
                    <th>Cantidad en stock</th>
                    <th>Precio unitario</th>
                    <th>IEP</th>
                    <th>Precio IEP</th>
                    <th>Ultima actualizacion</th>
                    <th>Usuario que actualizo por última vez</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($inventario as $producto)
                    <tr>
                        <td class="px-4 py-2">  {{ $producto->id }} </td>
                        <td class="px-4 py-2">  {{ $producto->nombre }} </td>
                        <td class="px-4 py-2">  {{ $producto->marca->nombre }} </td>
                        <td class="px-4 py-2">  {{ $producto->tamano }} </td>
                        <td class="px-4 py-2">  {{ $producto->cantidad }} </td>
                        <td class="px-4 py-2">  {{ $producto->precio_unitario }} </td>
                        <td class="px-4 py-2">  {{ $producto->ieps }} </td>
                        <td class="px-4 py-2">  {{ $producto->precio_ieps }} </td>
                        <td class="px-4 py-2">  {{ $producto->updated_at }} </td>
                        <td class="px-4 py-2">  {{ $producto->usuario->name }} </td>
                        </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <br>
</body>
</html>