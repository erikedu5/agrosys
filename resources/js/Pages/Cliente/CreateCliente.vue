
<script setup>
    import AppLayout from'@/Layouts/AppLayout.vue';
    import { usePersistedForm } from '@/stores/formStore';
    import Checkbox from '@/Components/Checkbox.vue';
    import InputError from '@/Components/InputError.vue';
    import { watch } from 'vue';

    const props=defineProps({cliente: Object});

    const { form, reset } = usePersistedForm('clienteForm', {
        nombre: props.cliente !== undefined ? props.cliente.nombre : '',
        requiereFactura: props.cliente !== undefined ? props.cliente.requiereFactura: false,
        rfc: props.cliente !== undefined ? props.cliente.rfc: null,
        regimen_fiscal: props.cliente !== undefined ? props.cliente.regimen_fiscal : '',
        codigo_postal: props.cliente !== undefined ? props.cliente.codigo_postal : '',
        uso_cfdi: props.cliente !== undefined ? props.cliente.uso_cfdi : '',
        email_facturacion: props.cliente !== undefined ? props.cliente.email_facturacion : '',
        porcentaje_descuento: props.cliente !== undefined ? props.cliente.porcentaje_descuento: 0,
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
        form.porcentaje_descuento = cliente?.porcentaje_descuento ?? 0;
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
    <AppLayout title="Crear Cliente">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Crear Cliente
            </h2>
        </template>

        <div class="flex max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 mt-5">
            <div class="grow">
                <div class="md-col-span-2 mt-5 md:mt-0">
                    <div class="shadow-lg bg-white md:rounded-md p-4">
                        <form @submit.prevent="submit">
                            <label class="block font-medium text-sm text-gray-700">Nombre</label>
                            <input type="text" required
                                class="form-input w-full rounded-md shadow-sm"
                                :class="{'border-red-500': form.errors.nombre}"
                                v-model="form.nombre">
                            <InputError class="mt-2" :message="form.errors.nombre" />
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Porcentaje de descuento</label>
                            <input type="number" step="0.01"
                                class="form-input w-full rounded-md shadow-sm"
                                :class="{'border-red-500': form.errors.porcentaje_descuento}"
                                v-model="form.porcentaje_descuento">
                            <InputError class="mt-2" :message="form.errors.porcentaje_descuento" />
                            <br>
                            <br>
                            <label class="block font-medium text-sm text-gray-700">
                                <checkbox v-model:checked="form.requiereFactura" />
                                <span class="ml-2 text-sm">Requiere factura</span>
                            </label><br>

                            <div v-if="form.requiereFactura">
                                <label class="block font-medium text-sm text-gray-700">RFC</label>
                                <input :required="form.requiereFactura"
                                    class="form-input w-full rounded-md shadow-sm"
                                    :class="{'border-red-500': form.errors.rfc}"
                                    v-model="form.rfc">
                                <InputError class="mt-2" :message="form.errors.rfc" />
                                <br>
                                <br>

                                <label class="block font-medium text-sm text-gray-700">Régimen fiscal</label>
                                <select :required="form.requiereFactura"
                                    class="form-select w-full rounded-md shadow-sm"
                                    :class="{'border-red-500': form.errors.regimen_fiscal}"
                                    v-model="form.regimen_fiscal">
                                    <option value="" disabled>Seleccione una opción</option>
                                    <option v-for="regimen in regimenesFiscales" :key="regimen.value" :value="regimen.value">
                                        {{ regimen.label }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.regimen_fiscal" />
                                <br>
                                <br>

                                <label class="block font-medium text-sm text-gray-700">Código postal</label>
                                <input :required="form.requiereFactura"
                                    class="form-input w-full rounded-md shadow-sm"
                                    :class="{'border-red-500': form.errors.codigo_postal}"
                                    v-model="form.codigo_postal">
                                <InputError class="mt-2" :message="form.errors.codigo_postal" />
                                <br>
                                <br>

                                <label class="block font-medium text-sm text-gray-700">Uso CFDI</label>
                                <select :required="form.requiereFactura"
                                    class="form-select w-full rounded-md shadow-sm"
                                    :class="{'border-red-500': form.errors.uso_cfdi}"
                                    v-model="form.uso_cfdi">
                                    <option value="" disabled>Seleccione una opción</option>
                                    <option v-for="uso in usosCfdi" :key="uso.value" :value="uso.value">
                                        {{ uso.label }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.uso_cfdi" />
                                <br>
                                <br>

                                <label class="block font-medium text-sm text-gray-700">Correo para facturación (opcional)</label>
                                <input type="email"
                                    class="form-input w-full rounded-md shadow-sm"
                                    :class="{'border-red-500': form.errors.email_facturacion}"
                                    v-model="form.email_facturacion">
                                <InputError class="mt-2" :message="form.errors.email_facturacion" />
                                <br>
                                <br>
                            </div>

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
