<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import { Head, Link, router } from "@inertiajs/vue3";

const props = defineProps({
  order: Object,
});

function updateStatus(newStatus) {
  router.put(route("orders.update", props.order.id), { status: newStatus });
}

function markPaid() {
  router.put(route("orders.update", props.order.id), { status: "completed", paid: true });
}
</script>

<template>
  <Head title="Detalle del pedido" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <span>Pedido #{{ order.id }}</span>
        <Link :href="route('orders.index')" class="text-sm text-gray-600 hover:underline">Volver a órdenes</Link>
      </div>
    </template>
    <div class="space-y-6">
      <div class="rounded-lg border border-gray-200 bg-white p-4">
        <p class="text-sm text-gray-500">Mesa: {{ order.table ? `Mesa ${order.table.table_number}` : "—" }}</p>
        <p class="text-sm text-gray-500">Tipo: {{ order.type }} · Pago: {{ order.payment_method }}</p>
        <p class="mt-2 text-lg font-bold">Total: ${{ Number(order.total_amount).toFixed(2) }}</p>
        <p class="text-sm">
          Estado: <span class="font-medium">{{ order.status }}</span>
          · {{ order.paid ? "Pagado" : "Pendiente de pago" }}
        </p>
      </div>
      <div class="rounded-lg border border-gray-200 bg-white p-4">
        <h3 class="mb-3 font-semibold text-gray-800">Detalle</h3>
        <ul class="divide-y divide-gray-100">
          <li
            v-for="d in order.order_details"
            :key="d.id"
            class="flex justify-between py-2 text-sm"
          >
            <span>{{ d.product?.name }} x {{ d.quantity }}</span>
            <span>${{ (Number(d.unit_price) * d.quantity).toFixed(2) }}</span>
          </li>
        </ul>
      </div>
      <div class="flex flex-wrap gap-2">
        <template v-if="order.status === 'pending'">
          <PrimaryButton @click="updateStatus('in_progress')">En progreso</PrimaryButton>
          <PrimaryButton @click="markPaid()">Cobrar / Marcar pagado</PrimaryButton>
        </template>
        <template v-if="order.status === 'in_progress'">
          <PrimaryButton @click="markPaid()">Cobrar / Marcar pagado</PrimaryButton>
        </template>
        <template v-if="!['completed', 'cancelled'].includes(order.status)">
          <DangerButton @click="updateStatus('cancelled')">Cancelar pedido</DangerButton>
        </template>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
