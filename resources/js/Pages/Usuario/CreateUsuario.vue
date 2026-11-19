
<script setup>
    import AppLayout from'@/Layouts/AppLayout.vue';
    import PageHeader from '@/Components/PageHeader.vue';
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

    const { form, reset } = usePersistedForm(props.usuario ? `usuarioForm-${props.usuario.id}` : 'usuarioForm', {
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
    <AppLayout title="Usuario">
        <template #header>
            <PageHeader :title="usuario ? 'Editar usuario' : 'Nuevo usuario'" subtitle="Acceso y permisos" :back="route('usuario.index')" />
        </template>

        <div class="ui-page ui-page-narrow">
            <form class="ui-card flex flex-col gap-4 p-4 sm:p-5" @submit.prevent="submit">
                <div class="ui-field">
                    <label class="ui-label">Nombre</label>
                    <input type="text" class="ui-input" v-model="form.name">
                    <InputError :message="form.errors.name" />
                </div>

                <div class="ui-field">
                    <label class="ui-label">Email</label>
                    <input type="email" class="ui-input" v-model="form.email">
                    <InputError :message="form.errors.email" />
                </div>

                <div class="ui-field">
                    <label class="ui-label">{{ props.usuario ? 'Contraseña (opcional)' : 'Contraseña' }}</label>
                    <PasswordInput
                        v-model="form.password"
                        :placeholder="props.usuario ? 'Dejar vacío para conservar la contraseña actual' : 'Ingrese la contraseña'"
                        input-class="ui-input"
                        autocomplete="new-password"
                        :required="!props.usuario" />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="ui-field">
                    <label class="ui-label">Tipo de usuario</label>
                    <vue-single-select v-model="tipoSeleccionado" :options="tipoOptions" option-label="label" placeholder="Seleccione" class="w-full" />
                    <InputError :message="form.errors.tipo" />
                </div>

                <!-- Selector de Empresa (solo para adminEmpresa) -->
                <div v-if="mostrarEmpresa" class="ui-field">
                    <label class="ui-label">Empresa</label>
                    <vue-single-select v-model="empresaSeleccionada" :options="empresas" option-label="nombre" placeholder="Seleccione una empresa" class="w-full" />
                    <InputError :message="form.errors.id_empresa" />
                </div>

                <!-- Selector de Sucursal (para otros tipos de usuario) -->
                <div v-if="mostrarSucursal" class="ui-field">
                    <label class="ui-label">Sucursal</label>
                    <vue-single-select v-model="sucursalSeleccionada" :options="sucursales" option-label="nombre" placeholder="Seleccione una sucursal" class="w-full" />
                    <InputError :message="form.errors.id_sucursal" />
                </div>

                <button class="ui-btn ui-btn-primary ui-btn-lg w-full sm:w-auto sm:self-end" :disabled="form.processing">Guardar</button>
            </form>
        </div>
    </AppLayout>
</template>
