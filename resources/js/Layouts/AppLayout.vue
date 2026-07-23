<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import Toast from '@/Components/Toast.vue';
import SearchBar from '@/Components/SearchBar.vue';
import Loading from '@/Components/Loading.vue';
import SucursalSelectionModal from '@/Components/SucursalSelectionModal.vue';
import SubscriptionBanner from '@/Components/SubscriptionBanner.vue';
import OfflineBanner from '@/Components/OfflineBanner.vue';
import RequiresConnection from '@/Components/RequiresConnection.vue';
import SyncStatus from '@/Components/SyncStatus.vue';
import { useConnectivityStore } from '@/stores/connectivity';
import { canVisitOffline } from '@/Offline/guards/routePolicies';

defineProps({
    title: String,
});

const switchToTeam = (team) => {
    router.put(route('current-team.update'), {
        team_id: team.id,
    }, {
        preserveState: false,
    });
};

const minusDays = (date, days) => {
    let result = new Date(date);
    result.setDate(date.getDate() - days);
    return result;
}

const fechaFin = new Date();
const fechaInicio = minusDays(fechaFin, 1);

const showingNavigationDropdown = ref(false);
const showingSucursalModal = ref(false);
const page = usePage();
const isLoading = ref(false);
const connectivity = useConnectivityStore();
const isOfflineNow = computed(() => navigator.onLine === false || connectivity.isLimited);

router.on('start', () => (isLoading.value = true));
router.on('finish', () => (isLoading.value = false));

onMounted(() => {
    // Lógica para autoselección de sucursal
    const sucursalActiva = page.props.sucursalActiva;
    if (sucursalActiva && sucursalActiva.esAdminEmpresa) {
        const storedSucursalId = localStorage.getItem('selected_sucursal_id');
        const availableSucursales = sucursalActiva.sucursalesDisponibles || [];
        
        if (availableSucursales.length > 0) {
            let targetId = null;

            if (storedSucursalId) {
                // Verificar si el ID guardado sigue siendo válido
                const exists = availableSucursales.find(s => s.id == storedSucursalId);
                if (exists && exists.id !== sucursalActiva.id) {
                    targetId = exists.id;
                }
            } else {
                // Si no hay nada guardado y hay opciones, seleccionar el primero por defecto
                const firstId = availableSucursales[0].id;
                
                // Si la actual no es la primera, forzamos el cambio
                if (firstId !== sucursalActiva.id) {
                    targetId = firstId;
                } else {
                    // Si YA estamos en la primera, pero no estaba en localStorage, lo guardamos para el futuro
                    localStorage.setItem('selected_sucursal_id', firstId);
                }
            }

            if (targetId) {
                console.log('Auto-switching sucursal to:', targetId);
                router.post('/sucursal/change', {
                    sucursal_id: targetId
                }, {
                    preserveScroll: true,
                    onSuccess: () => {
                        // Asegurar storage
                        localStorage.setItem('selected_sucursal_id', targetId);
                    }
                });
            }
        }
    }
});

const logout = () => {
    if (isOfflineNow.value) {
        notify('Cerrar sesión requiere conexión. La sesión local se conserva temporalmente.', 'error');
        return;
    }
    router.post(route('logout'));
};

const isItemOfflineBlocked = (item) => isOfflineNow.value && !canVisitOffline(route(item.route, item.params));

const handleOfflineNavigation = (event, item) => {
    if (!isOfflineNow.value) return;
    event.preventDefault();
    event.stopImmediatePropagation?.();

    if (isItemOfflineBlocked(item)) {
        notify('Esta función requiere conexión.', 'error');
        return;
    }

    const target = new URL(route(item.route, item.params), window.location.origin);
    const destination = `${target.pathname}${target.search}`;
    const current = `${window.location.pathname}${window.location.search}`;
    if (destination !== current) window.location.assign(destination);
};

const abrirModalSucursal = () => {
    if (isOfflineNow.value) {
        notify('Cambiar de sucursal requiere conexión.', 'error');
        return;
    }
    console.log('Abriendo modal de sucursal...');
    console.log('Estado actual del modal:', showingSucursalModal.value);
    showingSucursalModal.value = true;
    console.log('Estado después de abrir:', showingSucursalModal.value);
};

const cerrarModalSucursal = () => {
    console.log('Cerrando modal de sucursal...');
    showingSucursalModal.value = false;
};

