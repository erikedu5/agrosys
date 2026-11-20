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
        },
        rolesPermitidos: {
            type: Array,
            default: () => []
        },
        empresasAuditor: {
            type: Array,
            default: () => []
        }
    });

    const alcancesIniciales = (props.usuario?.alcances || []).map(a => ({ id_empresa: a.id_empresa, id_sucursal: a.id_sucursal }));

    const { form, reset } = usePersistedForm(props.usuario ? `usuarioForm-${props.usuario.id}` : 'usuarioForm', {
        name: props.usuario !== undefined ? props.usuario.name : '',
        password: '',
        email: props.usuario !== undefined ? props.usuario.email: '',
        tipo: props.usuario !== undefined ? props.usuario.tipo: '',
        id: props.usuario !== undefined ? props.usuario.id: null,
        id_sucursal: props.usuario !== undefined ? props.usuario.id_sucursal: (props.sucursales[0]?.id || null),
        id_empresa: props.usuario !== undefined ? props.usuario.id_empresa : null,
        alcances: alcancesIniciales,
        acceso_hasta: props.usuario?.acceso_hasta_fecha || '',
    });
    if (!Array.isArray(form.alcances)) {
        form.alcances = [];
    }

    const todasLasOpciones = [
        { value: 'admin', label: 'Administrador Sucursal' },
        { value: 'vendedor', label: 'Vendedor' },
        { value: 'inventario', label: 'Inventario' },
        { value: 'adminEmpresa', label: 'Administrador Empresa' },
        { value: 'auditor', label: 'Auditor externo' },
    ];

    // El servidor decide qué roles puede asignar quien edita; la UI sólo lo refleja.
    const tipoOptions = todasLasOpciones.filter(o => props.rolesPermitidos.length === 0
        ? ['admin', 'vendedor', 'inventario'].includes(o.value) || (props.isSuperAdmin && o.value === 'adminEmpresa')
        : props.rolesPermitidos.includes(o.value));

    const tipoSeleccionado = ref(tipoOptions.find(o => o.value === form.tipo) || null);
    watch(tipoSeleccionado, (v) => {
        form.tipo = v ? v.value : '';
        // Limpiar selecciones cuando cambia el tipo
        if (v?.value === 'adminEmpresa') {
            form.id_sucursal = null;
            sucursalSeleccionada.value = null;
        } else if (v?.value === 'auditor') {
            form.id_sucursal = null;
            form.id_empresa = null;
            sucursalSeleccionada.value = null;
            empresaSeleccionada.value = null;
            if (form.alcances.length === 0) {
                agregarAlcance();
            }
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
    const esAuditor = computed(() => form.tipo === 'auditor');
    const mostrarEmpresa = computed(() => form.tipo === 'adminEmpresa');
    const mostrarSucursal = computed(() => !['adminEmpresa', 'auditor', ''].includes(form.tipo));

    const sucursalesDe = (empresaId) => props.sucursales.filter(s => Number(s.id_empresa) === Number(empresaId));

    const agregarAlcance = () => {
        form.alcances.push({ id_empresa: props.empresasAuditor.length === 1 ? props.empresasAuditor[0].id : '', id_sucursal: null });
    };
    const quitarAlcance = (index) => {
        form.alcances.splice(index, 1);
    };
    const onEmpresaAlcance = (alcance) => {
        alcance.id_sucursal = null;
    };

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

                <!-- Alcance del auditor externo -->
                <template v-if="esAuditor">
                    <div class="ui-alert ui-alert-blue">
                        El auditor sólo accede al módulo de auditorías. No puede modificar inventario.
                        Al abrir una auditoría elegirá empresa (si tiene varias) y sucursal/bodega dentro de este alcance.
                    </div>

                    <div class="ui-field">
                        <div class="flex items-center justify-between gap-2">
                            <label class="ui-label">Alcance</label>
                            <button v-if="isSuperAdmin || form.alcances.length === 0" type="button" class="ui-btn" @click="agregarAlcance">Agregar empresa</button>
                        </div>
                        <div v-for="(alcance, index) in form.alcances" :key="index" class="ui-card flex flex-col gap-2 p-3 sm:flex-row sm:items-end">
                            <div class="ui-field flex-1">
                                <label class="ui-label">Empresa</label>
                                <select v-model="alcance.id_empresa" class="ui-input" @change="onEmpresaAlcance(alcance)">
                                    <option value="" disabled>Seleccione</option>
                                    <option v-for="empresa in empresasAuditor" :key="empresa.id" :value="empresa.id">{{ empresa.nombre }}</option>
                                </select>
                            </div>
                            <div class="ui-field flex-1">
                                <label class="ui-label">Sucursal / bodega</label>
                                <select v-model="alcance.id_sucursal" class="ui-input" :disabled="!alcance.id_empresa">
                                    <option :value="null">Todas las sucursales</option>
                                    <option v-for="sucursal in sucursalesDe(alcance.id_empresa)" :key="sucursal.id" :value="sucursal.id">
                                        {{ sucursal.nombre }}{{ sucursal.es_bodega ? ' (bodega)' : '' }}
                                    </option>
                                </select>
                            </div>
                            <button type="button" class="ui-btn ui-btn-danger" @click="quitarAlcance(index)">Quitar</button>
                        </div>
                        <button v-if="!isSuperAdmin && form.alcances.length > 0" type="button" class="ui-btn self-start" @click="agregarAlcance">Agregar sucursal</button>
                        <p class="ui-help">"Todas las sucursales" incluye las que se den de alta después.</p>
                        <InputError :message="form.errors.alcances" />
                    </div>

                    <div class="ui-field">
                        <label class="ui-label">Acceso válido hasta (opcional)</label>
                        <input type="date" class="ui-input" v-model="form.acceso_hasta">
                        <p class="ui-help">Después de esta fecha el auditor ya no podrá iniciar sesión ni operar auditorías.</p>
                        <InputError :message="form.errors.acceso_hasta" />
                    </div>
                </template>

                <button class="ui-btn ui-btn-primary ui-btn-lg w-full sm:w-auto sm:self-end" :disabled="form.processing">Guardar</button>
            </form>
        </div>
    </AppLayout>
</template>
