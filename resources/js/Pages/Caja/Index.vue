<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import CashPanel from '@/Components/Cash/CashPanel.vue';
defineProps({ cash: Object });
</script>
<template>
    <AppLayout title="Apertura y corte de caja">
        <div class="mx-auto max-w-6xl px-4 py-6"><CashPanel :cash="cash" /></div>
    </AppLayout>
</template>
