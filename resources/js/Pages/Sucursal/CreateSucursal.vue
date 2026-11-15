
<script setup>
    import AppLayout from'@/Layouts/AppLayout.vue';
    import PageHeader from '@/Components/PageHeader.vue';
    import { usePersistedForm } from '@/stores/formStore';
    import InputError from '@/Components/InputError.vue';
    import VueSingleSelect from '@/Components/VueSingleSelect.vue';
    import { ref, watch } from 'vue';

    const props=defineProps({
        sucursal: Object,
        empresas: {
            type: Array,
            default: []
        }
    });

    const { form, reset } = usePersistedForm('sucursalForm', {
        id: props.sucursal !== undefined ? props.sucursal.id: null,
        nombre: props.sucursal !== undefined ? props.sucursal.nombre: '',
        direccion: props.sucursal !== undefined ? props.sucursal.direccion: '',
        telefono: props.sucursal !== undefined ? props.sucursal.telefono: '',
        email: props.sucursal !== undefined ? props.sucursal.email: '',
        es_matriz: props.sucursal !== undefined ? props.sucursal.es_matriz? true: false : false,
        es_bodega: props.sucursal !== undefined ? props.sucursal.es_bodega? true: false : false,
        id_empresa: props.sucursal !== undefined ? props.sucursal.id_empresa : props.empresas[0].id,
        ticket_width_mm: props.sucursal !== undefined && props.sucursal.ticket_width_mm ? props.sucursal.ticket_width_mm : 80,
});

    const empresaSeleccionada = ref(props.empresas.find(e => e.id === form.id_empresa) || null);
    watch(empresaSeleccionada, (val) => {
        form.id_empresa = val ? val.id : null;
    });

    const ticketOptions = [
        { value: 80, label: '80 mm (recomendado)' },
        { value: 58, label: '58 mm' }
    ];
    const ticketSeleccionado = ref(ticketOptions.find(t => t.value === form.ticket_width_mm) || null);
    watch(ticketSeleccionado, (val) => {
        form.ticket_width_mm = val ? val.value : null;
    });

    const submit = () => {
        if (props.sucursal == undefined) {
            form.post(route('sucursal.store'), {
                onSuccess: reset,
            });
        } else {
            form.put(route('sucursal.update', props.sucursal.id), {
                onSuccess: reset,
            });
        }
    }
</script>
<template>
    <AppLayout title="Sucursal">
        <template #header>
            <PageHeader :title="sucursal ? 'Editar sucursal' : 'Nueva sucursal'" subtitle="Datos de contacto y configuración" :back="route('sucursal.index')" />
        </template>

        <div class="ui-page ui-page-narrow">
            <form class="ui-card flex flex-col gap-4 p-4 sm:p-5" @submit.prevent="submit">
                <div class="ui-field">
                    <label class="ui-label">Empresa</label>
                    <vue-single-select v-model="empresaSeleccionada" :options="empresas" option-key="id" option-label="nombre" placeholder="Seleccione una empresa" class="w-full" />
                    <InputError :message="form.errors.id_empresa" />
                </div>

                <div class="ui-field">
                    <label class="ui-label">Nombre</label>
                    <input type="text" class="ui-input" v-model="form.nombre">
                    <InputError :message="form.errors.nombre" />
                </div>

                <div class="ui-field">
                    <label class="ui-label">Dirección</label>
                    <input type="text" class="ui-input" v-model="form.direccion">
                    <InputError :message="form.errors.direccion" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="ui-field">
                        <label class="ui-label">Teléfono</label>
                        <input type="tel" class="ui-input" v-model="form.telefono">
                        <InputError :message="form.errors.telefono" />
                    </div>
                    <div class="ui-field">
                        <label class="ui-label">Email</label>
                        <input type="email" class="ui-input" v-model="form.email">
                        <InputError :message="form.errors.email" />
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="ui-check">
                        <input type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" v-model="form.es_matriz">
                        Es matriz
                    </label>
                    <label class="ui-check">
                        <input type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" v-model="form.es_bodega">
                        Es bodega
                    </label>
                </div>
                <p class="ui-help -mt-2">Una bodega permite realizar transferencias desde esta ubicación.</p>

                <div class="ui-field">
                    <label class="ui-label">Ancho de ticket (mm)</label>
                    <vue-single-select v-model="ticketSeleccionado" :options="ticketOptions" option-key="value" option-label="label" placeholder="Seleccione" class="w-full" />
                    <InputError :message="form.errors.ticket_width_mm" />
                </div>

                <button class="ui-btn ui-btn-primary ui-btn-lg w-full sm:w-auto sm:self-end" :disabled="form.processing">Guardar</button>
            </form>
        </div>
    </AppLayout>
</template>
