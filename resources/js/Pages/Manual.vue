<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, onMounted } from 'vue';

const searchTerm = ref('');
const activeSection = ref('');

const sections = [
    { id: 'primeros-pasos', title: '🚀 Primeros Pasos' },
    { id: 'ventas', title: '💰 Ventas' },
    { id: 'inventario', title: '📦 Inventario' },
    { id: 'compras', title: '🛒 Compras' },
    { id: 'clientes', title: '👥 Clientes' },
    { id: 'reportes', title: '📊 Reportes' },
    { id: 'configuracion', title: '⚙️ Configuración' },
    { id: 'ayuda', title: '🆘 Ayuda' }
];

const scrollToSection = (sectionId) => {
    const element = document.getElementById(sectionId);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
    }
};

onMounted(() => {
    const handleScroll = () => {
        const sectionElements = document.querySelectorAll('[data-section]');
        let current = '';
        
        sectionElements.forEach(section => {
            const rect = section.getBoundingClientRect();
            if (rect.top <= 100 && rect.bottom >= 100) {
                current = section.dataset.section;
            }
        });
        
        activeSection.value = current;
    };

    window.addEventListener('scroll', handleScroll);
    
    return () => {
        window.removeEventListener('scroll', handleScroll);
    };
});
</script>

<template>
    <AppLayout title="Manual de Usuario">
        <template #header>
            <div class="bg-gradient-to-r from-green-800 to-green-600 text-white py-8 px-4 text-center">
                <h1 class="text-4xl md:text-5xl font-bold mb-2">🌱 Manual de Usuario - AgroSys</h1>
                <p class="text-xl opacity-90">Guía Completa del Sistema de Gestión Agrícola</p>
            </div>
        </template>
        
        <div class="max-w-7xl mx-auto">
            <!-- Navegación sticky -->
            <div class="sticky top-0 bg-gray-50 border-b border-gray-200 z-50 shadow-sm">
                <nav class="flex flex-wrap gap-3 p-4 justify-center">
                    <button
                        v-for="section in sections"
                        :key="section.id"
                        @click="scrollToSection(section.id)"
                        :class="[
                            'px-4 py-2 rounded-full text-sm font-medium transition-all duration-300 border-2',
                            activeSection === section.id 
                                ? 'bg-green-800 text-white border-green-800 transform -translate-y-1 shadow-lg' 
                                : 'bg-white text-gray-600 border-gray-200 hover:bg-green-800 hover:text-white hover:border-green-800 hover:transform hover:-translate-y-1 hover:shadow-md'
                        ]"
                    >
                        {{ section.title }}
                    </button>
                </nav>
            </div>

            <div class="p-6 space-y-8">
                <!-- Buscador -->
                <div class="text-center">
                    <div class="max-w-md mx-auto">
                        <input
                            v-model="searchTerm"
                            type="text"
                            placeholder="🔍 Buscar en el manual..."
                            class="w-full px-6 py-3 border-2 border-gray-200 rounded-full text-lg outline-none transition-colors duration-300 focus:border-green-600"
                        />
                    </div>
                </div>

                <!-- Enlaces rápidos -->
                <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white p-6 rounded-2xl">
                    <h3 class="text-2xl font-bold mb-4">🔗 Enlaces Rápidos al Sistema</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                        <a href="/venta" class="bg-white/10 hover:bg-white/20 p-3 rounded-lg text-center transition-colors duration-300 text-white no-underline">
                            <div class="font-semibold">💰 Realizar Venta</div>
                        </a>
                        <a href="/inventario" class="bg-white/10 hover:bg-white/20 p-3 rounded-lg text-center transition-colors duration-300 text-white no-underline">
                            <div class="font-semibold">📦 Ver Inventario</div>
                        </a>
                        <a href="/compra" class="bg-white/10 hover:bg-white/20 p-3 rounded-lg text-center transition-colors duration-300 text-white no-underline">
                            <div class="font-semibold">🛒 Registrar Compra</div>
                        </a>
                        <a href="/cliente" class="bg-white/10 hover:bg-white/20 p-3 rounded-lg text-center transition-colors duration-300 text-white no-underline">
                            <div class="font-semibold">👥 Gestionar Clientes</div>
                        </a>
                        <a href="/reporte" class="bg-white/10 hover:bg-white/20 p-3 rounded-lg text-center transition-colors duration-300 text-white no-underline">
                            <div class="font-semibold">📊 Ver Reportes</div>
                        </a>
                        <a href="/dashboard" class="bg-white/10 hover:bg-white/20 p-3 rounded-lg text-center transition-colors duration-300 text-white no-underline">
                            <div class="font-semibold">🏠 Ir al Dashboard</div>
                        </a>
                    </div>
                </div>

                <!-- Índice de contenidos -->
                <div class="bg-blue-50 p-6 rounded-2xl">
                    <h3 class="text-2xl font-bold text-blue-600 mb-4">📚 Índice de Contenidos</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="bg-white p-4 rounded-lg border-l-4 border-blue-600">
                            <h4 class="text-lg font-semibold text-blue-600 mb-2">🚀 Primeros Pasos</h4>
                            <ul class="space-y-1 text-sm">
                                <li><a href="#que-es-agrosys" class="text-gray-600 hover:text-blue-600 transition-colors">¿Qué es AgroSys?</a></li>
                                <li><a href="#como-entrar" class="text-gray-600 hover:text-blue-600 transition-colors">Cómo entrar al sistema</a></li>
                                <li><a href="#pantalla-principal" class="text-gray-600 hover:text-blue-600 transition-colors">Tu pantalla principal</a></li>
                            </ul>
                        </div>
                        <div class="bg-white p-4 rounded-lg border-l-4 border-green-600">
                            <h4 class="text-lg font-semibold text-green-600 mb-2">💰 Ventas Diarias</h4>
                            <ul class="space-y-1 text-sm">
                                <li><a href="#hacer-venta" class="text-gray-600 hover:text-green-600 transition-colors">Cómo hacer una venta</a></li>
                                <li><a href="#buscar-productos" class="text-gray-600 hover:text-green-600 transition-colors">Buscar productos</a></li>
                                <li><a href="#imprimir-tickets" class="text-gray-600 hover:text-green-600 transition-colors">Imprimir tickets</a></li>
                                <li><a href="#devoluciones" class="text-gray-600 hover:text-green-600 transition-colors">Hacer devoluciones</a></li>
                            </ul>
                        </div>
                        <div class="bg-white p-4 rounded-lg border-l-4 border-yellow-600">
                            <h4 class="text-lg font-semibold text-yellow-600 mb-2">📦 Control de Inventario</h4>
                            <ul class="space-y-1 text-sm">
                                <li><a href="#ver-productos" class="text-gray-600 hover:text-yellow-600 transition-colors">Ver qué productos tienes</a></li>
                                <li><a href="#agregar-productos" class="text-gray-600 hover:text-yellow-600 transition-colors">Agregar nuevos productos</a></li>
                                <li><a href="#registrar-mercancia" class="text-gray-600 hover:text-yellow-600 transition-colors">Registrar mercancía</a></li>
                                <li><a href="#poco-stock" class="text-gray-600 hover:text-yellow-600 transition-colors">Productos con poco stock</a></li>
                            </ul>
                        </div>
                        <div class="bg-white p-4 rounded-lg border-l-4 border-purple-600">
                            <h4 class="text-lg font-semibold text-purple-600 mb-2">🛒 Compras a Proveedores</h4>
                            <ul class="space-y-1 text-sm">
                                <li><a href="#registrar-compra" class="text-gray-600 hover:text-purple-600 transition-colors">Registrar una compra</a></li>
                                <li><a href="#pagos-plazos" class="text-gray-600 hover:text-purple-600 transition-colors">Manejar pagos a plazos</a></li>
                                <li><a href="#estado-pagos" class="text-gray-600 hover:text-purple-600 transition-colors">Estado de pagos</a></li>
                            </ul>
                        </div>
                        <div class="bg-white p-4 rounded-lg border-l-4 border-indigo-600">
                            <h4 class="text-lg font-semibold text-indigo-600 mb-2">👥 Clientes</h4>
                            <ul class="space-y-1 text-sm">
                                <li><a href="#registrar-clientes" class="text-gray-600 hover:text-indigo-600 transition-colors">Registrar nuevos clientes</a></li>
                                <li><a href="#buscar-clientes" class="text-gray-600 hover:text-indigo-600 transition-colors">Buscar clientes existentes</a></li>
                                <li><a href="#manejar-facturas" class="text-gray-600 hover:text-indigo-600 transition-colors">Manejar facturas</a></li>
                            </ul>
                        </div>
                        <div class="bg-white p-4 rounded-lg border-l-4 border-red-600">
                            <h4 class="text-lg font-semibold text-red-600 mb-2">📊 Reportes</h4>
                            <ul class="space-y-1 text-sm">
                                <li><a href="#reportes-ventas" class="text-gray-600 hover:text-red-600 transition-colors">Ver reportes de ventas</a></li>
                                <li><a href="#consultar-inventario" class="text-gray-600 hover:text-red-600 transition-colors">Consultar inventario</a></li>
                                <li><a href="#productos-vendidos" class="text-gray-600 hover:text-red-600 transition-colors">Productos más vendidos</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Primeros Pasos -->
                <section id="primeros-pasos" data-section="primeros-pasos" class="bg-gray-50 p-6 rounded-2xl border-l-4 border-green-600">
                    <h2 class="text-3xl font-bold text-green-800 mb-6 flex items-center gap-2">
                        🚀 Primeros Pasos
                    </h2>
                    
                    <h3 id="que-es-agrosys" class="text-2xl font-semibold text-green-700 mb-4 pb-2 border-b-2 border-gray-200">¿Qué es AgroSys?</h3>
                    <p class="text-gray-700 mb-6">AgroSys es tu aliado para manejar todo lo relacionado con tu negocio agrícola. Con este sistema puedes:</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                        <div class="bg-white p-4 rounded-lg shadow-sm border hover:shadow-md transition-shadow duration-300">
                            <h4 class="text-lg font-semibold text-green-800 mb-2">💰 Vender productos</h4>
                            <p class="text-gray-600 text-sm">De manera rápida y eficiente con búsqueda inteligente y tickets automáticos</p>
                        </div>
                        <div class="bg-white p-4 rounded-lg shadow-sm border hover:shadow-md transition-shadow duration-300">
                            <h4 class="text-lg font-semibold text-green-800 mb-2">📦 Controlar tu inventario</h4>
                            <p class="text-gray-600 text-sm">Para nunca quedarte sin mercancía y conocer exactamente qué tienes</p>
                        </div>
                        <div class="bg-white p-4 rounded-lg shadow-sm border hover:shadow-md transition-shadow duration-300">
                            <h4 class="text-lg font-semibold text-green-800 mb-2">🛒 Registrar compras</h4>
                            <p class="text-gray-600 text-sm">A tus proveedores y manejar pagos a plazos de forma organizada</p>
                        </div>
                        <div class="bg-white p-4 rounded-lg shadow-sm border hover:shadow-md transition-shadow duration-300">
                            <h4 class="text-lg font-semibold text-green-800 mb-2">👥 Gestionar clientes</h4>
                            <p class="text-gray-600 text-sm">Y sus facturas para mejorar el servicio y fidelización</p>
                        </div>
                        <div class="bg-white p-4 rounded-lg shadow-sm border hover:shadow-md transition-shadow duration-300">
                            <h4 class="text-lg font-semibold text-green-800 mb-2">📊 Generar reportes</h4>
                            <p class="text-gray-600 text-sm">Para saber cómo va tu negocio y tomar mejores decisiones</p>
                        </div>
                        <div class="bg-white p-4 rounded-lg shadow-sm border hover:shadow-md transition-shadow duration-300">
                            <h4 class="text-lg font-semibold text-green-800 mb-2">⚙️ Organizar catálogos</h4>
                            <p class="text-gray-600 text-sm">De productos, marcas y clasificaciones de forma sistemática</p>
                        </div>
                    </div>

                    <h3 id="como-entrar" class="text-2xl font-semibold text-green-700 mb-4 pb-2 border-b-2 border-gray-200">Cómo entrar al sistema</h3>
                    <div class="bg-white rounded-lg p-4 mb-4 border">
                        <div class="space-y-4">
                            <div class="p-3 bg-blue-50 rounded-lg border-l-4 border-blue-500">
                                <strong class="text-blue-600">Paso 1:</strong> <span class="text-gray-700">Abre tu navegador (Chrome, Firefox, Safari, etc.)</span>
                            </div>
                            <div class="p-3 bg-blue-50 rounded-lg border-l-4 border-blue-500">
                                <strong class="text-blue-600">Paso 2:</strong> <span class="text-gray-700">Escribe la dirección web de tu AgroSys (te la proporcionó tu administrador)</span>
                            </div>
                            <div class="p-3 bg-blue-50 rounded-lg border-l-4 border-blue-500">
                                <strong class="text-blue-600">Paso 3:</strong> <span class="text-gray-700">Verás una pantalla de entrada donde debes escribir:</span>
                                <ul class="list-disc list-inside mt-2 ml-4 text-gray-600">
                                    <li>Tu <strong>nombre de usuario</strong></li>
                                    <li>Tu <strong>contraseña</strong></li>
                                </ul>
                            </div>
                            <div class="p-3 bg-blue-50 rounded-lg border-l-4 border-blue-500">
                                <strong class="text-blue-600">Paso 4:</strong> <span class="text-gray-700">Haz clic en "Iniciar Sesión"</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg mb-4">
                        <div class="flex items-start">
                            <span class="text-lg mr-2">💡</span>
                            <div>
                                <strong class="text-yellow-800">Consejo:</strong> 
                                <span class="text-yellow-700">Si olvidas tu contraseña, contacta a tu administrador para que la restablezca.</span>
                            </div>
                        </div>
                    </div>

                    <h3 id="pantalla-principal" class="text-2xl font-semibold text-green-700 mb-4 pb-2 border-b-2 border-gray-200">Tu pantalla principal</h3>
                    <p class="text-gray-700 mb-4">Una vez que entres, verás el <strong>Dashboard</strong> (tablero principal). Aquí encontrarás:</p>
                    
                    <div class="space-y-4">
                        <div class="bg-white p-4 rounded-lg border">
                            <h4 class="text-lg font-semibold text-gray-800 mb-2">En la parte superior:</h4>
                            <ul class="list-disc list-inside text-gray-600 space-y-1">
                                <li><strong>Menú principal:</strong> Para navegar a diferentes secciones</li>
                                <li><strong>Buscador:</strong> Para encontrar productos o soluciones rápidamente</li>
                                <li><strong>Tu nombre:</strong> En la esquina derecha, con opción para salir del sistema</li>
                            </ul>
                        </div>
                        
                        <div class="bg-white p-4 rounded-lg border">
                            <h4 class="text-lg font-semibold text-gray-800 mb-2">En el centro:</h4>
                            <ul class="list-disc list-inside text-gray-600 space-y-1">
                                <li><strong>Buscador de soluciones:</strong> Escribe el nombre de una plaga o enfermedad para encontrar tratamientos</li>
                                <li><strong>Accesos rápidos:</strong> Botones para las tareas más comunes según tu rol</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- Ventas -->
                <section id="ventas" data-section="ventas" class="bg-gray-50 p-6 rounded-2xl border-l-4 border-green-600">
                    <h2 class="text-3xl font-bold text-green-800 mb-6 flex items-center gap-2">
                        💰 Ventas Diarias
                    </h2>
                    
                    <h3 id="hacer-venta" class="text-2xl font-semibold text-green-700 mb-4 pb-2 border-b-2 border-gray-200">Cómo hacer una venta</h3>
                    
                    <h4 class="text-xl font-medium text-gray-800 mb-4">Paso a paso para vender:</h4>
                    <div class="bg-white rounded-lg p-4 mb-6 border">
                        <div class="space-y-4">
                            <div class="p-3 bg-blue-50 rounded-lg border-l-4 border-blue-500">
                                <strong class="text-blue-600">1. Ir al módulo de ventas</strong><br>
                                <span class="text-gray-700">En el menú superior, haz clic en <strong>"Venta"</strong><br>
                                Luego selecciona <strong>"Venta"</strong> (la primera opción)</span>
                            </div>
                            
                            <div class="p-3 bg-blue-50 rounded-lg border-l-4 border-blue-500">
                                <strong class="text-blue-600">2. Buscar el producto</strong><br>
                                <span class="text-gray-700">En el campo de búsqueda, escribe:</span>
                                <ul class="list-disc list-inside mt-2 ml-4 text-gray-600">
                                    <li>El nombre del producto (ej: "Fertilizante")</li>
                                    <li>El código de barras</li>
                                    <li>El ingrediente activo (ej: "Glifosato")</li>
                                </ul>
                                <span class="text-gray-700">Los productos aparecerán automáticamente mientras escribes</span>
                            </div>
                            
                            <div class="p-3 bg-blue-50 rounded-lg border-l-4 border-blue-500">
                                <strong class="text-blue-600">3. Agregar productos a la venta</strong><br>
                                <span class="text-gray-700">Haz clic en el producto que quieres vender<br>
                                Aparecerá una ventana donde puedes:</span>
                                <ul class="list-disc list-inside mt-2 ml-4 text-gray-600">
                                    <li>Cambiar la <strong>cantidad</strong> (por defecto es 1)</li>
                                    <li>Ver el <strong>precio</strong></li>
                                </ul>
                                <span class="text-gray-700">Haz clic en <strong>"Agregar"</strong></span>
                            </div>

                            <div class="p-3 bg-blue-50 rounded-lg border-l-4 border-blue-500">
                                <strong class="text-blue-600">4. Revisar tu carrito</strong><br>
                                <span class="text-gray-700">En el lado derecho verás todos los productos agregados<br>
                                Puedes cambiar cantidades o quitar productos</span>
                            </div>

                            <div class="p-3 bg-blue-50 rounded-lg border-l-4 border-blue-500">
                                <strong class="text-blue-600">5. Finalizar la venta</strong><br>
                                <span class="text-gray-700">Revisa que todo esté correcto<br>
                                Haz clic en <strong>"Finalizar Venta"</strong><br>
                                El ticket se imprimirá automáticamente</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg">
                        <div class="flex items-start">
                            <span class="text-lg mr-2">💡</span>
                            <div>
                                <strong class="text-yellow-800">Trucos para buscar mejor:</strong>
                                <ul class="list-disc list-inside mt-2 text-yellow-700">
                                    <li>Escribe solo las primeras letras</li>
                                    <li>Usa nombres comunes (ej: "ferti" para fertilizantes)</li>
                                    <li>Prueba con la marca si no encuentras por nombre</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Inventario -->
                <section id="inventario" data-section="inventario" class="bg-gray-50 p-6 rounded-2xl border-l-4 border-yellow-600">
                    <h2 class="text-3xl font-bold text-yellow-800 mb-6 flex items-center gap-2">
                        📦 Control de Inventario
                    </h2>
                    
                    <h3 id="ver-productos" class="text-2xl font-semibold text-yellow-700 mb-4 pb-2 border-b-2 border-gray-200">Ver qué productos tienes</h3>
                    
                    <h4 class="text-xl font-medium text-gray-800 mb-2">Para consultar tu inventario:</h4>
                    <p class="text-gray-700 mb-4">Ve a <strong>"Administración de Inventario"</strong> → <strong>"Inventario"</strong></p>

                    <h4 class="text-xl font-medium text-gray-800 mb-2">Información que verás:</h4>
                    <div class="bg-white p-4 rounded-lg border mb-4">
                        <ul class="list-disc list-inside text-gray-600 space-y-1">
                            <li><strong>Nombre del producto</strong></li>
                            <li><strong>Existencias actuales</strong> (cuántos tienes)</li>
                            <li><strong>Precio de venta</strong></li>
                            <li><strong>Clasificación</strong> (tipo de producto)</li>
                            <li><strong>Marca</strong></li>
                            <li><strong>Código de barras</strong></li>
                        </ul>
                    </div>

                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg mb-4">
                        <div class="flex items-start">
                            <span class="text-lg mr-2">💡</span>
                            <div>
                                <strong class="text-yellow-800">Cómo buscar productos específicos:</strong> 
                                <span class="text-yellow-700">Usa el campo de búsqueda arriba de la tabla. Puedes buscar por nombre o ingrediente activo. Los resultados se filtran automáticamente.</span>
                            </div>
                        </div>
                    </div>

                    <h3 id="agregar-productos" class="text-2xl font-semibold text-yellow-700 mb-4 pb-2 border-b-2 border-gray-200">Agregar nuevos productos</h3>
                    
                    <div class="bg-white rounded-lg p-4 mb-4 border">
                        <h4 class="text-lg font-semibold text-gray-800 mb-2">Cuándo agregar un producto nuevo:</h4>
                        <ul class="list-disc list-inside text-gray-600 space-y-1">
                            <li>Cuando empiezas a vender una marca nueva</li>
                            <li>Cuando llega un producto que nunca habías manejado</li>
                            <li>Cuando cambia la presentación de un producto existente</li>
                        </ul>
                    </div>
                </section>

                <!-- Compras -->
                <section id="compras" data-section="compras" class="bg-gray-50 p-6 rounded-2xl border-l-4 border-purple-600">
                    <h2 class="text-3xl font-bold text-purple-800 mb-6 flex items-center gap-2">
                        🛒 Compras a Proveedores
                    </h2>
                    
                    <h3 id="registrar-compra" class="text-2xl font-semibold text-purple-700 mb-4 pb-2 border-b-2 border-gray-200">Registrar una compra</h3>
                    
                    <p class="text-gray-700 mb-6">El sistema te permite manejar diferentes estados de pago para tus compras:</p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-white p-4 rounded-lg border border-green-200">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="inline-block w-3 h-3 bg-green-500 rounded-full"></span>
                                <span class="font-semibold text-green-800">Pagada</span>
                            </div>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>Ya no debes nada</li>
                                <li>No puedes modificar la compra</li>
                                <li>Solo para consulta</li>
                            </ul>
                        </div>
                        
                        <div class="bg-white p-4 rounded-lg border border-yellow-200">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="inline-block w-3 h-3 bg-yellow-500 rounded-full"></span>
                                <span class="font-semibold text-yellow-800">Adeudo</span>
                            </div>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>Tienes pagos pendientes</li>
                                <li>Puedes agregar abonos</li>
                                <li>Puedes cambiar el estado</li>
                            </ul>
                        </div>
                        
                        <div class="bg-white p-4 rounded-lg border border-red-200">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="inline-block w-3 h-3 bg-red-500 rounded-full"></span>
                                <span class="font-semibold text-red-800">Retrasada</span>
                            </div>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>Se pasó la fecha de pago</li>
                                <li>Requiere atención inmediata</li>
                                <li>Puedes agregar abonos</li>
                            </ul>
                        </div>
                    </div>

                    <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-lg">
                        <div class="flex items-start">
                            <span class="text-lg mr-2">⚠️</span>
                            <div>
                                <strong class="text-red-800">Cambiar estados:</strong> 
                                <span class="text-red-700">Puedes cambiar entre "Adeudo" y "Retrasada" según convenga. Si cambias de "Pagada" a "Adeudo", se borra el historial de pagos (ten cuidado).</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Clientes -->
                <section id="clientes" data-section="clientes" class="bg-gray-50 p-6 rounded-2xl border-l-4 border-indigo-600">
                    <h2 class="text-3xl font-bold text-indigo-800 mb-6 flex items-center gap-2">
                        👥 Clientes
                    </h2>
                    
                    <p class="text-gray-700 mb-4">Gestiona la información de tus clientes y lleva un control de sus compras y facturas.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="bg-white p-4 rounded-lg border">
                            <h4 class="text-lg font-semibold text-indigo-800 mb-2">Información necesaria:</h4>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>• Nombre completo</li>
                                <li>• Dirección (para entregas)</li>
                                <li>• Teléfono (para contacto)</li>
                                <li>• Email (opcional, para enviar facturas)</li>
                                <li>• RFC (si necesita factura)</li>
                            </ul>
                        </div>
                        <div class="bg-white p-4 rounded-lg border">
                            <h4 class="text-lg font-semibold text-indigo-800 mb-2">Cuándo registrar:</h4>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>• Primera vez que compra</li>
                                <li>• Para llevar historial de compras</li>
                                <li>• Para poder facturar</li>
                                <li>• Para brindar mejor servicio</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg">
                        <div class="flex items-start">
                            <span class="text-lg mr-2">💡</span>
                            <div>
                                <strong class="text-yellow-800">Consejo:</strong> 
                                <span class="text-yellow-700">Registra toda la información posible de cada cliente desde el primer contacto. Esto te ayudará a brindar un mejor servicio.</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Reportes -->
                <section id="reportes" data-section="reportes" class="bg-gray-50 p-6 rounded-2xl border-l-4 border-red-600">
                    <h2 class="text-3xl font-bold text-red-800 mb-6 flex items-center gap-2">
                        📊 Reportes
                    </h2>
                    
                    <p class="text-gray-700 mb-4">Los reportes te ayudan a entender el rendimiento de tu negocio y tomar decisiones informadas.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="bg-white p-4 rounded-lg border">
                            <h4 class="text-lg font-semibold text-red-800 mb-2">📈 Reporte de Ventas</h4>
                            <p class="text-gray-600 text-sm">Analiza tus ventas por período, productos más vendidos y rendimiento general.</p>
                            <ul class="text-xs text-gray-500 mt-2 space-y-1">
                                <li>• Cuánto vendiste en un período</li>
                                <li>• Comparar ventas entre días/meses</li>
                                <li>• Planificar compras futuras</li>
                            </ul>
                        </div>
                        <div class="bg-white p-4 rounded-lg border">
                            <h4 class="text-lg font-semibold text-red-800 mb-2">📦 Reporte de Inventario</h4>
                            <p class="text-gray-600 text-sm">Consulta el estado actual de tu inventario y productos con poco stock.</p>
                            <ul class="text-xs text-gray-500 mt-2 space-y-1">
                                <li>• Todos los productos que tienes</li>
                                <li>• Existencias actuales</li>
                                <li>• Valor del inventario</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- Configuración -->
                <section id="configuracion" data-section="configuracion" class="bg-gray-50 p-6 rounded-2xl border-l-4 border-gray-600">
                    <h2 class="text-3xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                        ⚙️ Configuración
                    </h2>
                    
                    <p class="text-gray-700 mb-4">Administra los catálogos y configuraciones básicas del sistema.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-white p-4 rounded-lg border">
                            <h4 class="text-lg font-semibold text-gray-800 mb-2">📋 Clasificaciones</h4>
                            <p class="text-gray-600 text-sm">Tipos de producto como Fertilizantes, Pesticidas, Herbicidas, Fungicidas, etc.</p>
                        </div>
                        <div class="bg-white p-4 rounded-lg border">
                            <h4 class="text-lg font-semibold text-gray-800 mb-2">🏷️ Marcas</h4>
                            <p class="text-gray-600 text-sm">Lista de todas las marcas que manejas. Útil para organizar productos y reportes.</p>
                        </div>
                        <div class="bg-white p-4 rounded-lg border">
                            <h4 class="text-lg font-semibold text-gray-800 mb-2">🐛 Enfermedades</h4>
                            <p class="text-gray-600 text-sm">Base de datos de problemas agrícolas. Cada enfermedad tiene síntomas y tratamientos.</p>
                        </div>
                        <div class="bg-white p-4 rounded-lg border">
                            <h4 class="text-lg font-semibold text-gray-800 mb-2">👥 Usuarios</h4>
                            <p class="text-gray-600 text-sm">Gestión de usuarios y permisos del sistema (solo para administradores).</p>
                        </div>
                    </div>
                </section>

                <!-- Ayuda -->
                <section id="ayuda" data-section="ayuda" class="bg-gray-50 p-6 rounded-2xl border-l-4 border-orange-600">
                    <h2 class="text-3xl font-bold text-orange-800 mb-6 flex items-center gap-2">
                        🆘 ¿Necesitas Ayuda?
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-red-50 p-4 rounded-lg border-l-4 border-red-400">
                            <div class="flex items-start">
                                <span class="text-lg mr-2">⚠️</span>
                                <div>
                                    <h4 class="font-semibold text-red-800 mb-2">Problemas Comunes</h4>
                                    <ul class="text-red-700 text-sm space-y-1">
                                        <li>• No puedo entrar al sistema</li>
                                        <li>• No aparecen productos</li>
                                        <li>• No se imprime el ticket</li>
                                        <li>• El sistema está lento</li>
                                        <li>• Borré algo por error</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-green-50 p-4 rounded-lg border-l-4 border-green-400">
                            <div class="flex items-start">
                                <span class="text-lg mr-2">💡</span>
                                <div>
                                    <h4 class="font-semibold text-green-800 mb-2">Consejos Útiles</h4>
                                    <ul class="text-green-700 text-sm space-y-1">
                                        <li>• Practica en horarios de poca actividad</li>
                                        <li>• Haz respaldos regulares</li>
                                        <li>• Mantén actualizada la información</li>
                                        <li>• No tengas miedo de explorar</li>
                                        <li>• Contacta a tu administrador si necesitas ayuda</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 bg-blue-50 p-4 rounded-lg border-l-4 border-blue-400">
                        <div class="flex items-start">
                            <span class="text-lg mr-2">📞</span>
                            <div>
                                <h4 class="font-semibold text-blue-800 mb-2">Para soporte técnico:</h4>
                                <ul class="text-blue-700 text-sm space-y-1">
                                    <li>• Contacta a tu administrador del sistema</li>
                                    <li>• Ten a la mano la descripción exacta del problema</li>
                                    <li>• Menciona qué estabas haciendo cuando ocurrió el error</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Footer de finalización -->
                <div class="text-center bg-gradient-to-r from-green-800 to-green-600 text-white p-8 rounded-2xl">
                    <h2 class="text-3xl font-bold mb-4">🎉 ¡Felicidades!</h2>
                    <p class="text-xl mb-4">Has completado la guía de AgroSys. Con esta información podrás manejar eficientemente tu negocio agrícola.</p>
                    <p class="text-lg">Recuerda que la práctica hace al maestro, así que no dudes en explorar todas las funcionalidades del sistema.</p>
                    <p class="text-xl font-bold mt-6">🌱 AgroSys está diseñado para crecer contigo y hacer tu trabajo más fácil.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>