<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import RecordCard from '@/Components/RecordCard.vue';
import { formatFecha } from '@/utils/formato';
import { ref, watch, defineProps, onMounted } from 'vue';
import { usePersistedForm } from '@/stores/formStore';
import PaginationBar from '@/Components/PaginationBar.vue'
import VueSingleSelect from '@/Components/VueSingleSelect.vue';
import InputError from '@/Components/InputError.vue';
import AddInventarioModal from '@/Components/AddInventarioModal.vue';

const props = defineProps({
    producto: Object,
    enfermedadesFlor: Array,
    solucion: Object,
    solucionesByProduct: Array,
});

// Modal state
const showInventarioModal = ref(false);

const { form, reset } = usePersistedForm('agregarSolucionForm', {
    id_producto: props.solucion != null ? props.solucion.id_producto : props.producto.id,
    id_enfermedad_tipo_flor: props.solucion != null ? props.solucion.id_enfermedad_tipo_flor : 0,
    dosis_bomba_ml: props.solucion != null ? props.solucion.dosis_bomba_ml : 0,
    dosis_tambo_ml: props.solucion != null ? props.solucion.dosis_tambo_ml : 0,
    condiciones: props.solucion != null ? props.solucion.condiciones : '',
    id: props.solucion != null ? props.solucion.id : null,
});

const clientErrors = ref({
    id_enfermedad_tipo_flor: '',
    dosis_bomba_ml: '',
    dosis_tambo_ml: '',
});

const validateRequired = () => {
    clientErrors.value = {
        id_enfermedad_tipo_flor: form.id_enfermedad_tipo_flor ? '' : 'Campo obligatorio',
        dosis_bomba_ml:
            form.dosis_bomba_ml !== null && form.dosis_bomba_ml !== '' && Number(form.dosis_bomba_ml) > 0
                ? ''
                : 'Campo obligatorio (mayor a 0)',
        dosis_tambo_ml:
            form.dosis_tambo_ml !== null && form.dosis_tambo_ml !== '' && Number(form.dosis_tambo_ml) > 0
                ? ''
                : 'Campo obligatorio (mayor a 0)',
    };

    return !clientErrors.value.id_enfermedad_tipo_flor
        && !clientErrors.value.dosis_bomba_ml
        && !clientErrors.value.dosis_tambo_ml;
};

// Detectar si viene de la creación de un producto
onMounted(() => {
    // Verificar si hay un parámetro que indique que viene de producto recién creado
    const urlParams = new URLSearchParams(window.location.search);
    const fromProductCreation = urlParams.get('from_product_creation');

    if (fromProductCreation === 'true') {
        showInventarioModal.value = true;
        // Limpiar el parámetro de la URL sin recargar la página
        window.history.replaceState({}, document.title, window.location.pathname + '?id_producto=' + props.producto.id);
    }
});

const submit = async () => {
    if (!validateRequired()) {
        return;
    }

    if (props.solucion == undefined) {
        form.post(route('solucion.store'), {
            onSuccess: reset,
        });
    } else {
        form.put(route('solucion.update', props.solucion.id), {
            onSuccess: reset,
        });
    }
}



// Funciones del modal de inventario
const openInventarioModal = () => {
    showInventarioModal.value = true;
};

const closeInventarioModal = () => {
    showInventarioModal.value = false;
};

const onInventarioSuccess = () => {
    // Recargar la página para actualizar el stock del producto
    window.location.reload();
};


</script>

