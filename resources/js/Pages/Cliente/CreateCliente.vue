
<script setup>
    import AppLayout from'@/Layouts/AppLayout.vue';
    import PageHeader from '@/Components/PageHeader.vue';
    import { usePersistedForm } from '@/stores/formStore';
    import Checkbox from '@/Components/Checkbox.vue';
    import InputError from '@/Components/InputError.vue';
    import { watch } from 'vue';
    import { TIPOS_PRECIO } from '@/utils/precios';

    const props=defineProps({cliente: Object});

    const { form, reset } = usePersistedForm('clienteForm', {
        nombre: props.cliente !== undefined ? props.cliente.nombre : '',
        requiereFactura: props.cliente !== undefined ? props.cliente.requiereFactura: false,
        rfc: props.cliente !== undefined ? props.cliente.rfc: null,
        regimen_fiscal: props.cliente !== undefined ? props.cliente.regimen_fiscal : '',
        codigo_postal: props.cliente !== undefined ? props.cliente.codigo_postal : '',
        uso_cfdi: props.cliente !== undefined ? props.cliente.uso_cfdi : '',
        email_facturacion: props.cliente !== undefined ? props.cliente.email_facturacion : '',
        tipo_precio: props.cliente !== undefined ? (props.cliente.tipo_precio ?? 'publico') : 'publico',
        limite_credito: props.cliente !== undefined ? (props.cliente.limite_credito ?? '') : '',
        plazo_credito_dias: props.cliente !== undefined ? (props.cliente.plazo_credito_dias ?? '') : '',
        id: props.cliente !== undefined ? props.cliente.id: null,
    });

    const regimenesFiscales = [
        { value: '601', label: '601 - General de Ley Personas Morales' },
        { value: '603', label: '603 - Personas Morales con Fines no Lucrativos' },
        { value: '605', label: '605 - Sueldos y Salarios e Ingresos Asimilados a Salarios' },
        { value: '606', label: '606 - Arrendamiento' },
        { value: '608', label: '608 - Demás ingresos' },
        { value: '612', label: '612 - Personas Físicas con Actividades Empresariales y Profesionales' },
        { value: '621', label: '621 - Incorporación Fiscal' },
        { value: '622', label: '622 - Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras' },
        { value: '623', label: '623 - Opcional para Grupos de Sociedades' },
        { value: '624', label: '624 - Coordinados' },
        { value: '626', label: '626 - Régimen Simplificado de Confianza' },
    ];

    const usosCfdi = [
        { value: 'G01', label: 'G01 - Adquisición de mercancías' },
        { value: 'G02', label: 'G02 - Devoluciones, descuentos o bonificaciones' },
        { value: 'G03', label: 'G03 - Gastos en general' },
        { value: 'I01', label: 'I01 - Construcciones' },
        { value: 'I02', label: 'I02 - Mobiliario y equipo de oficina por inversiones' },
        { value: 'I03', label: 'I03 - Equipo de transporte' },
        { value: 'I04', label: 'I04 - Equipo de computo y accesorios' },
        { value: 'I05', label: 'I05 - Dados, troqueles, moldes, matrices y herramental' },
        { value: 'I06', label: 'I06 - Comunicaciones telefónicas' },
        { value: 'I07', label: 'I07 - Comunicaciones satelitales' },
        { value: 'I08', label: 'I08 - Otra maquinaria y equipo' },
        { value: 'P01', label: 'P01 - Por definir' },
    ];

    const applyClienteData = (cliente) => {
        form.nombre = cliente?.nombre ?? '';
        form.requiereFactura = Boolean(cliente?.requiereFactura);
        form.rfc = cliente?.rfc ?? null;
        form.regimen_fiscal = cliente?.regimen_fiscal ?? '';
        form.codigo_postal = cliente?.codigo_postal ?? '';
        form.uso_cfdi = cliente?.uso_cfdi ?? '';
        form.email_facturacion = cliente?.email_facturacion ?? '';
        form.tipo_precio = cliente?.tipo_precio ?? 'publico';
        form.limite_credito = cliente?.limite_credito ?? '';
        form.plazo_credito_dias = cliente?.plazo_credito_dias ?? '';
        form.id = cliente?.id ?? null;
    };

    if (props.cliente) {
        applyClienteData(props.cliente);
    }

    watch(() => props.cliente, (cliente) => {
        if (cliente) {
            applyClienteData(cliente);
        }
    });

    watch(() => form.requiereFactura, (requiere) => {
        if (!requiere) {
            form.rfc = null;
            form.regimen_fiscal = null;
            form.codigo_postal = null;
            form.uso_cfdi = null;
            form.email_facturacion = null;
        }
    });

    const submit = () => {
        if (props.cliente == undefined) {
            form.post(route('cliente.store'), {
                onSuccess: () => {
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Cliente guardado' } }));
                    reset();
                },
            });
        } else {
            form.put(route('cliente.update', props.cliente.id), {
                onSuccess: () => {
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Cliente actualizado' } }));
                    reset();
                },
            });
        }
    }
