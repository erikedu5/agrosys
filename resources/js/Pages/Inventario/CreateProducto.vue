<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import VueSingleSelect from '@/Components/VueSingleSelect.vue';
import { ref, watch, onMounted, computed } from 'vue';
import { notify } from '@/utils/notify';
import { TIPOS_PRECIO } from '@/utils/precios';

const props = defineProps({
    producto: Object,
    clasificaciones: Array,
    marcas: Array,
    enfermedadesFlor: Array,
});

const page = usePage();
const mostrarCamposPrecio = computed(() => {
    const config = page.props.empresaConfig?.mostrar_campos_precio ?? true;
    const esAdminEmpresa = page.props.auth?.user?.tipo === 'adminEmpresa' || page.props.auth?.user?.tipo === 'superAdmin';
    return esAdminEmpresa ? true : config;
});

const form = useForm({
    nombre: '',
    id_clasificacion: 0,
    id_marca: 0,
    id: null,
    precio_unitario: '',
    precio_publico: '',
    precio_preferencial_1: '',
    precio_preferencial_2: '',
    precio_preferencial_3: '',
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
        TIPOS_PRECIO.forEach(({ campo }) => {
            form[campo] = props.producto[campo] || 0.0;
        });
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

watch(() => form.precio_unitario, (value) => {
    if (value === '' || value === null || value === undefined) {
        form.clearErrors('precio_unitario');
    }
});

// En un producto nuevo los precios de distribuidor siguen al público hasta que se editan.
const preferencialesEditados = ref(false);
const copiarPrecioPublico = (campo) => {
    if (props.producto) {
        return;
    }
    if (campo !== 'precio_publico') {
        preferencialesEditados.value = true;
    } else if (!preferencialesEditados.value) {
        TIPOS_PRECIO.forEach(({ campo }) => {
            if (campo !== 'precio_publico') {
                form[campo] = form.precio_publico;
            }
        });
    }
};

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

    if (
        mostrarCamposPrecio.value &&
        form.precio_unitario !== null &&
        form.precio_unitario !== undefined &&
        form.precio_unitario !== '' &&
        parseFloat(form.precio_unitario) < 0
    ) {
        form.setError('precio_unitario', 'El precio de compra no puede ser negativo');
        hasErrors = true;
    }

    TIPOS_PRECIO.forEach(({ campo, label }) => {
        if (!form[campo] || parseFloat(form[campo]) <= 0) {
            form.setError(campo, `El precio ${label.toLowerCase()} debe ser mayor a 0`);
            hasErrors = true;
        }
    });

    if (!form.tamano || form.tamano.trim() === '') {
        form.setError('tamano', 'El tamaño del producto es requerido');
        hasErrors = true;
    }

    return !hasErrors;
};

const submit = () => {
    if (!validateForm()) {
        return;
    }

    const handleErrors = (errors) => {
        Object.values(errors).forEach((message) => {
            if (message) {
                notify(message, 'error');
            }
        });
    };

    if (props.producto == undefined) {
        form.post(route('inventario.store'), {
            onSuccess: () => {
                form.reset();
                preferencialesEditados.value = false;
            },
            onError: handleErrors,
        });
    } else {
        form.put(route('inventario.update', props.producto.id), {
            onSuccess: () => {
                // No resetear en edición
            },
            onError: handleErrors,
        });
    }
}
</script>

<template>
    <AppLayout title="Producto">
        <template #header>
            <PageHeader :title="props.producto ? 'Editar producto' : 'Nuevo producto'" subtitle="Datos, precios y código" :back="route('inventario.index')" />
        </template>

        <div class="ui-page ui-page-narrow">
            <form class="flex flex-col gap-4" @submit.prevent="submit">
                <section class="ui-card flex flex-col gap-4 p-4 sm:p-5">
                    <h2 class="ui-section-title">Producto</h2>
                    <div class="ui-field">
                        <label class="ui-label">Nombre *</label>
                        <input type="text" class="ui-input" :class="{ 'ui-input-error': form.errors.nombre }" v-model="form.nombre"
                            @blur="form.nombre && form.clearErrors('nombre')" required>
                        <InputError :message="form.errors.nombre" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="ui-field">
                            <label class="ui-label">Clasificación *</label>
                            <vue-single-select v-model="clasificacionSeleccionada" :options="clasificaciones" option-key="id"
                                option-label="nombre" placeholder="Seleccione una clasificación" class="w-full" />
                            <InputError :message="form.errors.id_clasificacion" />
                        </div>
                        <div class="ui-field">
                            <label class="ui-label">Marca *</label>
                            <vue-single-select v-model="marcaSeleccionada" :options="marcas" option-key="id"
                                option-label="nombre" placeholder="Seleccione una marca" class="w-full" />
                            <InputError :message="form.errors.id_marca" />
                        </div>
                    </div>

                    <div class="ui-field">
                        <label class="ui-label">Tamaño *</label>
                        <input type="text" class="ui-input" :class="{ 'ui-input-error': form.errors.tamano }" v-model="form.tamano"
                            @blur="form.tamano && form.clearErrors('tamano')" required>
                        <p class="ui-help">Agrega la unidad al tamaño, por ejemplo: “kg”, “g”, “ml”, “l”.</p>
                        <InputError :message="form.errors.tamano" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="ui-field">
                            <label class="ui-label">Ingrediente activo</label>
                            <input type="text" class="ui-input" v-model="form.ingrediente_activo">
                            <InputError :message="form.errors.ingrediente_activo" />
                        </div>
                        <div class="ui-field">
                            <label class="ui-label">Código de barras</label>
                            <input type="text" class="ui-input" v-model="form.barcode">
                            <InputError :message="form.errors.barcode" />
                        </div>
                    </div>
                </section>

                <section class="ui-card flex flex-col gap-4 p-4 sm:p-5">
                    <h2 class="ui-section-title">Precios</h2>
                    <div v-if="mostrarCamposPrecio" class="ui-field">
                        <label class="ui-label">Precio de compra (opcional)</label>
                        <input type="number" step="0.01" min="0" class="ui-input" :class="{ 'ui-input-error': form.errors.precio_unitario }" v-model="form.precio_unitario">
                        <InputError :message="form.errors.precio_unitario" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div v-for="tipo in TIPOS_PRECIO" :key="tipo.campo" class="ui-field">
                            <label class="ui-label">Precio {{ tipo.label }} *</label>
                            <input type="number" step="0.01" min="0.01" @input="copiarPrecioPublico(tipo.campo)" class="ui-input"
                                :class="{ 'ui-input-error': form.errors[tipo.campo] }" v-model="form[tipo.campo]" required>
                            <InputError :message="form.errors[tipo.campo]" />
                        </div>
                    </div>
                </section>

                <button type="submit" :disabled="form.processing" class="ui-btn ui-btn-primary ui-btn-lg w-full sm:w-auto sm:self-end">
                    {{ form.processing ? 'Guardando...' : (props.producto ? 'Actualizar producto' : 'Crear producto') }}
                </button>
            </form>
        </div>
    </AppLayout>
</template>
