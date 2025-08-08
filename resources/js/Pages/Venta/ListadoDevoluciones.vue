<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  devoluciones: { type: Array, default: [] }
});
</script>
<template>
    <AppLayout title="Devoluciones">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Devoluciones
            </h2>
        </template>

        <hr class="my-6">

        <div class="flex">
            <div class="flex-none w-14 h-14">
            </div>
            <div class="grow h-14">
                <div class="md-col-span-2 mt-5 md:mt-0">
                    <div class="shadow bg-white md:rounded-md p-4">
                        <div class="flex justify-between">
                            <Link :href="route('venta.devoluciones.index')"
                                  class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                        hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                        focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                        dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                Crear una devolución
                            </Link>
                        </div>
                      </div>

                    <hr class="my-6">

                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                      <table class="min-w-full bg-white">
                        <thead>
                          <tr class="text-left">
                            <th class="px-2 py-1">ID</th>
                            <th class="px-2 py-1">Venta</th>
                            <th class="px-2 py-1">Total devuelto</th>
                            <th class="px-2 py-1">Fecha</th>
                            <th class="px-2 py-1">Productos</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="d in devoluciones" :key="d.id" class="border-t">
                            <td class="px-2 py-1">{{ d.id }}</td>
                            <td class="px-2 py-1">{{ d.venta_id }}</td>
                            <td class="px-2 py-1">${{ d.total }}</td>
                            <td class="px-2 py-1">{{ d.fecha }}</td>
                            <td class="px-2 py-1">
                              <ul>
                                <li v-for="detalle in d.detalles" :key="detalle.id">
                                  {{ detalle.producto.nombre }} - {{ detalle.cantidad }} unidades
                                </li>
                              </ul>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>

                  <div name="Pagination">

                  </div>
              </div>
          </div>
      </div>
      <div class="flex-none w-14 h-14">
      </div>
    </AppLayout>
</template>