</script>

<template>
    <AppLayout title="Cliente">
        <template #header>
            <PageHeader :title="cliente ? 'Editar cliente' : 'Nuevo cliente'" subtitle="Precio, crédito y facturación" :back="route('cliente.index')" />
        </template>

        <div class="ui-page ui-page-narrow">
            <form class="flex flex-col gap-4" @submit.prevent="submit">
                <section class="ui-card flex flex-col gap-4 p-4 sm:p-5">
                    <h2 class="ui-section-title">Datos del cliente</h2>
                    <div class="ui-field">
                        <label class="ui-label">Nombre</label>
                        <input type="text" required class="ui-input" :class="{ 'ui-input-error': form.errors.nombre }" v-model="form.nombre">
                        <InputError :message="form.errors.nombre" />
                    </div>

                    <div class="ui-field">
                        <label class="ui-label">Precio asignado</label>
                        <select class="ui-input" :class="{ 'ui-input-error': form.errors.tipo_precio }" v-model="form.tipo_precio">
                            <option v-for="tipo in TIPOS_PRECIO" :key="tipo.value" :value="tipo.value">{{ tipo.label }}</option>
                        </select>
                        <p class="ui-help">Precio del catálogo de productos que se usará en las ventas de este cliente.</p>
                        <InputError :message="form.errors.tipo_precio" />
                    </div>
                </section>

                <section class="ui-card flex flex-col gap-4 p-4 sm:p-5">
                    <h2 class="ui-section-title">Crédito</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="ui-field">
                            <label class="ui-label">Límite de crédito ($)</label>
                            <input type="number" step="0.01" min="0" placeholder="Sin límite" class="ui-input" :class="{ 'ui-input-error': form.errors.limite_credito }" v-model="form.limite_credito">
                            <InputError :message="form.errors.limite_credito" />
                        </div>
                        <div class="ui-field">
                            <label class="ui-label">Plazo de crédito (días)</label>
                            <input type="number" step="1" min="1" placeholder="Sin plazo" class="ui-input" :class="{ 'ui-input-error': form.errors.plazo_credito_dias }" v-model="form.plazo_credito_dias">
                            <InputError :message="form.errors.plazo_credito_dias" />
                        </div>
                    </div>
                    <p class="ui-help">Si el adeudo excede el límite o la venta a crédito sin pagar más antigua rebasa el plazo, al cliente solo se le podrá vender de contado. Vacío = sin restricción.</p>
                </section>

                <section class="ui-card flex flex-col gap-4 p-4 sm:p-5">
                    <h2 class="ui-section-title">Facturación</h2>
                    <label class="ui-check">
                        <Checkbox v-model:checked="form.requiereFactura" />
                        Requiere factura
                    </label>

                    <template v-if="form.requiereFactura">
                        <div class="ui-field">
                            <label class="ui-label">RFC</label>
                            <input :required="form.requiereFactura" class="ui-input" :class="{ 'ui-input-error': form.errors.rfc }" v-model="form.rfc">
                            <InputError :message="form.errors.rfc" />
                        </div>

                        <div class="ui-field">
                            <label class="ui-label">Régimen fiscal</label>
                            <select :required="form.requiereFactura" class="ui-input" :class="{ 'ui-input-error': form.errors.regimen_fiscal }" v-model="form.regimen_fiscal">
                                <option value="" disabled>Seleccione una opción</option>
                                <option v-for="regimen in regimenesFiscales" :key="regimen.value" :value="regimen.value">{{ regimen.label }}</option>
                            </select>
                            <InputError :message="form.errors.regimen_fiscal" />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="ui-field">
                                <label class="ui-label">Código postal</label>
                                <input :required="form.requiereFactura" class="ui-input" :class="{ 'ui-input-error': form.errors.codigo_postal }" v-model="form.codigo_postal">
                                <InputError :message="form.errors.codigo_postal" />
                            </div>
                            <div class="ui-field">
                                <label class="ui-label">Uso CFDI</label>
                                <select :required="form.requiereFactura" class="ui-input" :class="{ 'ui-input-error': form.errors.uso_cfdi }" v-model="form.uso_cfdi">
                                    <option value="" disabled>Seleccione una opción</option>
                                    <option v-for="uso in usosCfdi" :key="uso.value" :value="uso.value">{{ uso.label }}</option>
                                </select>
                                <InputError :message="form.errors.uso_cfdi" />
                            </div>
                        </div>

                        <div class="ui-field">
                            <label class="ui-label">Correo para facturación (opcional)</label>
                            <input type="email" class="ui-input" :class="{ 'ui-input-error': form.errors.email_facturacion }" v-model="form.email_facturacion">
                            <InputError :message="form.errors.email_facturacion" />
                        </div>
                    </template>
                </section>

                <button class="ui-btn ui-btn-primary ui-btn-lg w-full sm:w-auto sm:self-end" :disabled="form.processing">Guardar</button>
            </form>
        </div>
    </AppLayout>
</template>