<template>
    <AppLayout title="Solución">
        <template #header>
            <PageHeader :title="`Soluciones de ${props.producto.nombre}`"
                :subtitle="(props.producto.cantidad || 0) > 0 ? `Existencia actual: ${props.producto.cantidad} unidades` : 'Sin existencia disponible'"
                :back="route('inventario.index')">
                <button type="button" @click="openInventarioModal" class="ui-btn ui-btn-primary w-full sm:w-auto">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                    Agregar inventario
                </button>
            </PageHeader>
        </template>

        <div class="ui-page">
            <div class="ui-card flex flex-col gap-4 p-4 sm:p-5">
                <div>
                    <h2 class="ui-section-title">{{ props.solucion ? 'Editar solución' : 'Nueva solución' }}</h2>
                    <p class="ui-help">Los campos marcados con * son obligatorios.</p>
                </div>

                <div class="ui-field">
                    <label class="ui-label">Enfermedad y flor que afecta <span class="text-red-600" title="Campo obligatorio">*</span></label>
                    <vue-single-select id="solucion" placeholder="Seleccione enfermedad"
                        v-model="form.id_enfermedad_tipo_flor" option-key="id" option-label="nombre"
                        :options="enfermedadesFlor">
                    </vue-single-select>
                    <InputError :message="form.errors.id_enfermedad_tipo_flor" />
                    <p v-if="clientErrors.id_enfermedad_tipo_flor" class="text-sm text-red-600">{{ clientErrors.id_enfermedad_tipo_flor }}</p>
                </div>

                <form class="flex flex-col gap-4" @submit.prevent="submit">
                    <div class="ui-field">
                        <label class="ui-label">Condiciones de aplicación</label>
                        <textarea class="ui-input" v-model="form.condiciones"></textarea>
                        <InputError :message="form.errors.condiciones" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="ui-field">
                            <label class="ui-label">Dosis por bomba en ml/gr <span class="text-red-600" title="Campo obligatorio">*</span></label>
                            <input type="number" step="0.01" min="0" class="ui-input" :class="{ 'ui-input-error': clientErrors.dosis_bomba_ml }"
                                v-model="form.dosis_bomba_ml" placeholder="Ej: 25.5">
                            <InputError :message="form.errors.dosis_bomba_ml" />
                            <p v-if="clientErrors.dosis_bomba_ml" class="text-sm text-red-600">{{ clientErrors.dosis_bomba_ml }}</p>
                            <p class="ui-help">Dosis recomendada por bomba de 20 litros</p>
                        </div>

                        <div class="ui-field">
                            <label class="ui-label">Dosis por tambo en ml/gr <span class="text-red-600" title="Campo obligatorio">*</span></label>
                            <input type="number" step="0.01" min="0" class="ui-input" :class="{ 'ui-input-error': clientErrors.dosis_tambo_ml }"
                                v-model="form.dosis_tambo_ml" placeholder="Ej: 375">
                            <InputError :message="form.errors.dosis_tambo_ml" />
                            <p v-if="clientErrors.dosis_tambo_ml" class="text-sm text-red-600">{{ clientErrors.dosis_tambo_ml }}</p>
                            <p class="ui-help">Dosis recomendada por tambo de 300 litros</p>
                        </div>
                    </div>

                    <div class="ui-alert ui-alert-blue">
                        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        <p>Los campos de dosis son obligatorios para guardar la solución. Ingrese cada valor según las recomendaciones específicas del producto.</p>
                    </div>

                    <button class="ui-btn ui-btn-primary ui-btn-lg w-full sm:w-auto sm:self-end" :disabled="form.processing">Guardar</button>
                </form>
            </div>

            <h2 class="ui-section-title">Soluciones registradas</h2>
            <div class="ui-grid">
                <RecordCard v-for="solucionByProduct in solucionesByProduct.data" :key="solucionByProduct.id"
                    :eyebrow="`#${solucionByProduct.id}`"
                    :title="`${solucionByProduct.enfermedad?.nombre ?? '—'} en ${solucionByProduct.tipoFlor?.nombre ?? '—'}`"
                    :fields="[
                        { label: 'Dosis por bomba', value: solucionByProduct.dosis_bomba_ml },
                        { label: 'Dosis por tambo', value: solucionByProduct.dosis_tambo_ml },
                        { label: 'Condiciones de aplicación', value: solucionByProduct.condiciones, wide: true },
                        { label: 'Última actualización', value: formatFecha(solucionByProduct.updated_at), wide: true },
                    ]" />
            </div>
            <p v-if="!solucionesByProduct.data?.length" class="ui-muted">Este producto aún no tiene soluciones registradas.</p>
            <PaginationBar :paginator="solucionesByProduct" :prefix="'&id_producto=' + props.producto.id" />
        </div>

        <!-- Modal de agregar inventario -->
        <AddInventarioModal :show="showInventarioModal" :producto="props.producto" @close="closeInventarioModal"
            @success="onInventarioSuccess" />
    </AppLayout>
</template>

