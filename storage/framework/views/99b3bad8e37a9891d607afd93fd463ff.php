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
        <div style=" text-align: center"><strong>Vendedor: </strong> <?php echo e($usuario->name); ?> </div>
    </div>

    <br><br>

    <div style="margin-bottom:25px;"><strong>Cliente:</strong> <?php echo e($cliente->nombre); ?></div>
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
                <?php $__currentLoopData = $productos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $producto): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
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
    <br>

    <?php $__currentLoopData = $abonos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $abono): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div style="margin-bottom:25px; text-align: right">Se abono: <strong> $<?php echo e($abono->cantidad_abonada); ?> </strong> el dia <?php echo e($abono->created_at); ?></div>
        <br>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    
    <div style="margin-bottom:25px; text-align: right">Tiene un adeudo de $<?php echo e($cliente->balance); ?></div>
    <br>
    
    <div style=" text-align: center"> <?php echo e($empresa->aviso); ?> </div>
</body>
</html><?php /**PATH /Users/erikeduardojimenez/Documents/workspace-tecualoyanSys/agrosys/resources/views/reportes/venta.blade.php ENDPATH**/ ?>