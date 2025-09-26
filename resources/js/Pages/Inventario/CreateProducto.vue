<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import VueSingleSelect from '@/Components/VueSingleSelect.vue';
import { ref, watch, onMounted } from 'vue';

const props = defineProps({
    producto: Object,
    clasificaciones: Array,
    marcas: Array,
    enfermedadesFlor: Array,
});

const form = useForm({
    nombre: '',
    id_clasificacion: 0,
    id_marca: 0,
    id: null,
    precio_unitario: 0.0,
    ieps: 0,
    precio_ieps: 0.0,
    tamano: '',
    cantidad: 0,
    ingrediente_activo: '',
    barcode: '',
    id_usuario: 0,
});

const clasificacionSeleccionada = ref(null);
const marcaSeleccionada = ref(null);

// Función para cargar datos del producto
const cargarDatosProducto = () => {
    if (props.producto) {
        form.nombre = props.producto.nombre || '';
        form.id_clasificacion = props.producto.id_clasificacion || 0;
        form.id_marca = props.producto.id_marca || 0;
        form.id = props.producto.id || null;
        form.precio_unitario = props.producto.precio_unitario || 0.0;
        form.ieps = props.producto.ieps || 0;
        form.precio_ieps = props.producto.precio_ieps || 0.0;
        form.tamano = props.producto.tamano || '';
        form.cantidad = props.producto.cantidad || 0;
        form.ingrediente_activo = props.producto.ingrediente_activo || '';
        form.barcode = props.producto.barcode || '';

        // Actualizar los selects
        if (props.clasificaciones && props.clasificaciones.length > 0) {
            clasificacionSeleccionada.value = props.clasificaciones.find(c => c.id === props.producto.id_clasificacion) || null;
        }
        if (props.marcas && props.marcas.length > 0) {
            marcaSeleccionada.value = props.marcas.find(m => m.id === props.producto.id_marca) || null;
        }
    }
};

// Cargar datos al montar el componente
onMounted(() => {
    cargarDatosProducto();
});

// Watchers para los selects
watch(clasificacionSeleccionada, (v) => {
    form.id_clasificacion = v ? v.id : 0;
});

watch(marcaSeleccionada, (v) => {
    form.id_marca = v ? v.id : 0;
});

const validateForm = () => {
    form.clearErrors();
    let hasErrors = false;

    if (!form.nombre || form.nombre.trim() === '') {
        form.setError('nombre', 'El nombre del producto es requerido');
        hasErrors = true;
    }

    if (!form.id_clasificacion || form.id_clasificacion === 0) {
        form.setError('id_clasificacion', 'Debe seleccionar una clasificación');
        hasErrors = true;
    }

    if (!form.id_marca || form.id_marca === 0) {
        form.setError('id_marca', 'Debe seleccionar una marca');
        hasErrors = true;
    }

    if (!form.precio_unitario || parseFloat(form.precio_unitario) <= 0) {
        form.setError('precio_unitario', 'El precio de compra debe ser mayor a 0');
        hasErrors = true;
    }

    if (!form.precio_ieps || parseFloat(form.precio_ieps) <= 0) {
        form.setError('precio_ieps', 'El precio con IEPS debe ser mayor a 0');
        hasErrors = true;
    }

    if (!form.tamano || form.tamano.trim() === '') {
        form.setError('tamano', 'El tamaño del producto es requerido');
        hasErrors = true;
    }

    if (form.ieps === null || form.ieps === undefined || form.ieps === '' || parseFloat(form.ieps) < 0) {
        form.setError('ieps', 'El IEPS debe ser mayor o igual a 0');
        hasErrors = true;
    }

    return !hasErrors;
};

const submit = () => {
    if (!validateForm()) {
        return;
    }

    if (props.producto == undefined) {
        form.post(route('inventario.store'), {
            onSuccess: () => {
                form.reset();
            },
        });
    } else {
        form.put(route('inventario.update', props.producto.id), {
            onSuccess: () => {
                // No resetear en edición
            },
        });
    }
}

const calcularIps = () => {
    if (form.precio_unitario && form.ieps !== null && form.ieps !== undefined) {
        const precioBase = parseFloat(form.precio_unitario) || 0;
        const iepsPercent = parseFloat(form.ieps) || 0;
        form.precio_ieps = (precioBase + (precioBase * iepsPercent / 100)).toFixed(2);
    }
}