// Computed para determinar la sucursal "activa" para efectos de UI (menús)
// Priorizando lo que está en localStorage si somos administradores de empresa
const currentSucursalData = computed(() => {
    const fromProps = page.props.sucursalActiva;

    // Si no es adminEmpresa, confiamos plenamente en el backend
    if (!fromProps || !fromProps.esAdminEmpresa) return fromProps;


    // Si es adminEmpresa, intentamos validar con localStorage para respuesta inmediata
    if (typeof window !== 'undefined' && window.localStorage) {
        const storedId = localStorage.getItem('selected_sucursal_id');
        if (storedId && fromProps.sucursalesDisponibles) {
            const match = fromProps.sucursalesDisponibles.find(s => s.id == storedId);
            if (match) {
                return match; 
                // Devuelve el objeto de la lista, que DEBE tener 'es_bodega' (agregado en Middleware)
            }
        }
    }
    
    return fromProps;
});

const navItems = computed(() => {
    const tipo = page.props.auth.user.tipo;
    // Usamos el computed basado en localStorage para la validación de es_bodega
    const esBodega = currentSucursalData.value?.es_bodega === true;

    console.log('esBodega', esBodega);
    console.log('tipo', tipo);

    return [
        { type: 'link', label: 'Inicio', route: 'dashboard' },
        {
            type: 'dropdown',
            label: 'Venta',
            condition: ['vendedor', 'superAdmin', 'admin', 'adminEmpresa'].includes(tipo),
            children: [
                { label: 'Venta', route: 'venta.index' },
                { label: 'Devoluciones', route: 'devoluciones.list' },
            ],
        },
        {
            type: 'dropdown',
            label: 'Administración de Inventario',
            condition: ['inventario', 'admin', 'superAdmin', 'adminEmpresa'].includes(tipo),
            children: [
                { label: 'Inventario', route: 'inventario.index' },
                { label: 'Compra a proveedores', route: 'compra.index' },
            ],
        },
        {
            type: 'link',
            label: 'Transferencias',
            route: 'transferencias.index',
            condition: ['inventario', 'admin', 'superAdmin', 'adminEmpresa'].includes(tipo)
        },
        {
            type: 'dropdown',
            label: 'Clientes',
            condition: ['vendedor', 'admin', 'superAdmin', 'adminEmpresa'].includes(tipo),
            children: [
                { label: 'Clientes', route: 'cliente.index' },
                { label: 'Facturas de ventas', route: 'facturas.index', params: { fechaInicio, fechaFin } },
            ],
        },
        {
            type: 'link',
            label: 'Reportes',
            route: 'reporte',
            condition: ['inventario', 'vendedor', 'admin', 'superAdmin', 'adminEmpresa'].includes(tipo),
        },
        {
            type: 'link',
            condition: ['vendedor', 'admin', 'superAdmin', 'adminEmpresa'].includes(tipo),
            label: 'Pedidos', route: 'pedidos.index'
        },
        {
            type: 'dropdown',
            label: 'Administración de Catálogos',
            condition: ['inventario', 'admin', 'superAdmin', 'adminEmpresa'].includes(tipo),
            children: [
                { label: 'Catálogo de Clasificación', route: 'clasificacion.index' },
                { label: 'Catálogo de Marca', route: 'marca.index' },
                { label: 'Catálogo de Enfermedades', route: 'enfermedad.index' },
                { label: 'Catálogo de Tipo de Flores', route: 'tipoFlor.index' },
            ],
        },
        {
            type: 'dropdown',
            label: 'Administración',
            condition: ['superAdmin', 'adminEmpresa'].includes(tipo),
            children: [
                { label: 'Empresas', route: 'empresa.index' },
                ...(tipo === 'superAdmin' ? [{ label: 'Suscripciones', route: 'empresa.subscriptions.index' }] : []),
                { label: 'Sucursal', route: 'sucursal.index' },
                { label: 'Usuarios', route: 'usuario.index' },
            ],
        },
    ];
});

const searchItems = computed(() => {
    const items = [];
    navItems.value.forEach((i) => {
        const isVisible = i.condition === undefined ? true : Boolean(i.condition);
        console.log('i', i);
        if (i.type === 'link' && isVisible) {
            items.push({ label: i.label, route: i.route, params: i.params });
        } else if (i.type === 'dropdown' && isVisible) {
            i.children.forEach((c) => items.push(c));
        }
    });
    return items;
});
</script>

