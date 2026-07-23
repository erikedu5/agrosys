<script setup>
defineProps({ label: { type: String, default: 'Esta función requiere conexión.' } });
</script>
<template><span class="inline-flex items-center rounded bg-gray-200 px-2 py-1 text-xs text-gray-600" role="note">{{ label }}</span></template>
