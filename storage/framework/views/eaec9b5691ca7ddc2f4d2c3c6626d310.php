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
        <div style=" text-align: center"> <?php echo e($empresa->nombre); ?> </div>
        <div style=" text-align: center"> <?php echo e($empresa->direccion); ?> </div>
        <div style=" text-align: center"><strong>Email: </strong> <?php echo e($empresa->email); ?> </div>
        <div style=" text-align: center"><strong>Telefono: </strong> <?php echo e($empresa->telefono); ?> </div>
        <div style=" text-align: center"><strong>RFC: </strong> <?php echo e($empresa->rfc); ?> </div>
    </div>

    <br><br>

        <div style=" text-align: left"> <strong>Reporte de ventas correspondiente rango del  </strong> <?php echo e($fechaInicio); ?> <strong> al </strong> <?php echo e($fechaFin); ?> </div>
        <br>

        <div style=" text-align: left"> <strong> Monto total de ventas de contado: </strong>  $ <?php echo e($montoContado); ?>  </div>
        <br>

        <div style=" text-align: left"> <strong> Monto total de ventas de credito: </strong> $ <?php echo e($montoCredito); ?> </div>
        <br>

        <div style=" text-align: left"> <strong> Monto total de ventas del rango: </strong> $ <?php echo e($montoCredito + $montoContado); ?> </div>
        <br>

        <div style=" text-align: left"> <strong> Abonos recibidos dentro del rango de fechas: </strong> $ <?php echo e($totalAbonadoRango); ?> </div>
        <br>

    <br>
    
    <?php $__currentLoopData = $ventas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $venta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

    <hr class="my-6">
    <div style="margin-bottom:25px;"><strong>Vendedor:</strong> <?php echo e($venta->usuario->name); ?></div>
    <div style="margin-bottom:25px;"><strong>Cliente:</strong> <?php echo e($venta->cliente->nombre); ?></div>
    <div style="margin-bottom:25px;"><strong>Fecha de Venta:</strong> <?php echo e($venta->created_at); ?></div>
    <div style="margin-bottom:25px;"><strong>Tipo de venta:</strong> <?php echo e($venta->tipo_venta); ?></div>
    
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
                <?php $__currentLoopData = $venta->productos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $producto): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                    <td class="px-4 py-2">  <?php echo e($producto->detail->id); ?> </td>
                    <td class="px-4 py-2">  <?php echo e($producto->detail->nombre); ?> </td>
                    <td class="px-4 py-2">  <?php echo e($producto->cantidad); ?> </td>
                    <td class="px-4 py-2">  <?php echo e($producto->total_productos/$producto->cantidad); ?> </td>
                    <td class="px-4 py-2">  <?php echo e($producto->total_productos); ?> </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
    <br>
    <div style="margin-bottom:25px; text-align: right"><strong>Total de la venta:</strong> <?php echo e($venta->total); ?></div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</body>
</html><?php /**PATH /Users/erikeduardojimenez/Documents/workspace-tecualoyanSys/agrosys/resources/views/reportes/ventas.blade.php ENDPATH**/ ?>