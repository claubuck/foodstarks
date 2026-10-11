<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'

defineProps({
  branches: Array,
})

function destroy(id) {
  if (confirm('¿Eliminar esta sucursal? Los productos, mesas y órdenes asociados pueden verse afectados.')) {
    router.delete(route('branches.destroy', id))
  }
}
</script>

<template>
  <Head title="Sucursales" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <span>Sucursales</span>
        <div class="flex items-center gap-4">
          <Link :href="route('restaurant.edit')" class="text-sm text-primary-600 hover:underline">Restaurante</Link>
          <Link :href="route('product-categories.index')" class="text-sm text-primary-600 hover:underline">Categorías</Link>
          <Link :href="route('branches.create')" class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-medium shadow-sm transition bg-primary-600 text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">Nueva sucursal</Link>
        </div>
      </div>
    </template>
    <div class="space-y-4">
      <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Nombre</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Dirección</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Teléfono</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Estado</th>
              <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-for="b in branches" :key="b.id" class="hover:bg-gray-50">
              <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-700">{{ b.name }}</td>
              <td class="px-4 py-3 text-sm text-gray-600">{{ b.address || '—' }}</td>
              <td class="px-4 py-3 text-sm text-gray-600">{{ b.phone || '—' }}</td>
              <td class="px-4 py-3">
                <span :class="b.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'" class="inline-flex rounded-full px-2 py-1 text-xs font-medium">
                  {{ b.is_active ? 'Activa' : 'Inactiva' }}
                </span>
              </td>
              <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                <Link :href="route('branches.edit', b.id)" class="text-primary-600 hover:underline">Editar</Link>
                <button type="button" class="ml-3 text-red-600 hover:underline" :disabled="branches.length <= 1" @click="destroy(b.id)">
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
