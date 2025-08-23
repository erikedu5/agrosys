<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
</script>

<template>
    <AppLayout title="Manual de Usuario">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Manual de usuario
            </h2>
        </template>

        <div class="p-6 space-y-6">
            <h1 class="text-2xl font-bold">Manual de Usuario - AgroSys</h1>
            <p>AgroSys es un sistema de inventario, ventas y administración para negocios agrícolas construido con Laravel y Vue 3.</p>
            <p>Las capturas de pantalla mostradas son ilustrativas.</p>

            <section class="space-y-4">
                <h2 class="text-xl font-semibold">Acceso</h2>
                <ol class="list-decimal ml-5 space-y-4">
                    <li>
                        Visita <code>/</code> para la pantalla de inicio de sesión.<br>
                        <img src="https://via.placeholder.com/600x400?text=Captura" alt="Pantalla de inicio de sesión" class="my-2 w-full max-w-[600px]">
                        <p>Utiliza las credenciales proporcionadas por el administrador; en caso de olvidar tu contraseña, solicita su restablecimiento.</p>
                    </li>
                    <li>
                        Tras autenticarte, se muestra el panel principal en <code>/dashboard</code>.<br>
                        <img src="https://via.placeholder.com/600x400?text=Captura" alt="Panel principal" class="my-2 w-full max-w-[600px]">
                        <p>Desde aquí puedes navegar a los distintos módulos mediante el menú superior.</p>
                    </li>
                </ol>
            </section>

            <section class="space-y-4">
                <h2 class="text-xl font-semibold">Módulos principales</h2>

                <div class="space-y-4">
                    <h3 class="text-lg font-semibold">Inventario</h3>
                    <ol class="list-decimal ml-5 space-y-4">
                        <li>
                            Accede al listado de inventario en <code>/inventario</code> para gestionar productos.<br>
                            <img src="https://via.placeholder.com/600x400?text=Captura" alt="Listado de inventario" class="my-2 w-full max-w-[600px]">
                            <p>Desde este panel puedes revisar existencias, filtrar por clasificación o escanear códigos de barras para encontrar artículos rápidamente.</p>
                        </li>
                        <li>
                            Para agregar un producto utiliza <code>/inventario/addInventario</code>.<br>
                            <img src="https://via.placeholder.com/600x400?text=Captura" alt="Alta de inventario" class="my-2 w-full max-w-[600px]">
                            <p>Llena datos como nombre, cantidad inicial y precios de compra y venta; el sistema actualizará automáticamente el stock.</p>
                        </li>
                        <li>
                            Consulta los catálogos de clasificación, enfermedad, tipo de flor y marca según necesites.<br>
                            <img src="https://via.placeholder.com/600x400?text=Captura" alt="Catálogos de inventario" class="my-2 w-full max-w-[600px]">
                            <p>Estos catálogos mantienen la información estandarizada y permiten crear reportes y filtros consistentes.</p>
                        </li>
                    </ol>
                </div>

                <div class="space-y-4">
                    <h3 class="text-lg font-semibold">Ventas y Devoluciones</h3>
                    <ol class="list-decimal ml-5 space-y-4">
                        <li>
                            Gestiona ventas en <code>/venta</code>.<br>
                            <img src="https://via.placeholder.com/600x400?text=Captura" alt="Gestión de ventas" class="my-2 w-full max-w-[600px]">
                            <p>Añade productos al carrito, aplica descuentos y define métodos de pago antes de confirmar la operación.</p>
                        </li>
                        <li>
                            Genera tickets en <code>/ticket/{venta}</code>.<br>
                            <img src="https://via.placeholder.com/600x400?text=Captura" alt="Generación de tickets" class="my-2 w-full max-w-[600px]">
                            <p>El ticket contiene el detalle de la venta y puede enviarse a impresión térmica para el cliente.</p>
                        </li>
                        <li>
                            Maneja devoluciones desde <code>/venta/devoluciones/index</code>.<br>
                            <img src="https://via.placeholder.com/600x400?text=Captura" alt="Gestión de devoluciones" class="my-2 w-full max-w-[600px]">
                            <p>Registra productos devueltos y el sistema ajustará el inventario y el historial de ventas.</p>
                        </li>
                    </ol>
                </div>

                <div class="space-y-4">
                    <h3 class="text-lg font-semibold">Clientes y Pedidos</h3>
                    <ol class="list-decimal ml-5 space-y-4">
                        <li>
                            Administra clientes en <code>/cliente</code>.<br>
                            <img src="https://via.placeholder.com/600x400?text=Captura" alt="Listado de clientes" class="my-2 w-full max-w-[600px]">
                            <p>Captura información de contacto, direcciones y preferencias comerciales para cada cliente.</p>
                        </li>
                        <li>
                            Registra pedidos internos en <code>/pedidos/index</code>.<br>
                            <img src="https://via.placeholder.com/600x400?text=Captura" alt="Pedidos internos" class="my-2 w-full max-w-[600px]">
                            <p>Asocia los pedidos a clientes, define fechas de entrega y monitorea su estado hasta la entrega.</p>
                        </li>
                    </ol>
                </div>

                <div class="space-y-4">
                    <h3 class="text-lg font-semibold">Administración</h3>
                    <ol class="list-decimal ml-5 space-y-4">
                        <li>
                            Gestiona empresas en <code>/empresa</code>.<br>
                            <img src="https://via.placeholder.com/600x400?text=Captura" alt="Administración de empresas" class="my-2 w-full max-w-[600px]">
                            <p>Registra datos fiscales y define parámetros generales de la organización.</p>
                        </li>
                        <li>
                            Administra usuarios en <code>/usuario</code>.<br>
                            <img src="https://via.placeholder.com/600x400?text=Captura" alt="Administración de usuarios" class="my-2 w-full max-w-[600px]">
                            <p>Asigna roles y permisos a cada colaborador para controlar el acceso a los distintos módulos.</p>
                        </li>
                        <li>
                            Controla sucursales en <code>/sucursal</code>, incluyendo el ancho del ticket térmico.<br>
                            <img src="https://via.placeholder.com/600x400?text=Captura" alt="Administración de sucursales" class="my-2 w-full max-w-[600px]">
                            <p>Define datos de contacto y configuraciones específicas para cada punto de venta.</p>
                        </li>
                    </ol>
                </div>

                <div class="space-y-4">
                    <h3 class="text-lg font-semibold">Reportes</h3>
                    <ol class="list-decimal ml-5 space-y-4">
                        <li>
                            Accede al panel de reportes en <code>/reporte</code>.<br>
                            <img src="https://via.placeholder.com/600x400?text=Captura" alt="Panel de reportes" class="my-2 w-full max-w-[600px]">
                            <p>Selecciona rangos de fechas y sucursales para generar estadísticas personalizadas.</p>
                        </li>
                        <li>
                            Genera reportes de ventas, inventario y ventas por producto o marca, con versiones para impresión térmica.<br>
                            <img src="https://via.placeholder.com/600x400?text=Captura" alt="Reportes específicos" class="my-2 w-full max-w-[600px]">
                            <p>Los reportes pueden exportarse a PDF o Excel para su análisis y contabilidad.</p>
                        </li>
                    </ol>
                </div>

                <div class="space-y-4">
                    <h3 class="text-lg font-semibold">Compras, Facturas y API</h3>
                    <ol class="list-decimal ml-5 space-y-4">
                        <li>
                            Registra compras en <code>/compra</code>.<br>
                            <img src="https://via.placeholder.com/600x400?text=Captura" alt="Módulo de compras" class="my-2 w-full max-w-[600px]">
                            <p>Vincula proveedores, especifica costos y cantidades recibidas para mantener actualizado el inventario.</p>
                        </li>
                        <li>
                            Administra facturas en <code>/facturas/index</code>.<br>
                            <img src="https://via.placeholder.com/600x400?text=Captura" alt="Módulo de facturas" class="my-2 w-full max-w-[600px]">
                            <p>Consulta el estado de emisión, descarga los archivos XML y PDF y envía comprobantes a los clientes.</p>
                        </li>
                        <li>
                            Consulta la API pública para sucursales, productos y pedidos mediante la cabecera <code>X-API-KEY</code>.<br>
                            <img src="https://via.placeholder.com/600x400?text=Captura" alt="API pública" class="my-2 w-full max-w-[600px]">
                            <p>La documentación incluye ejemplos de peticiones y respuestas para integraciones externas.</p>
                        </li>
                    </ol>
                </div>
            </section>

            <section class="space-y-4">
                <h2 class="text-xl font-semibold">Roles y seguridad</h2>
                <p>Cada ruta está protegida por roles (<code>vendedor</code>, <code>inventario</code>, <code>admin</code> y <code>superAdmin</code>). Asegúrate de disponer del rol adecuado para acceder a cada módulo.</p>
            </section>

            <section class="space-y-4">
                <h2 class="text-xl font-semibold">Soporte</h2>
                <p>Para más información consulta la documentación Swagger disponible en <code>/api/documentation</code>.</p>
            </section>
        </div>
    </AppLayout>
</template>

