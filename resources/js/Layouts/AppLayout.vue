<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';

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


const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div>
        <Head :title="title" />

        <Banner />

        <div>
            <nav class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
                <!-- Primary Navigation Menu -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="shrink-0 flex items-center">
                                <Link :href="route('dashboard')">
                                    <ApplicationMark class="block h-9 w-auto" />
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-10 md:-my-px md:ml-10 md:flex flex-row flex-wrap justify-center">
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                    Dashboard
                                </NavLink>
                                <div v-if="$page.props.auth.user.tipo == 'vendedor' ||
                                               $page.props.auth.user.tipo == 'superAdmin' ||
                                               $page.props.auth.user.tipo == 'admin'"
                                    class="inline-flex items-center px-1 pt-1 border-b-2
                                            border-transparent text-sm font-medium leading-5
                                            text-gray-500 dark:text-gray-400 hover:text-gray-700
                                            dark:hover:text-gray-300 hover:border-gray-300
                                            dark:hover:border-gray-700 focus:outline-none focus:text-gray-700
                                            dark:focus:text-gray-300 focus:border-gray-300
                                            dark:focus:border-gray-700 transition
                                            duration-150 ease-in-out">
                                    <Dropdown>
                                        <template #trigger >
                                            <button type="button">
                                                Venta
                                            </button>
                                        </template>

                                        <template #content>
                                            <DropdownLink :href="route('venta.index')" :active="route().current('venta.index')">
                                                Venta
                                            </DropdownLink>
                                            <DropdownLink :href="route('devoluciones.list')" :active="route().current('devoluciones.list')">
                                                Devoluciones
                                            </DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>

                                <div v-if="$page.props.auth.user.tipo == 'inventario' ||
                                           $page.props.auth.user.tipo == 'admin' ||
                                           $page.props.auth.user.tipo == 'superAdmin'"
                                    class="inline-flex items-center px-1 pt-1 border-b-2
                                            border-transparent text-sm font-medium leading-5
                                            text-gray-500 dark:text-gray-400 hover:text-gray-700
                                            dark:hover:text-gray-300 hover:border-gray-300
                                            dark:hover:border-gray-700 focus:outline-none focus:text-gray-700
                                            dark:focus:text-gray-300 focus:border-gray-300
                                            dark:focus:border-gray-700 transition
                                            duration-150 ease-in-out">
                                    <Dropdown>
                                        <template #trigger >
                                            <button type="button">
                                                Administración de Inventario
                                            </button>
                                        </template>

                                        <template #content>
                                            <DropdownLink :href="route('inventario.index')">
                                                Inventario
                                            </DropdownLink>

                                            <DropdownLink :href="route('compra.index')">
                                                Compra a proveedores
                                            </DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>

                                <div v-if="$page.props.auth.user.tipo == 'vendedor' ||
                                           $page.props.auth.user.tipo == 'admin' ||
                                           $page.props.auth.user.tipo == 'superAdmin'"
                                    class="inline-flex items-center px-1 pt-1 border-b-2
                                            border-transparent text-sm font-medium leading-5
                                            text-gray-500 dark:text-gray-400 hover:text-gray-700
                                            dark:hover:text-gray-300 hover:border-gray-300
                                            dark:hover:border-gray-700 focus:outline-none focus:text-gray-700
                                            dark:focus:text-gray-300 focus:border-gray-300
                                            dark:focus:border-gray-700 transition
                                            duration-150 ease-in-out">
                                    <Dropdown>
                                        <template #trigger >
                                            <button type="button">
                                                Clientes
                                            </button>
                                        </template>

                                        <template #content>
                                            <DropdownLink :href="route('cliente.index')" :active="route().current('cliente.*')">
                                                Clientes
                                            </DropdownLink>

                                            <DropdownLink :href="route('facturas.index', {'fechaInicio': fechaInicio, 'fechaFin': fechaFin })" :active="route().current('cliente.*')">
                                                Facturas de ventas
                                            </DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>

                                <NavLink :href="route('reporte')" :active="route().current('reporte.*')">
                                    Reportes
                                </NavLink>
                                <NavLink :href="route('pedidos.index')" :active="route().current('pedidos.index')">
                                    Pedidos
                                </NavLink>
                                <div v-if="$page.props.auth.user.tipo == 'inventario' ||
                                           $page.props.auth.user.tipo == 'admin' ||
                                           $page.props.auth.user.tipo == 'superAdmin'"
                                    class="inline-flex items-center px-1 pt-1 border-b-2
                                            border-transparent text-sm font-medium leading-5
                                            text-gray-500 dark:text-gray-400 hover:text-gray-700
                                            dark:hover:text-gray-300 hover:border-gray-300
                                            dark:hover:border-gray-700 focus:outline-none focus:text-gray-700
                                            dark:focus:text-gray-300 focus:border-gray-300
                                            dark:focus:border-gray-700 transition
                                            duration-150 ease-in-out">
                                    <Dropdown>
                                        <template #trigger>
                                            <button type="button">
                                                Administración de Catálogos
                                            </button>
                                        </template>

                                        <template #content>
                                            <DropdownLink :href="route('clasificacion.index')">
                                                Catalogo de Clasificación
                                            </DropdownLink>

                                            <DropdownLink :href="route('marca.index')">
                                                Catalogo de Marca
                                            </DropdownLink>

                                            <DropdownLink :href="route('enfermedad.index')">
                                                Catalogo de Enfermedades
                                            </DropdownLink>

                                            <DropdownLink :href="route('tipoFlor.index')">
                                                Catalogo de Tipo de Flores
                                            </DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>
                                <div v-if="$page.props.auth.user.tipo == 'superAdmin' || $page.props.auth.user.tipo == 'admin' "
                                    class="inline-flex items-center px-1 pt-1 border-b-2
                                            border-transparent text-sm font-medium leading-5
                                            text-gray-500 dark:text-gray-400 hover:text-gray-700
                                            dark:hover:text-gray-300 hover:border-gray-300
                                            dark:hover:border-gray-700 focus:outline-none focus:text-gray-700
                                            dark:focus:text-gray-300 focus:border-gray-300
                                            dark:focus:border-gray-700 transition
                                            duration-150 ease-in-out">
                                    <Dropdown>
                                        <template #trigger>
                                            <button type="button">
                                                Administración
                                            </button>
                                        </template>

                                        <template #content >
                                            <DropdownLink v-if="$page.props.auth.user.tipo == 'superAdmin'"
                                             :href="route('empresa.index')">
                                                Empresas
                                            </DropdownLink>

                                            <DropdownLink :href="route('sucursal.index')">
                                                Sucursal
                                            </DropdownLink>

                                            <DropdownLink :href="route('usuario.index')">
                                                Usuarios
                                            </DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>
                            </div>
                        </div>

                        <div class="hidden md:flex md:items-center md:ml-6">

                            <!-- Settings Dropdown -->
                            <div class="ml-3 relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button v-if="$page.props.jetstream.managesProfilePhotos" class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 transition">
                                            <img class="h-8 w-8 rounded-full object-cover" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
                                        </button>

                                        <span v-else class="inline-flex rounded-md">
                                            <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150">
                                                {{ $page.props.auth.user.name }}

                                                <!--svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                                </svg-->
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <!-- Account Management -->
                                        <div class="block px-4 py-2 text-xs text-gray-400">
                                            Administrador de cuenta
                                        </div>

                                        <DropdownLink :href="route('profile.show')">
                                            Perfil
                                        </DropdownLink>

                                        <div class="border-t border-gray-200 dark:border-gray-600" />

                                        <!-- Authentication -->
                                        <form @submit.prevent="logout">
                                            <DropdownLink as="button">
                                                Cerrar sessión
                                            </DropdownLink>
                                        </form>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-mr-2 flex items-center md:hidden">
                            <button class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out" @click="showingNavigationDropdown = ! showingNavigationDropdown">
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{'hidden': showingNavigationDropdown, 'inline-flex': ! showingNavigationDropdown }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{'hidden': ! showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div :class="{'block': showingNavigationDropdown, 'hidden': ! showingNavigationDropdown}" class="md:hidden">
                    <div class="pt-2 pb-3 space-y-1">
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            Dashboard
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="$page.props.auth.user.tipo == 'vendedor' ||
                                                 $page.props.auth.user.tipo == 'admin' ||
                                                 $page.props.auth.user.tipo == 'superAdmin' "
                                               :href="route('venta.index')" :active="route().current('venta.index')">
                            Venta
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="$page.props.auth.user.tipo == 'vendedor' ||
                                                 $page.props.auth.user.tipo == 'admin' ||
                                                 $page.props.auth.user.tipo == 'superAdmin' "
                                               :href="route('devoluciones.list')" :active="route().current('devoluciones.list')">
                            Devoluciones
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="$page.props.auth.user.tipo == 'inventario' ||
                                                 $page.props.auth.user.tipo == 'admin' ||
                                                 $page.props.auth.user.tipo == 'superAdmin' "
                                               :href="route('inventario.index')" :active="route().current('inventario.*')">
                            Inventario
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="$page.props.auth.user.tipo == 'vendedor' ||
                                                 $page.props.auth.user.tipo == 'admin' ||
                                                 $page.props.auth.user.tipo == 'superAdmin'"
                                               :href="route('cliente.index')" :active="route().current('cliente.*')">
                             Clientes
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('reporte')" :active="route().current('reporte.*')">
                            Reportes
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('pedidos.index')" :active="route().current('pedidos.index')">
                            Pedidos
                        </ResponsiveNavLink>

                        <ResponsiveNavLink v-if="$page.props.auth.user.tipo == 'inventario'  ||
                                               $page.props.auth.user.tipo == 'superAdmin' ||
                                               $page.props.auth.user.tipo == 'admin'"
                                               href="">
                            --------- Administración de Catalogos ---------
                        </ResponsiveNavLink>

                        <ResponsiveNavLink v-if="$page.props.auth.user.tipo == 'inventario' ||
                                               $page.props.auth.user.tipo == 'superAdmin' ||
                                               $page.props.auth.user.tipo == 'admin' "
                                               :href="route('clasificacion.index')" :active="route().current('clasificacion.*')">
                            Catalogo de Clasificación
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="$page.props.auth.user.tipo == 'inventario' ||
                                                 $page.props.auth.user.tipo == 'admin' ||
                                                 $page.props.auth.user.tipo == 'superAdmin' "
                                               :href="route('marca.index')" :active="route().current('marca.*')">
                            Catalogo de Marca
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="$page.props.auth.user.tipo == 'inventario' ||
                                                 $page.props.auth.user.tipo == 'admin' ||
                                                 $page.props.auth.user.tipo == 'superAdmin' "
                                               :href="route('enfermedad.index')" :active="route().current('enfermedad.*')">
                            Catalogo de Enfermedades
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="$page.props.auth.user.tipo == 'inventario' ||
                                                 $page.props.auth.user.tipo == 'admin' ||
                                                 $page.props.auth.user.tipo == 'superAdmin' "
                                               :href="route('tipoFlor.index')" :active="route().current('tipoFlor.*')">
                            Catalogo de Tipo de Flores
                        </ResponsiveNavLink>
                        <ResponsiveNavLink v-if="$page.props.auth.user.tipo == 'inventario' ||
                                                 $page.props.auth.user.tipo == 'admin' ||
                                                 $page.props.auth.user.tipo == 'superAdmin' "
                                               :href="route('tipoFlor.index')" :active="route().current('tipoFlor.*')">
                            Catalogo de Tipo de cultivo
                        </ResponsiveNavLink>

                        <ResponsiveNavLink v-if="$page.props.auth.user.tipo == 'superAdmin' ||
                                                 $page.props.auth.user.tipo == 'admin' "
                                               href="">
                            --------- Administración ---------
                        </ResponsiveNavLink>

                        <ResponsiveNavLink v-if="$page.props.auth.user.tipo == 'superAdmin' ||
                                                 $page.props.auth.user.tipo == 'admin' "
                                               :href="route('empresa.index')" :active="route().current('empresa.*')">
                            Empresas
                        </ResponsiveNavLink>

                        <ResponsiveNavLink v-if="$page.props.auth.user.tipo == 'superAdmin' ||
                                                 $page.props.auth.user.tipo == 'admin' "
                                               :href="route('sucursal.index')" :active="route().current('sucursal.*')">
                            Sucursales
                        </ResponsiveNavLink>

                        <ResponsiveNavLink v-if="$page.props.auth.user.tipo == 'superAdmin' ||
                                                 $page.props.auth.user.tipo == 'admin' "
                                               :href="route('usuario.index')" :active="route().current('usuario.*')">
                            Usuarios
                        </ResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options -->
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

                            <!-- Authentication -->
                            <form method="POST" @submit.prevent="logout">
                                <ResponsiveNavLink as="button">
                                    Cerrar Sessión
                                </ResponsiveNavLink>
                            </form>

                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header v-if="$slots.header" class="bg-white dark:bg-gray-800 shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
