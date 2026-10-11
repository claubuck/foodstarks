<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'

const props = defineProps({
  reservations: Object,
  filters: Object,
})

const date = ref(props.filters?.date || '')
const status = ref(props.filters?.status || '')

watch([date, status], () => {
  router.get(route('reservations.index'), { date: date.value || undefined, status: status.value || undefined }, { preserveState: true })
})
</script>

<template>
  <Head title="Reservas" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <span>Reservas</span>
        <Link :href="route('reservations.create')" class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-medium shadow-sm transition bg-primary-600 text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">Nueva reserva</Link>
      </div>
    </template>
    <div class="space-y-4">
      <div class="flex flex-wrap items-center gap-4">
        <input v-model="date" type="date" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        <select v-model="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
          <option value="">Todos</option>
          <option value="pending">Pendiente</option>
          <option value="confirmed">Confirmada</option>
          <option value="completed">Completada</option>
          <option value="cancelled">Cancelada</option>
          <option value="no_show">No show</option>
        </select>
      </div>
      <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Fecha / Hora</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Cliente</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Mesa</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Comensales</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Estado</th>
              <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-for="r in reservations.data" :key="r.id" class="hover:bg-gray-50">
              <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ new Date(r.reservation_time).toLocaleString('es') }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ r.customer_name }}<br /><span class="text-gray-500">{{ r.customer_phone }}</span></td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ r.table ? `Mesa ${r.table.table_number}` : '—' }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ r.party_size }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ r.status }}</td>
              <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                <Link :href="route('reservations.edit', r.id)" class="text-blue-600 hover:underline">Editar</Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="flex justify-center gap-2">
        <template v-for="(link, i) in reservations.links" :key="i">
          <component :is="link.url ? Link : 'span'" :href="link.url" v-html="link.label" class="rounded border px-3 py-1 text-sm" :class="{ 'bg-blue-500 text-white': link.active, 'text-gray-700 hover:bg-gray-100': !link.active && link.url, 'text-gray-400': !link.url }" />
        </template>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
