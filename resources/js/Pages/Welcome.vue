<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="Bienvenido a Agrosys" />

    <div class="min-h-screen bg-white font-sans text-gray-900 selection:bg-orange-500 selection:text-white">
        <!-- Navigation -->
        <nav class="absolute top-0 left-0 right-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20 items-center">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 flex items-center gap-2">
                             <!-- Logo Placeholder or Icon -->
                             <svg class="h-10 w-10 text-white drop-shadow-md" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2L2 7l10 5 10-5-10-5zm0 9l2.5-1.25L12 8.5l-2.5 1.25L12 11zm0 2.5l-5-2.5-5 2.5L12 22l10-8.5-5-2.5-5 2.5z"/>
                            </svg>
                            <span class="font-bold text-2xl text-white tracking-tight drop-shadow-md">Agrosys</span>
                        </div>
                    </div>
                    <div class="hidden md:flex items-center space-x-8">
                        <Link :href="route('login')" class="px-6 py-2 rounded-lg bg-white text-green-700 font-bold hover:bg-gray-100 transition duration-300 ease-in-out shadow-md">
                            Ingresar
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="relative bg-white overflow-hidden">
             <div class="absolute inset-0">
                <img class="w-full h-full object-cover" src="/images/hero.png" alt="Campo agrícola moderno con flores">
                <!-- Capa oscura ligeramente verde para contraste, similar a estilo logístico pero manteniendo frescura -->
                <div class="absolute inset-0 bg-gray-900/40 mix-blend-multiply"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-90"></div>
            </div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-32 pb-24 sm:pb-32 lg:pb-40">
                <div class="lg:w-2/3">
                    <h1 class="text-4xl tracking-tight font-extrabold text-white sm:text-5xl md:text-6xl lg:text-7xl drop-shadow-lg">
                        <span class="block">Tu cosecha digital</span>
                        <span class="block text-orange-400">sin perder el control</span>
                    </h1>
                    <p class="mt-6 max-w-lg text-xl text-gray-100 sm:max-w-3xl font-medium drop-shadow-md">
                        Optimiza tu inventario, controla tus ventas y gestiona tu negocio agrícola con la plataforma que combina tecnología y naturaleza.
                    </p>
                    <div class="mt-10 max-w-sm sm:flex sm:max-w-none gap-4">
                        <Link :href="route('plans.index')" class="flex items-center justify-center px-8 py-3 border border-transparent text-base font-bold rounded-lg text-white bg-orange-500 hover:bg-orange-600 md:py-4 md:text-lg md:px-10 shadow-lg transform transition hover:-translate-y-1">
                            Ver planes
                        </Link>
                        <Link :href="route('subscription.onboarding')" class="mt-3 sm:mt-0 flex items-center justify-center px-8 py-3 border border-white text-base font-bold rounded-lg text-white hover:bg-white hover:text-green-800 md:py-4 md:text-lg md:px-10 transition-colors bg-white/10 backdrop-blur-sm">
                            Iniciar prueba gratis
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div id="features" class="py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h2 class="text-lg leading-6 font-bold text-orange-500 uppercase tracking-wider">
                        Características
                    </h2>
                    <h3 class="mt-2 text-3xl leading-8 font-extrabold text-green-800 sm:text-4xl">
                        Todo lo que necesitas para crecer
                    </h3>
                    <p class="mt-4 max-w-2xl text-xl text-gray-500 mx-auto">
                        Herramientas diseñadas específicamente para el sector agrícola y floricultor.
                    </p>
                </div>

                <div class="mt-16">
                    <!-- Highlights -->
                    <div class="space-y-16 md:space-y-0 md:grid md:grid-cols-2 md:gap-x-8 md:gap-y-16 mb-20">
                        <!-- Feature 1 -->
                        <div class="relative flex flex-col gap-4">
                            <div class="flex items-center gap-4">
                                <div class="flex items-center justify-center h-12 w-12 rounded-xl bg-green-100 text-green-600">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                                <h4 class="text-2xl font-bold text-gray-900">Inventario Inteligente</h4>
                            </div>
                            <p class="mt-2 text-base text-gray-500 leading-relaxed">
                                Mantén el rastro de cada producto con nuestro sistema Kárdex. Monitorea entradas, salidas y caducidad de lotes en tiempo real.
                            </p>
                             <img class="mt-4 rounded-2xl shadow-xl border border-gray-100" src="/images/inventory.png" alt="Inventario">
                        </div>

                        <!-- Feature 2 -->
                         <div class="relative flex flex-col gap-4">
                            <div class="flex items-center gap-4">
                                <div class="flex items-center justify-center h-12 w-12 rounded-xl bg-orange-100 text-orange-500">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <h4 class="text-2xl font-bold text-gray-900">Punto de Venta Ágil</h4>
                            </div>
                             <p class="mt-2 text-base text-gray-500 leading-relaxed">
                                Ventas rápidas, facturación electrónica integrada y control de adeudos. Todo optimizado para reducir tiempos de espera en mostrador.
                            </p>
                             <img class="mt-4 rounded-2xl shadow-xl border border-gray-100" src="/images/pos.png" alt="POS">
                        </div>
                    </div>

                    <!-- Adaptability Section -->
                    <div class="py-12 bg-green-50 rounded-2xl mb-16 px-6 md:px-12 flex flex-col md:flex-row items-center justify-between gap-8 border border-green-100">
                         <div class="md:w-1/2">
                            <h3 class="text-3xl font-extrabold text-green-800 mb-4">Tu negocio en todas partes</h3>
                            <p class="text-lg text-gray-600 mb-6">
                                Agrosys es totalmente <b>responsivo</b>. Accede desde tu computadora de escritorio en el mostrador, consulta inventarios en tu tablet mientras caminas por la tienda, o revisa ventas desde tu celular estés donde estés.
                            </p>
                            <div class="flex gap-4 text-green-600">
                                <div class="flex flex-col items-center">
                                    <svg class="h-8 w-8 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    <span class="text-xs font-semibold">PC</span>
                                </div>
                                <div class="flex flex-col items-center">
                                    <svg class="h-8 w-8 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                    <span class="text-xs font-semibold">Móvil</span>
                                </div>
                                <div class="flex flex-col items-center">
                                    <svg class="h-8 w-8 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                    <span class="text-xs font-semibold">Tablet</span>
                                </div>
                            </div>
                        </div>
                         <div class="md:w-1/2 flex justify-center">
                            <!-- Placeholder for device adaptability illustration/icon -->
                            <div class="relative w-full max-w-sm">
                                <div class="absolute top-0 -left-4 w-72 h-72 bg-orange-200 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
                                <div class="absolute top-0 -right-4 w-72 h-72 bg-green-200 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
                                <img src="https://cdni.iconscout.com/illustration/premium/thumb/web-development-2974925-2477356.png" class="relative z-10 w-full drop-shadow-lg transform hover:scale-105 transition duration-500" alt="Adaptabilidad Dispositivos" style="mask-image: linear-gradient(to bottom, black 80%, transparent 100%);">
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Features Grid -->
                    <div class="mt-12">
                         <h3 class="text-2xl font-bold text-green-800 mb-8 text-center uppercase tracking-wide">Herramientas Profesionales</h3>
                         <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                            
                            <!-- Diccionario Agrícola (Prioridad Alta) -->
                            <div class="bg-white border-2 border-green-100 rounded-xl p-6 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 relative overflow-hidden group">
                                <div class="absolute top-0 right-0 bg-orange-500 text-white text-xs font-bold px-2 py-1 rounded-bl-lg">Destacado</div>
                                <div class="w-12 h-12 bg-green-600 text-white rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                     <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                </div>
                                <h4 class="text-xl font-bold text-gray-900 mb-2">Diccionario Agrícola</h4>
                                <p class="text-gray-600 text-sm">Más que un sistema, es tu enciclopedia. Consulta al instante <b>Enfermedades</b>, plagas y sus <b>Soluciones</b> recomendadas. Asesora a tus clientes con respaldo técnico.</p>
                            </div>

                            <!-- Facturación -->
                            <div class="bg-slate-50 rounded-xl p-6 hover:shadow-md transition-shadow duration-300">
                                <div class="w-10 h-10 bg-green-100 text-green-600 rounded-lg flex items-center justify-center mb-4">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h4 class="text-xl font-bold text-gray-900 mb-2">Facturación Electrónica</h4>
                                <p class="text-gray-500 text-sm">Emite facturas fiscales al instante con integración directa a Facturapi. Cumple con normativas sin complicaciones.</p>
                            </div>

                            <!-- Créditos y Clientes -->
                            <div class="bg-slate-50 rounded-xl p-6 hover:shadow-md transition-shadow duration-300">
                                <div class="w-10 h-10 bg-orange-100 text-orange-500 rounded-lg flex items-center justify-center mb-4">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <h4 class="text-xl font-bold text-gray-900 mb-2">Gestión de Clientes</h4>
                                <p class="text-gray-500 text-sm">Administra perfiles de clientes, historial de compras y control detallado de cuentas por cobrar y abonos.</p>
                            </div>

                            <!-- Multi-Sucursal -->
                            <div class="bg-slate-50 rounded-xl p-6 hover:shadow-md transition-shadow duration-300">
                                <div class="w-10 h-10 bg-green-100 text-green-600 rounded-lg flex items-center justify-center mb-4">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <h4 class="text-xl font-bold text-gray-900 mb-2">Multi-Sucursal</h4>
                                <p class="text-gray-500 text-sm">Centraliza la administración de múltiples tiendas o bodegas. Visualiza el rendimiento global de tu empresa.</p>
                            </div>

                            <!-- Reportes -->
                            <div class="bg-slate-50 rounded-xl p-6 hover:shadow-md transition-shadow duration-300">
                                <div class="w-10 h-10 bg-green-100 text-green-600 rounded-lg flex items-center justify-center mb-4">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <h4 class="text-xl font-bold text-gray-900 mb-2">Reportes Financieros</h4>
                                <p class="text-gray-500 text-sm">Analiza ganancias diarias, ventas por producto y stock valorizado. Toma decisiones basadas en datos.</p>
                            </div>

                             <!-- Seguridad -->
                            <div class="bg-slate-50 rounded-xl p-6 hover:shadow-md transition-shadow duration-300">
                                <div class="w-10 h-10 bg-orange-100 text-orange-500 rounded-lg flex items-center justify-center mb-4">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <h4 class="text-xl font-bold text-gray-900 mb-2">Roles y Permisos</h4>
                                <p class="text-gray-500 text-sm">Control total sobre quién accede a qué. Perfiles para Vendedores, Administradores, Encargados de Almacén, etc.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Section -->
        <div class="bg-slate-50 border-t border-slate-200">
            <div class="max-w-7xl mx-auto py-16 px-4 sm:px-6 lg:px-8 flex flex-col lg:flex-row items-center justify-between gap-8">
                <div>
                     <h2 class="text-3xl font-extrabold tracking-tight text-gray-900">
                        <span class="block text-green-800">¿Necesitas asesoría personalizada?</span>
                        <span class="block text-orange-500 text-2xl mt-1">Estamos listos para ayudarte.</span>
                    </h2>
                </div>
                
                <div class="flex">
                    <a href="https://wa.me/5217228259581" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center px-8 py-4 border border-transparent text-lg font-bold rounded-full text-white bg-green-600 hover:bg-green-700 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                        </svg>
                        Contactar ahora
                    </a>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-slate-900 pt-12 pb-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row justify-between items-center border-b border-slate-800 pb-8 mb-8">
                    <div class="flex items-center gap-3 mb-4 md:mb-0">
                         <div class="bg-white p-1 rounded-full">
                            <svg class="h-6 w-6 text-green-700" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2L2 7l10 5 10-5-10-5zm0 9l2.5-1.25L12 8.5l-2.5 1.25L12 11zm0 2.5l-5-2.5-5 2.5L12 22l10-8.5-5-2.5-5 2.5z"/>
                            </svg>
                         </div>
                        <span class="text-xl font-bold text-white tracking-wide">Agrosys</span>
                    </div>
                     <p class="text-slate-400 text-sm">
                        Tecnología que impulsa el campo.
                    </p>
                </div>
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <p class="text-center text-sm text-slate-500">
                        &copy; 2024 Agrosys Inc. Todos los derechos reservados.
                    </p>
                    <div class="flex space-x-6">
                        <Link :href="route('legal.privacy')" class="text-slate-400 hover:text-white transition-colors">Aviso de Privacidad</Link>
                        <Link :href="route('legal.terms')" class="text-slate-400 hover:text-white transition-colors">Términos de Uso</Link>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
