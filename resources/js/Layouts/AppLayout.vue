<script setup>
import { ref, computed } from 'vue';
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
const page = usePage();
const isLoading = ref(false);

router.on('start', () => (isLoading.value = true));
router.on('finish', () => (isLoading.value = false));

const logout = () => {
    router.post(route('logout'));
};

const navItems = computed(() => {
    const tipo = page.props.auth.user.tipo;
    return [
        { type: 'link', label: 'Inicio', route: 'dashboard' },
        {
            type: 'dropdown',
            label: 'Venta',
            condition: ['vendedor', 'superAdmin', 'admin'].includes(tipo),
            children: [
                { label: 'Venta', route: 'venta.index' },
                { label: 'Devoluciones', route: 'devoluciones.list' },
            ],
        },
        {
            type: 'dropdown',
            label: 'Administración de Inventario',
            condition: ['inventario', 'admin', 'superAdmin'].includes(tipo),
            children: [
                { label: 'Inventario', route: 'inventario.index' },
                { label: 'Compra a proveedores', route: 'compra.index' },
            ],
        },
        {
            type: 'dropdown',
            label: 'Clientes',
            condition: ['vendedor', 'admin', 'superAdmin'].includes(tipo),
            children: [
                { label: 'Clientes', route: 'cliente.index' },
                { label: 'Facturas de ventas', route: 'facturas.index', params: { fechaInicio, fechaFin } },
            ],
        },
        { type: 'link', label: 'Reportes', route: 'reporte' },
        { type: 'link', label: 'Pedidos', route: 'pedidos.index' },
        {
            type: 'dropdown',
            label: 'Administración de Catálogos',
            condition: ['inventario', 'admin', 'superAdmin'].includes(tipo),
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
            condition: ['admin', 'superAdmin'].includes(tipo),
            children: [
                { label: 'Empresas', route: 'empresa.index' },
                { label: 'Sucursal', route: 'sucursal.index' },
                { label: 'Usuarios', route: 'usuario.index' },
            ],
        },
    ];
});

const searchItems = computed(() => {
    const items = [];
    navItems.value.forEach((i) => {
        if (i.type === 'link') {
            items.push({ label: i.label, route: i.route, params: i.params });
        } else if (i.type === 'dropdown' && i.condition) {
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

        <Toast />
        <Loading :show="isLoading" />
        <div>
            <nav class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 fixed top-0 w-full z-50">
                <!-- Menú de navegación principal -->
                <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <!-- Logotipo -->
                            <div class="shrink-0 flex items-center">
                                <Link :href="route('dashboard')">
                                    <ApplicationMark class="block h-9 w-auto" />
                                </Link>
                            </div>

                            <!-- Enlaces de navegación -->
                            <div class="hidden space-x-10 md:-my-px md:ml-10 md:flex flex-row flex-wrap justify-center">
                                <template v-for="item in navItems" :key="item.label">
                                    <NavLink v-if="item.type === 'link'" :href="route(item.route, item.params)" :active="route().current(item.route + '*')">
                                        {{ item.label }}
                                    </NavLink>
                                    <div v-else-if="item.type === 'dropdown' && item.condition" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700 focus:outline-none focus:text-gray-700 dark:focus:text-gray-300 focus:border-gray-300 dark:focus:border-gray-700 transition duration-150 ease-in-out">
                                        <Dropdown>
                                            <template #trigger>
                                                <button type="button">{{ item.label }}</button>
                                            </template>
                                            <template #content>
                                                <DropdownLink v-for="child in item.children" :key="child.label" :href="route(child.route, child.params)" :active="route().current(child.route)">
                                                    {{ child.label }}
                                                </DropdownLink>
                                            </template>
                                        </Dropdown>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="hidden md:flex md:items-center md:ml-6 space-x-4">
                            <SearchBar :items="searchItems" />
                            <!-- Menú de configuración -->
                            <div class="ml-3 relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button v-if="$page.props.jetstream.managesProfilePhotos" class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 transition">
                                            <img class="h-8 w-8 rounded-full object-cover" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
                                        </button>

                                        <span v-else class="inline-flex rounded-md">
                                            <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150">
                                                {{ $page.props.auth.user.name }}
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <!-- Gestión de la cuenta -->
                                        <div class="block px-4 py-2 text-xs text-gray-400">
                                            Administración de la cuenta
                                        </div>

                                        <DropdownLink :href="route('profile.show')">
                                            Perfil
                                        </DropdownLink>

                                        <div class="border-t border-gray-200 dark:border-gray-600" />

                                        <!-- Autenticación -->
                                        <form @submit.prevent="logout">
                                            <DropdownLink as="button">
                                                Cerrar sesión
                                            </DropdownLink>
                                        </form>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Botón de menú -->
                        <div class="-mr-2 flex items-center md:hidden">
                            <button class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out" @click="showingNavigationDropdown = ! showingNavigationDropdown">
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{'hidden': showingNavigationDropdown, 'inline-flex': ! showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    <path :class="{'hidden': ! showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Menú de navegación adaptable -->
                <div :class="{'block': showingNavigationDropdown, 'hidden': ! showingNavigationDropdown}" class="md:hidden">
                    <div class="pt-2 pb-3 space-y-1">
                        <ResponsiveNavLink v-for="item in searchItems" :key="item.label" :href="route(item.route, item.params)" :active="route().current(item.route)">
                            {{ item.label }}
                        </ResponsiveNavLink>
                    </div>

                    <!-- Opciones de configuración adaptables -->
                    <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
                        <div class="flex items-center px-4">
                            <div v-if="$page.props.jetstream.managesProfilePhotos" class="shrink-0 mr-3">
                                <img class="h-10 w-10 rounded-full object-cover" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
                            </div>

                            <div>
                                <div class="font-medium text-base text-gray-800 dark:text-gray-200">
                                    {{ $page.props.auth.user.name }}
                                </div>
                                <div class="font-medium text-sm text-gray-500">
                                    {{ $page.props.auth.user.email }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.show')" :active="route().current('profile.show')">
                                Perfil
                            </ResponsiveNavLink>

                            <!-- Autenticación -->
                            <form method="POST" @submit.prevent="logout">
                                <ResponsiveNavLink as="button">
                                    Cerrar sesión
                                </ResponsiveNavLink>
                            </form>

                        </div>
                    </div>
                </div>
            </nav>

            <div class="pt-16">
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
    </div>
</template>
