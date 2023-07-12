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
        <div> <?php echo e($empresa->nombre); ?> </div>
        <div> <?php echo e($empresa->direccion); ?> </div>
        <div><strong>Email: </strong> <?php echo e($empresa->email); ?> </div>
        <div><strong>Telefono: </strong> <?php echo e($empresa->telefono); ?> </div>
        <div><strong>RFC: </strong> <?php echo e($empresa->rfc); ?> </div>
    </div>

    <br><br>
    <div style=" text-align: center">
        <div> <strong> Reporte de stock de inventario </strong> </div>
       
        <?php if($clasificacion !== null || $marca !== null): ?> 
        <div> Filtador por 
            <?php if($clasificacion !== null): ?> 
            <span> <?php echo e($clasificacion->nombre); ?> </span> 
            <?php endif; ?>

            <?php if($marca !== null): ?> 
            <span> <?php echo e($marca->nombre); ?> </span> 
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div>Con fecha de creación del reporte <?php echo e($fechaCreacion); ?></div>
    </div>
        <br><br>
    <div>
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
                <?php $__currentLoopData = $inventario; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $producto): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td class="px-4 py-2">  <?php echo e($producto->id); ?> </td>
                        <td class="px-4 py-2">  <?php echo e($producto->nombre); ?> </td>
                        <td class="px-4 py-2">  <?php echo e($producto->marca->nombre); ?> </td>
                        <td class="px-4 py-2">  <?php echo e($producto->tamano); ?> </td>
                        <td class="px-4 py-2">  <?php echo e($producto->cantidad); ?> </td>
                        <td class="px-4 py-2">  <?php echo e($producto->precio_unitario); ?> </td>
                        <td class="px-4 py-2">  <?php echo e($producto->ieps); ?> </td>
                        <td class="px-4 py-2">  <?php echo e($producto->precio_ieps); ?> </td>
                        <td class="px-4 py-2">  <?php echo e($producto->updated_at); ?> </td>
                        <td class="px-4 py-2">  <?php echo e($producto->usuario->name); ?> </td>
                        </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
    <br>
</body>
</html><?php /**PATH /Users/erikeduardojimenez/Documents/workspace-tecualoyanSys/agrosys/resources/views/reportes/inventario.blade.php ENDPATH**/ ?>