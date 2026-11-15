
<script setup>
    import AppLayout from'@/Layouts/AppLayout.vue';
    import PageHeader from '@/Components/PageHeader.vue';
    import { usePersistedForm } from '@/stores/formStore';
    import InputError from '@/Components/InputError.vue';

const props = defineProps({
    empresa: Object,
    all: {
        type: Boolean,
        default: false
    },
    isSuperAdmin: {
        type: Boolean,
        default: false
    }
});

    const { form, reset } = usePersistedForm('empresaForm', {
        id: props.empresa !== undefined ? props.empresa.id: null,
        nombre: props.empresa !== undefined ? props.empresa.nombre : '',
        direccion: props.empresa !== undefined ? props.empresa.direccion : '',
        telefono: props.empresa !== undefined ? props.empresa.telefono : '',
        email: props.empresa !== undefined ? props.empresa.email : '',
        rfc: props.empresa !== undefined ? props.empresa.rfc : '',
        aviso: props.empresa !== undefined ? props.empresa.aviso : '',
        numero_sucursales: props.empresa !== undefined ? props.empresa.numero_sucursales: 1,
        mostrar_campos_precio: props.empresa !== undefined
            ? Boolean(props.empresa.mostrar_campos_precio)
            : true,
        enviar_facturas_automaticas: props.empresa !== undefined
            ? Boolean(props.empresa.enviar_facturas_automaticas)
            : false,
        ventas_bloqueadas: props.empresa !== undefined
            ? Boolean(props.empresa.ventas_bloqueadas)
            : false,
        motivo_bloqueo: props.empresa !== undefined && props.empresa.motivo_bloqueo
            ? props.empresa.motivo_bloqueo
            : '',
    });

    const submit = () => {
        if (props.empresa == null) {
            form.post(route('empresa.store'), {
                onSuccess: reset,
            });
        } else {
            form.put(route('empresa.update', props.empresa.id), {
                onSuccess: reset,
            });
        }
    }
</script>

<template>
    <AppLayout title="Empresa">
        <template #header>
            <PageHeader :title="empresa ? 'Editar empresa' : 'Nueva empresa'" subtitle="Datos fiscales y configuración" :back="route('empresa.index')" />
        </template>

        <div class="ui-page ui-page-narrow">
            <form class="flex flex-col gap-4" @submit.prevent="submit">
                <section class="ui-card flex flex-col gap-4 p-4 sm:p-5">
                    <h2 class="ui-section-title">Datos de la empresa</h2>
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
                        </div>
                        <div class="ui-field">
                            <label class="ui-label">Email</label>
                            <input type="email" class="ui-input" v-model="form.email">
                            <InputError :message="form.errors.email" />
                        </div>
                    </div>

                    <div class="ui-field">
                        <label class="ui-label">RFC</label>
                        <input type="text" class="ui-input" v-model="form.rfc">
                    </div>

                    <div class="ui-field">
                        <label class="ui-label">Aviso</label>
                        <textarea class="ui-input" v-model="form.aviso" rows="6"></textarea>
                    </div>
                </section>

                <section class="ui-card flex flex-col gap-4 p-4 sm:p-5">
                    <h2 class="ui-section-title">Configuración</h2>
                    <label class="ui-check">
                        <input type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" v-model="form.mostrar_campos_precio">
                        Mostrar precio de compra en productos
                    </label>

                    <div v-if="isSuperAdmin || all" class="ui-field">
                        <label class="ui-check">
                            <input type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" v-model="form.enviar_facturas_automaticas">
                            Enviar facturas automáticamente al generar ventas
                        </label>
                        <p class="ui-help">Al activar esta opción se intentará timbrar y enviar el CFDI en cada venta con cliente que requiera factura.</p>
                    </div>

                    <template v-if="isSuperAdmin">
                        <div class="ui-field">
                            <label class="ui-check">
                                <input type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" v-model="form.ventas_bloqueadas">
                                Bloqueo de funcionalidades de venta e inventario
                            </label>
                            <InputError :message="form.errors.ventas_bloqueadas" />
                        </div>
                        <div class="ui-field">
                            <label class="ui-label">Motivo del bloqueo</label>
                            <textarea class="ui-input" rows="3" v-model="form.motivo_bloqueo" :disabled="!form.ventas_bloqueadas"></textarea>
                            <InputError :message="form.errors.motivo_bloqueo" />
                        </div>
                    </template>

                    <div v-if="!all" class="ui-field">
                        <label class="ui-label">Máximo número de sucursales</label>
                        <input type="number" step="1" min="1" class="ui-input" :class="{ 'ui-input-error': form.errors.numero_sucursales }" v-model="form.numero_sucursales">
                        <InputError :message="form.errors.numero_sucursales" />
                    </div>
                </section>

                <button class="ui-btn ui-btn-primary ui-btn-lg w-full sm:w-auto sm:self-end" :disabled="form.processing">Guardar</button>
            </form>
        </div>
    </AppLayout>
</template>