<template>
    <div>
        <Head :title="title" />

        <Banner />
        <SubscriptionBanner />
        <OfflineBanner />
        <SyncStatus v-if="$page.props.offline?.enabled" />

        <Toast />
        <Loading :show="isLoading" />
        <div>
            <nav :class="connectivity.mode !== 'online' ? 'top-12' : 'top-0'" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 fixed w-full z-50 transition-[top] duration-200">
                <!-- Menú de navegación principal -->
                <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                        <!-- Lado izquierdo: Botón del menú + Logo -->
                        <div class="flex items-center space-x-4">
                            <!-- Botón del menú hamburguesa -->
                            <button class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out" @click="showingNavigationDropdown = ! showingNavigationDropdown">
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{'hidden': showingNavigationDropdown, 'inline-flex': ! showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    <path :class="{'hidden': ! showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                            
                            <!-- Logo -->
                            <div class="shrink-0 flex items-center">
                                <Link :href="route('dashboard')" @click.capture="handleOfflineNavigation($event, { route: 'dashboard' })">
                                    <ApplicationMark class="block h-9 w-auto" />
                                </Link>
                            </div>
                        </div>

                        <!-- Lado derecho: Información del usuario y sucursal -->
                        <div class="flex items-center space-x-4">
                            <button 
                                v-if="$page.props.auth.user.tipo === 'adminEmpresa' && $page.props.sucursalActiva?.sucursalesDisponibles?.length > 1"
                                @click="abrirModalSucursal"
                                :disabled="connectivity.isLimited"
                                :aria-disabled="connectivity.isLimited"
                                class="text-right p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200 group disabled:cursor-not-allowed disabled:opacity-50">
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white">
                                    {{ $page.props.auth.user.name }}
                                </div>
                                <div class="text-xs text-green-600 dark:text-green-400 flex items-center justify-end group-hover:text-green-700 dark:group-hover:text-green-300">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    {{ $page.props.sucursalActiva.nombre }}
                                    <!-- Icono de cambio -->
                                    <svg class="w-3 h-3 ml-1 opacity-60 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                    </svg>
                                </div>
                            </button>
                            
                            <!-- Versión no clickeable para otros tipos de usuario -->
                            <div 
                                v-else
                                class="text-right p-2">
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ $page.props.auth.user.name }}
                                </div>
                                <div v-if="$page.props.sucursalActiva" class="text-xs text-green-600 dark:text-green-400 flex items-center justify-end">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    {{ $page.props.sucursalActiva.nombre }}
                                </div>
                            </div>

                            <button
                                @click="logout"
                                class="px-3 py-2 text-sm font-semibold text-red-600 border border-red-200 rounded-md hover:bg-red-50 dark:border-red-500 dark:text-red-300 dark:hover:bg-red-900 transition-colors duration-200">
                                Cerrar sesión
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Sidebar de navegación lateral con cuadrícula -->
                <div 
                    :class="{'translate-x-0': showingNavigationDropdown, '-translate-x-full': !showingNavigationDropdown}" 
                    class="fixed top-16 left-0 z-40 w-80 h-[calc(100vh-4rem)] bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 shadow-lg transform transition-transform duration-300 ease-in-out overflow-y-auto menu-scroll">
                    
                    <!-- Sección de navegación con cuadrícula -->
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-600 pb-2">
                            Navegación
                        </h3>
                        
                        <!-- Grid de 3 columnas para los botones de navegación -->
                        <div class="grid grid-cols-3 gap-3 mb-6">
                            <template v-for="item in searchItems" :key="item.label">
                                <Link 
                                    :href="route(item.route, item.params)"
                                    @click.capture="handleOfflineNavigation($event, item)"
                                    :aria-disabled="isItemOfflineBlocked(item)"
                                    :class="[
                                        route().current(item.route) ? 'bg-blue-100 border-blue-500 text-blue-700 dark:bg-blue-900 dark:border-blue-400 dark:text-blue-300' : 'bg-gray-50 border-gray-200 text-gray-700 hover:bg-gray-100 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600',
                                        { 'cursor-not-allowed opacity-50': isItemOfflineBlocked(item) }
                                    ]"
                                    class="flex flex-col items-center justify-center p-3 border-2 rounded-lg transition-all duration-200 hover:shadow-md min-h-[80px] text-center"
                                >
                                    
                                    <!-- Iconos para cada tipo de menú -->
                                    <div class="mb-2">
                                        <!-- Icono de Dashboard -->
                                        <svg v-if="item.label.includes('Inicio')" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                        </svg>
                                        
                                        <!-- Icono de Venta -->
                                        <svg v-else-if="item.label.includes('Venta')" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                        </svg>
                                        
                                        <!-- Icono de Inventario -->
                                        <svg v-else-if="item.label.includes('Inventario')" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                        </svg>

                                        <!-- Icono de Transferencias -->
                                        <svg v-else-if="item.label.includes('Transferencias')" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                        </svg>
                                        
                                        <!-- Icono de Compras -->
                                        <svg v-else-if="item.label.includes('Compra')" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                        </svg>
                                        
                                        <!-- Icono de Clientes -->
                                        <svg v-else-if="item.label.includes('Cliente')" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11a3 3 0 100-6 3 3 0 000 6zM6 18a4 4 0 014-4h4a4 4 0 014 4"></path>
                                        </svg>

                                        <!-- Icono de Clasificación -->
                                        <svg v-else-if="item.label.includes('Clasificación')" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12l3-3 4 4 7-7"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 21l-4-4-2 2-2-2-2 2"></path>
                                        </svg>

                                        <!-- Icono de Marca -->
                                        <svg v-else-if="item.label.includes('Marca')" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7a1 1 0 011-1h4l5 5a1 1 0 010 1.414l-4.586 4.586a1 1 0 01-1.414 0L7 12V7z"></path>
                                            <circle cx="10.5" cy="9.5" r="0.75"></circle>
                                        </svg>

                                        <!-- Icono de Enfermedades -->
                                        <svg v-else-if="item.label.includes('Enfermedad')" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="4"></circle>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 16v-2m6-6h2M4 12h2m11.5 4.5l1.4 1.4M6.1 6.1l1.4 1.4m8 0l1.4-1.4M6.1 17.9l1.4-1.4m3.1-2.6l1.4 1.4m0-1.4l-1.4 1.4"></path>
                                        </svg>

                                        <!-- Icono de Flores -->
                                        <svg v-else-if="item.label.includes('Tipo de Flores') || item.label.includes('Flor')" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="2"></circle>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4c1.8 0 3 1.2 3 3s-1.2 3-3 3-3-1.2-3-3 1.2-3 3-3zm0 10c1.8 0 3 1.2 3 3s-1.2 3-3 3-3-1.2-3-3 1.2-3 3-3zm-6-4c0-1.8 1.2-3 3-3s3 1.2 3 3-1.2 3-3 3-3-1.2-3-3zm12 0c0 1.8-1.2 3-3 3s-3-1.2-3-3 1.2-3 3-3 3 1.2 3 3z"></path>
                                        </svg>
                                        
                                        <!-- Icono de Reportes -->
                                        <svg v-else-if="item.label.includes('Reporte')" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                        
                                        <!-- Icono de Pedidos -->
                                        <svg v-else-if="item.label.includes('Pedido')" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        </svg>
                                        
                                        <!-- Icono genérico para otros elementos -->
                                        <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                        </svg>
                                    </div>
                                    
                                    <span class="text-xs font-medium leading-tight">{{ item.label }}</span>
                                    <RequiresConnection v-if="isItemOfflineBlocked(item)" label="Requiere conexión" class="mt-1" />
                                </Link>
                            </template>
                        </div>
                    </div>

                    <!-- Sección de usuario y configuración -->
                    <div class="p-4 border-t border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Usuario
                        </h3>
                        
                        <!-- Información del usuario -->
                        <div class="flex items-center mb-4 p-3 bg-white dark:bg-gray-800 rounded-lg border">
                            <div v-if="$page.props.jetstream.managesProfilePhotos" class="shrink-0 mr-3">
                                <img class="h-12 w-12 rounded-full object-cover" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
                            </div>
                            <div class="flex-1">
                                <div class="font-medium text-base text-gray-800 dark:text-gray-200">
                                    {{ $page.props.auth.user.name }}
                                </div>
                                <div class="font-medium text-sm text-gray-500">
                                    {{ $page.props.auth.user.email }}
                                </div>
                                <div v-if="$page.props.sucursalActiva" class="font-medium text-sm text-green-600 dark:text-green-400">
                                    📍 {{ $page.props.sucursalActiva.nombre }}
                                </div>
                            </div>
                        </div>

                        <!-- Botones de configuración en cuadrícula -->
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <!-- Botón de Perfil -->
                            <Link 
                                :href="route('profile.show')"
                                @click.capture="handleOfflineNavigation($event, { route: 'profile.show' })"
                                :aria-disabled="isItemOfflineBlocked({ route: 'profile.show' })"
                                :class="[
                                    route().current('profile.show') ? 'bg-blue-100 border-blue-500 text-blue-700 dark:bg-blue-900 dark:border-blue-400 dark:text-blue-300' : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700',
                                    { 'cursor-not-allowed opacity-50': isItemOfflineBlocked({ route: 'profile.show' }) }
                                ]"
                                class="flex flex-col items-center justify-center p-3 border-2 rounded-lg transition-all duration-200 hover:shadow-md min-h-[80px] text-center"
                            >
                                <svg class="w-6 h-6 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span class="text-xs font-medium">Perfil</span>
                            </Link>

                            <!-- Botón de Manual -->
                            <a 
                                :href="route('manual')" 
                                target="_blank"
                                @click="handleOfflineNavigation($event, { route: 'manual' })"
                                :aria-disabled="isItemOfflineBlocked({ route: 'manual' })"
                                class="flex flex-col items-center justify-center p-3 border-2 border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 rounded-lg transition-all duration-200 hover:shadow-md min-h-[80px] text-center"
                                :class="{ 'cursor-not-allowed opacity-50': isItemOfflineBlocked({ route: 'manual' }) }">
                                <svg class="w-6 h-6 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                                <span class="text-xs font-medium">Manual</span>
                            </a>

                            <!-- Botón de Suscripción -->
                            <Link
                                :href="route('subscription.show')"
                                @click.capture="handleOfflineNavigation($event, { route: 'subscription.show' })"
                                :aria-disabled="isItemOfflineBlocked({ route: 'subscription.show' })"
                                :class="[
                                    route().current('subscription.show') ? 'bg-emerald-100 border-emerald-500 text-emerald-800 dark:bg-emerald-900 dark:border-emerald-400 dark:text-emerald-200' : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700',
                                    { 'cursor-not-allowed opacity-50': isItemOfflineBlocked({ route: 'subscription.show' }) }
                                ]"
                                class="flex flex-col items-center justify-center p-3 border-2 rounded-lg transition-all duration-200 hover:shadow-md min-h-[80px] text-center"
                            >
                                <svg class="w-6 h-6 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 10h20M4 6h16a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2z"></path>
                                </svg>
                                <span class="text-xs font-medium">Suscripción</span>
                            </Link>
                        </div>

                        <!-- Botón de cambiar sucursal si aplica -->
                        <div v-if="$page.props.auth.user.tipo === 'adminEmpresa' && $page.props.sucursalActiva?.sucursalesDisponibles?.length > 1" class="mb-4">
                            <button 
                                @click="abrirModalSucursal"
                                :disabled="connectivity.isLimited"
                                :aria-disabled="connectivity.isLimited"
                                class="w-full flex items-center justify-center p-3 border-2 border-green-200 bg-green-50 text-green-700 hover:bg-green-100 dark:bg-green-900 dark:border-green-600 dark:text-green-300 dark:hover:bg-green-800 rounded-lg transition-all duration-200 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                </svg>
                                <span class="text-sm font-medium">Cambiar Sucursal</span>
                            </button>
                        </div>

                        <!-- Botón de cerrar sesión -->
                        <form @submit.prevent="logout" class="w-full">
                            <button 
                                type="submit"
                                class="w-full flex items-center justify-center p-3 border-2 border-red-200 bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900 dark:border-red-600 dark:text-red-300 dark:hover:bg-red-800 rounded-lg transition-all duration-200 hover:shadow-md">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                <span class="text-sm font-medium">Cerrar Sesión</span>
                            </button>
                        </form>
                    </div>
                </div>

            </nav>

            <!-- Overlay para cerrar el sidebar en móviles -->
            <div 
                v-if="showingNavigationDropdown"
                @click="showingNavigationDropdown = false"
                class="fixed inset-0 z-30 bg-black bg-opacity-50 lg:hidden">
            </div>

            <div 
                :class="[
                    { 'lg:ml-80': showingNavigationDropdown },
                    connectivity.mode !== 'online' ? 'pt-28' : 'pt-16'
                ]"
                class="transition-all duration-300 ease-in-out">
                <!-- Encabezado de la página -->
                <header v-if="$slots.header" class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-8xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        <slot name="header" />
                    </div>
                </header>

                <!-- Contenido de la página -->
                <main>
                    <slot />
                </main>
            </div>
        </div>

        <!-- Modal de selección de sucursal -->
        <SucursalSelectionModal 
            :show="showingSucursalModal"
            :sucursal-activa="$page.props.sucursalActiva"
            @close="cerrarModalSucursal"
        />
    </div>
</template>

<style scoped>
/* Estilo personalizado para el scroll del menú */
.menu-scroll::-webkit-scrollbar {
    width: 6px;
}

.menu-scroll::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.menu-scroll::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.menu-scroll::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Para modo oscuro */
.dark .menu-scroll::-webkit-scrollbar-track {
    background: #374151;
}

.dark .menu-scroll::-webkit-scrollbar-thumb {
    background: #6b7280;
}

.dark .menu-scroll::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}

/* Para Firefox */
.menu-scroll {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f1f5f9;
}

.dark .menu-scroll {
    scrollbar-color: #6b7280 #374151;
}
</style>
