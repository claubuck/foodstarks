<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'

defineProps({
  tables: Array,
})

function destroy(id) {
  if (confirm('¿Eliminar esta mesa?')) {
    router.delete(route('tables.destroy', id))
  }
}
</script>

<template>
  <Head title="Mesas" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <span>Mesas</span>
        <Link :href="route('tables.create')" class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-medium shadow-sm transition bg-primary-600 text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">Agregar mesa</Link>
      </div>
    </template>
    <div class="space-y-4">
      <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Número</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Capacidad</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Estado</th>
              <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-for="t in tables" :key="t.id" class="hover:bg-gray-50">
              <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">Mesa {{ t.table_number }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ t.capacity }} personas</td>
              <td class="px-4 py-3">
                <span
                  class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                  :class="{
                    'bg-green-100 text-green-800': t.status === 'available',
                    'bg-orange-100 text-orange-800': t.status === 'occupied',
                    'bg-amber-100 text-amber-800': t.status === 'reserved',
                  }"
                >
                  {{ t.status }}
                </span>
              </td>
              <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                <Link :href="route('tables.edit', t.id)" class="text-blue-600 hover:underline">Editar</Link>
                <button type="button" class="ml-3 text-red-600 hover:underline" @click="destroy(t.id)">
                  Eliminar
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