const calcularPrecioCompra = () => {
    if (form.precio_ieps && form.ieps !== null && form.ieps !== undefined) {
        const precioConIeps = parseFloat(form.precio_ieps) || 0;
        const iepsPercent = parseFloat(form.ieps) || 0;
        if (iepsPercent > 0) {
            form.precio_unitario = (precioConIeps / (1 + iepsPercent / 100)).toFixed(2);
        } else {
            form.precio_unitario = precioConIeps.toFixed(2);
        }
    }
}
</script>

<template>
    <AppLayout title="CrearProducto">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ props.producto ? 'Editar Producto' : 'Crear Nuevo Producto' }}
            </h2>
        </template>

        <div class="flex max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 mt-3">
            <div class="grow">
                <div class="md-col-span-2 mt-5 md:mt-0">
                    <div class="shadow-lg bg-white md:rounded-md p-4">
                        <form @submit.prevent="submit">
                            <label class="block font-medium text-sm text-gray-700">Nombre *</label>
                            <input type="text" class="form-input w-full rounded-md shadow-sm"
                                :class="{ 'border-red-500': form.errors.nombre }" v-model="form.nombre"
                                @blur="form.nombre && form.clearErrors('nombre')" required>
                            <InputError class="mt-2" :message="form.errors.nombre" />
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Clasificación *</label>
                            <vue-single-select v-model="clasificacionSeleccionada" :options="clasificaciones"
                                option-key="id" option-label="nombre" placeholder="Seleccione una clasificación"
                                class="w-full" />
                            <InputError class="mt-2" :message="form.errors.id_clasificacion" />
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Marca *</label>
                            <vue-single-select v-model="marcaSeleccionada" :options="marcas" option-key="id"
                                option-label="nombre" placeholder="Seleccione una marca" class="w-full" />
                            <InputError class="mt-2" :message="form.errors.id_marca" />
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Precio Compra *</label>
                            <input type="number" step="0.01" min="0.01" @change="calcularIps()"
                                class="form-input w-full rounded-md shadow-sm"
                                :class="{ 'border-red-500': form.errors.precio_unitario }"
                                v-model="form.precio_unitario" required>
                            <InputError class="mt-2" :message="form.errors.precio_unitario" />
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">IEPS (%) *</label>
                            <input type="number" step="0.01" min="0" @change="calcularIps()" @input="calcularIps()"
                                class="form-input w-full rounded-md shadow-sm"
                                :class="{ 'border-red-500': form.errors.ieps }" v-model="form.ieps"
                                placeholder="Ingrese el porcentaje de IEPS (ej: 7.5)" required>
                            <InputError class="mt-2" :message="form.errors.ieps" />
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Precio con IEPS *</label>
                            <input type="number" step="0.01" min="0.01" @change="calcularPrecioCompra()"
                                class="form-input w-full rounded-md shadow-sm"
                                :class="{ 'border-red-500': form.errors.precio_ieps }" v-model="form.precio_ieps"
                                required>
                            <InputError class="mt-2" :message="form.errors.precio_ieps" />
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Tamaño * (Agrega unidad al tamaño
                                ejemplo:
                                "kg", "g", "ml", "l", etc.)</label>
                            <input type="text" class="form-input w-full rounded-md shadow-sm"
                                :class="{ 'border-red-500': form.errors.tamano }" v-model="form.tamano"
                                @blur="form.tamano && form.clearErrors('tamano')" required>
                            <InputError class="mt-2" :message="form.errors.tamano" />
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Ingrediente activo</label>
                            <input type="text" class="form-input w-full rounded-md shadow-sm"
                                v-model="form.ingrediente_activo">
                            <InputError class="mt-2" :message="form.errors.ingrediente_activo" />
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Código de barras</label>
                            <input type="text" class="form-input w-full rounded-md shadow-sm" v-model="form.barcode">
                            <InputError class="mt-2" :message="form.errors.barcode" />
                            <br>
                            <br>

                            <button type="submit" :disabled="form.processing" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md
                                       hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500
                                       disabled:opacity-50 disabled:cursor-not-allowed">
                                <span v-if="form.processing">Guardando...</span>
                                <span v-else>{{ props.producto ? 'Actualizar Producto' : 'Crear Producto' }}</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>