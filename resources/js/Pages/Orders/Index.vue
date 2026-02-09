<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { ref, watch } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";

const props = defineProps({
  orders: Object,
  filters: Object,
});

const status = ref(props.filters?.status || "");
const date = ref(props.filters?.date || "");

watch([status, date], () => {
  router.get(route("orders.index"), { status: status.value || undefined, date: date.value || undefined }, { preserveState: true });
});
</script>

<template>
  <Head title="Órdenes" />
  <AuthenticatedLayout>
    <template #header>
      <span>Órdenes</span>
    </template>
    <div class="space-y-4">
      <div class="flex flex-wrap items-center gap-4">
        <input
          v-model="date"
          type="date"
          class="rounded-lg border border-gray-300 px-3 py-2 text-sm"
        />
        <select v-model="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
          <option value="">Todos los estados</option>
          <option value="pending">Pendiente</option>
          <option value="in_progress">En progreso</option>
          <option value="completed">Completado</option>
          <option value="cancelled">Cancelado</option>
        </select>
      </div>
      <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Fecha</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Mesa</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Total</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Estado</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Tipo</th>
              <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-for="order in orders.data" :key="order.id" class="hover:bg-gray-50">
              <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                {{ new Date(order.created_at).toLocaleString("es") }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-700">
                {{ order.table ? `Mesa ${order.table.table_number}` : "—" }}
              </td>
              <td class="px-4 py-3 text-sm font-medium text-gray-700">${{ Number(order.total_amount).toFixed(2) }}</td>
              <td class="px-4 py-3">
                <span
                  class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                  :class="{
                    'bg-amber-100 text-amber-800': order.status === 'pending',
                    'bg-blue-100 text-blue-800': order.status === 'in_progress',
                    'bg-green-100 text-green-800': order.status === 'completed',
                    'bg-red-100 text-red-800': order.status === 'cancelled',
                  }"
                >
                  {{ order.status }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ order.type }}</td>
              <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                <Link :href="route('orders.show', order.id)" class="text-blue-600 hover:underline">Ver</Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="flex justify-center gap-2">
        <template v-for="(link, i) in orders.links" :key="i">
          <component
            :is="link.url ? Link : 'span'"
            :href="link.url"
            v-html="link.label"
            class="rounded border px-3 py-1 text-sm"
            :class="{
              'bg-blue-500 text-white': link.active,
              'text-gray-700 hover:bg-gray-100': !link.active && link.url,
              'text-gray-400': !link.url,
            }"
          />
        </template>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
