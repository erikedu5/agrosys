<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    version: String,
});
</script>

<template>
    <Head title="Aviso de Privacidad" />

    <div class="min-h-screen bg-white text-slate-900">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold tracking-tight">Aviso de Privacidad Integral</h1>
                    <p class="mt-2 text-sm text-slate-500">Versión: {{ version }}</p>
                </div>
                <Link :href="route('plans.index')" class="text-sm font-semibold text-emerald-700 hover:underline">
                    Ver planes
                </Link>
            </div>

            <div class="prose prose-slate max-w-none mt-8">
                <h2>1. Responsable del tratamiento</h2>
                <p>
                    <b>Pixka consultores</b>, operador de Agrosys, es responsable del tratamiento de los datos personales
                    recabados mediante la plataforma. Para cualquier asunto relacionado con privacidad y protección de datos
                    puede escribir a <a href="mailto:pixkaconsultores@gmail.com">pixkaconsultores@gmail.com</a>.
                </p>

                <h2>2. Datos personales tratados</h2>
                <p>Dependiendo del uso de la plataforma, podemos tratar las siguientes categorías:</p>
                <ul>
                    <li>Identificación y contacto: nombre, correo electrónico, teléfono y credenciales de acceso.</li>
                    <li>Datos laborales: empresa, sucursal, puesto, rol y permisos asignados.</li>
                    <li>Datos comerciales: clientes, proveedores, productos, ventas, compras, adeudos y movimientos de inventario.</li>
                    <li>Datos fiscales y de facturación: RFC, razón social, régimen fiscal, domicilio fiscal, uso de CFDI y correo de recepción.</li>
                    <li>Datos técnicos: dirección IP, dispositivo, navegador, identificadores de sesión, registros de acceso, errores y sincronización.</li>
                    <li>Datos de suscripción y pago. Agrosys no necesita almacenar el número completo de una tarjeta cuando el cobro lo procesa un proveedor externo.</li>
                </ul>
                <p>
                    La plataforma no solicita datos personales sensibles como requisito ordinario. Se pide a los usuarios no
                    capturar información sensible en campos abiertos o notas.
                </p>

                <h2>3. Finalidades necesarias</h2>
                <ul>
                    <li>Crear y administrar cuentas, empresas, sucursales, roles y sesiones.</li>
                    <li>Prestar las funciones contratadas de inventario, ventas, clientes, reportes, facturación y suscripciones.</li>
                    <li>Procesar y conciliar pagos, emitir comprobantes y atender obligaciones fiscales o contractuales.</li>
                    <li>Brindar soporte, responder solicitudes y comunicar incidentes o cambios relevantes del servicio.</li>
                    <li>Prevenir fraude, accesos no autorizados y mantener la seguridad, disponibilidad e integridad de la plataforma.</li>
                    <li>Cumplir obligaciones legales y atender requerimientos debidamente fundados de autoridades competentes.</li>
                </ul>

                <h2>4. Funcionamiento sin conexión y almacenamiento local</h2>
                <p>
                    Cuando el modo sin conexión está habilitado, Agrosys puede guardar temporalmente en el dispositivo información
                    necesaria para continuar operando, como datos de sesión autorizada, catálogo de productos, precios, existencias
                    estimadas, clientes, ventas, partidas, pagos y movimientos pendientes de sincronización.
                </p>
                <p>
                    Esta información se almacena en mecanismos locales del navegador o de la aplicación instalada y se transmite
                    al servidor cuando vuelve a existir una conexión utilizable. El usuario debe proteger el dispositivo con clave
                    o bloqueo de pantalla, evitar equipos públicos o compartidos y notificar oportunamente su pérdida, robo o uso no
                    autorizado. Cerrar sesión, borrar los datos del sitio o desinstalar la aplicación puede eliminar información local
                    todavía no sincronizada.
                </p>

                <h2>5. Tecnologías de seguimiento</h2>
                <p>
                    Utilizamos cookies, almacenamiento local y tecnologías equivalentes para autenticar sesiones, recordar la sucursal,
                    habilitar el modo offline, conservar preferencias y proteger la plataforma. También pueden utilizarse herramientas
                    de medición para conocer el desempeño y uso general del sitio. Estas últimas pueden ser bloqueadas por el navegador
                    sin impedir las funciones esenciales, aunque algunas métricas dejarán de recopilarse.
                </p>

                <h2>6. Encargados y transferencias</h2>
                <p>
                    Los datos pueden ser tratados por proveedores que prestan servicios de infraestructura, alojamiento, correo,
                    soporte, analítica, facturación electrónica y procesamiento de pagos, bajo instrucciones de Agrosys y medidas
                    contractuales de confidencialidad y seguridad. También podrán comunicarse a autoridades cuando exista una
                    obligación o requerimiento legal. Si una transferencia requiere consentimiento, éste será solicitado antes de realizarla.
                </p>

                <h2>7. Conservación y seguridad</h2>
                <p>
                    Conservamos la información mientras la cuenta o relación contractual esté vigente y durante los plazos posteriores
                    necesarios para obligaciones fiscales, contables, contractuales, seguridad y defensa de derechos. Después se elimina,
                    bloquea o anonimiza conforme resulte aplicable. Mantenemos medidas administrativas, técnicas y físicas razonables;
                    ningún sistema puede garantizar seguridad absoluta.
                </p>

                <h2>8. Derechos ARCO, revocación y limitación</h2>
                <p>
                    La persona titular puede solicitar acceso, rectificación, cancelación u oposición al tratamiento de sus datos,
                    así como revocar su consentimiento o limitar su uso y divulgación. La solicitud deberá identificar al titular,
                    acreditar su identidad, describir los datos y el derecho que desea ejercer e indicar un medio para recibir respuesta.
                    Las solicitudes deben enviarse a
                    <a href="mailto:pixkaconsultores@gmail.com">pixkaconsultores@gmail.com</a>.
                </p>

                <h2>9. Datos tratados por las empresas usuarias</h2>
                <p>
                    Cada empresa que utiliza Agrosys es responsable de contar con una base jurídica y proporcionar sus propios avisos
                    de privacidad respecto de los datos de empleados, clientes, proveedores u otras personas que capture en la plataforma.
                    En esos casos, Agrosys actúa como proveedor tecnológico o encargado conforme a las instrucciones de la empresa usuaria.
                </p>

                <h2>10. Cambios al aviso</h2>
                <p>
                    Las modificaciones se publicarán en esta misma dirección indicando una nueva versión. Cuando el cambio sea relevante,
                    podrá notificarse dentro de la plataforma y solicitarse una nueva aceptación.
                </p>
            </div>
        </div>
    </div>
</template>
