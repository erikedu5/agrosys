
<script setup>
    import AppLayout from'@/Layouts/AppLayout.vue';
    import PageHeader from '@/Components/PageHeader.vue';
    import EmptyState from '@/Components/EmptyState.vue';
    import { formatFecha, formatMoneda } from '@/utils/formato';
    import{ useForm }from'@inertiajs/vue3';
    import Pagination from '@/Components/Pagination.vue';
    import { notify } from '@/utils/notify';

    const props = defineProps({
        ventas: Object,
        cliente: Object,
        abonos: Object,
    });

    const form = useForm({
        nombre: props.cliente !== undefined ? props.cliente.nombre : '',
        id: props.cliente !== undefined ? props.cliente.id: null,
        abono: 0
    });

    const abonarNota = () => {
        if (form.abono > 0) {
            form.put(route('venta.update', props.cliente.id), form);
        } else {
            notify('No puedes abonar 0 pesos a una nota', 'error');
        }
    }

</script>

<template>
    <AppLayout title="Créditos">
        <template #header>
            <PageHeader :title="`Crédito de ${props.cliente.nombre}`" subtitle="Notas de venta a crédito y abonos" :back="route('cliente.index')" />
        </template>

        <div class="ui-page">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="ui-card p-4">
                    <p class="ui-muted">Crédito total</p>
                    <p class="text-2xl font-extrabold">{{ formatMoneda(cliente.adeudo_total) }}</p>
                </div>
                <div class="ui-card p-4">
                    <p class="ui-muted">Total de abonos</p>
                    <p class="text-2xl font-extrabold">{{ formatMoneda(cliente.abono_total) }}</p>
                </div>
                <div class="rounded-xl bg-blue-50 p-4 text-blue-900 dark:bg-blue-950 dark:text-blue-200">
                    <p class="text-sm font-semibold">Adeudo actual</p>
                    <p class="text-2xl font-extrabold">{{ formatMoneda(cliente.balance) }}</p>
                </div>
            </div>

            <div v-if="props.ventas.data.length !== 0" class="ui-card flex flex-col gap-3 p-4 sm:flex-row sm:items-end">
                <div class="ui-field flex-1">
                    <label class="ui-label">Abonar a cuenta</label>
                    <input type="number" step="0.01" inputmode="decimal" class="ui-input text-right text-2xl font-bold" v-model="form.abono">
                </div>
                <button type="button" class="ui-btn ui-btn-success ui-btn-lg" :disabled="form.processing" @click="abonarNota()">Abonar a nota</button>
            </div>

            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_360px] lg:items-start">
                <section class="flex min-w-0 flex-col gap-3">
                    <h2 class="ui-section-title">Notas de venta</h2>
                    <article v-for="venta in props.ventas.data" :key="venta.id" class="ui-card overflow-hidden">
                        <header class="flex items-start justify-between gap-3 p-4">
                            <div class="min-w-0">
                                <p class="text-[17px] font-bold leading-snug">Nota #{{ venta.id }}</p>
                                <p class="ui-muted">{{ formatFecha(venta.created_at, true) }}</p>
                            </div>
                            <p class="whitespace-nowrap text-xl font-extrabold">{{ formatMoneda(venta.total) }}</p>
                        </header>
                        <div v-for="product in venta.productos" :key="product.id" class="ui-row">
                            <span class="min-w-0 flex-1 font-semibold">{{ product.detalle }}</span>
                            <span class="ui-badge">{{ product.cantidad }} uds.</span>
                            <span class="min-w-[88px] text-right font-bold">{{ formatMoneda(product.total_productos) }}</span>
                        </div>
                    </article>
                    <EmptyState v-if="!props.ventas.data.length" title="Sin notas a crédito" text="Este cliente no tiene ventas a crédito pendientes." />
                    <Pagination :links="props.ventas.links" :prefix="''" />
                </section>

                <section class="ui-card overflow-hidden">
                    <h2 class="ui-section-title p-4">Abonos</h2>
                    <div v-for="abono in props.abonos.data" :key="abono.id" class="ui-row">
                        <span class="ui-muted min-w-0 flex-1">{{ formatFecha(abono.created_at, true) }}</span>
                        <span class="font-bold">{{ formatMoneda(abono.cantidad_abonada) }}</span>
                    </div>
                    <p v-if="!props.abonos.data?.length" class="ui-row ui-muted">Sin abonos registrados.</p>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
