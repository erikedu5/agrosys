
<script setup>
    import AppLayout from'@/Layouts/AppLayout.vue';
    import { usePersistedForm } from '@/stores/formStore';
    import InputError from '@/Components/InputError.vue';
    import VueSingleSelect from '@/Components/VueSingleSelect.vue';
    import PasswordInput from '@/Components/PasswordInput.vue';
    import { ref, watch, computed } from 'vue';

    const props=defineProps({
        usuario: Object,
        sucursales: {
            type: Array,
            default: []
        },
        empresas: {
            type: Array,
            default: []
        },
        isSuperAdmin: {
            type: Boolean,
            default: false
        }
    });

    const { form, reset } = usePersistedForm('usuarioForm', {
        name: props.usuario !== undefined ? props.usuario.name : '',
        password: '',
        email: props.usuario !== undefined ? props.usuario.email: '',
        tipo: props.usuario !== undefined ? props.usuario.tipo: '',
        id: props.usuario !== undefined ? props.usuario.id: null,
        id_sucursal: props.usuario !== undefined ? props.usuario.id_sucursal: (props.sucursales[0]?.id || null),
        id_empresa: props.usuario !== undefined ? props.usuario.id_empresa : null,
    });

    const tipoOptions = [
        { value: 'admin', label: 'Administrador Sucursal' },
        { value: 'vendedor', label: 'Vendedor' },
        { value: 'inventario', label: 'Inventario' }
    ];

    if (props.isSuperAdmin) {
        tipoOptions.push({ value: 'adminEmpresa', label: 'Administrador Empresa' });
    }

    const tipoSeleccionado = ref(tipoOptions.find(o => o.value === form.tipo) || null);
    watch(tipoSeleccionado, (v) => {
        form.tipo = v ? v.value : '';
        // Limpiar selecciones cuando cambia el tipo
        if (v?.value === 'adminEmpresa') {
            form.id_sucursal = null;
            sucursalSeleccionada.value = null;
        } else {
            form.id_empresa = null;
            empresaSeleccionada.value = null;
        }
    });

    const sucursalSeleccionada = ref(props.sucursales.find(s => s.id === form.id_sucursal) || null);
    watch(sucursalSeleccionada, (v) => {
        form.id_sucursal = v ? v.id : null;
    });

    const empresaSeleccionada = ref(props.empresas.find(e => e.id === form.id_empresa) || null);
    watch(empresaSeleccionada, (v) => {
        form.id_empresa = v ? v.id : null;
    });

    // Computed para mostrar dinámicamente empresa o sucursal
    const mostrarEmpresa = computed(() => form.tipo === 'adminEmpresa');
    const mostrarSucursal = computed(() => form.tipo !== 'adminEmpresa' && form.tipo !== '');

    const submit = () => {
        if (props.usuario == undefined) {
            form.post(route('usuario.store'), {
                onSuccess: reset,
            });
        } else {
            form.put(route('usuario.update', props.usuario.id), {
                onSuccess: reset,
            });
        }
    }
</script>

<template>
    <AppLayout title="CrearUsuario">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Crear Usuario
            </h2>
        </template>

        <div class="flex max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 mt-5">
            <div class="grow">
                <div class="md-col-span-2 mt-5 md:mt-0">
                    <div class="shadow bg-white md:rounded-md p-4">
                        <form @submit.prevent="submit">
                            <label class="block font-medium text-sm text-gray-700">Nombre</label>
                            <input type="text"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.name">
                            <InputError class="mt-2" :message="form.errors.name" />
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Password</label>
                            <PasswordInput 
                                v-model="form.password"
                                placeholder="Ingrese la contraseña"
                                input-class="form-input w-full rounded-md shadow-sm"
                                autocomplete="new-password"
                                required />
                            <InputError class="mt-2" :message="form.errors.password" />
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Email</label>
                            <input type="email"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.email">
                            <InputError class="mt-2" :message="form.errors.email" />
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Tipo de usuario</label>
                            <vue-single-select v-model="tipoSeleccionado" :options="tipoOptions" option-label="label" placeholder="Selecione" class="w-full" />
                            <InputError class="mt-2" :message="form.errors.tipo" />

                            <br>

                            <!-- Selector de Empresa (solo para adminEmpresa) -->
                            <div v-if="mostrarEmpresa">
                                <label class="block font-medium text-sm text-gray-700">Empresa</label>
                                <vue-single-select v-model="empresaSeleccionada" :options="empresas" option-label="nombre" placeholder="Seleccione una empresa" class="w-full" />
                                <InputError class="mt-2" :message="form.errors.id_empresa" />
                                <br>
                            </div>

                            <!-- Selector de Sucursal (para otros tipos de usuario) -->
                            <div v-if="mostrarSucursal">
                                <label class="block font-medium text-sm text-gray-700">Sucursal</label>
                                <vue-single-select v-model="sucursalSeleccionada" :options="sucursales" option-label="nombre" placeholder="Seleccione una sucursal" class="w-full" />
                                <InputError class="mt-2" :message="form.errors.id_sucursal" />
                                <br>
                            </div>
                            <br>

                            <button class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                            hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                            focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                            dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                Guardar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
