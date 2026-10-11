<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps({
  order: Object,
});

const statusLabels = { pending: "Pendiente", in_progress: "En preparación", completed: "Completado", cancelled: "Cancelado" };
const typeLabels = { dine_in: "En el local", takeaway: "Para llevar", delivery: "Delivery" };
const paymentLabels = { cash: "Efectivo", card: "Tarjeta", transfer: "Transferencia" };

const activo = computed(() => ["pending", "in_progress"].includes(props.order.status));
const metodoPago = ref(props.order.payment_method || "cash");

const agregarUrl = computed(() => {
  const params = new URLSearchParams({ order_id: props.order.id });
  if (props.order.table_id) params.set("table_id", props.order.table_id);
  return `${route("sales.create")}?${params.toString()}`;
});

function updateStatus(newStatus) {
  router.put(route("orders.update", props.order.id), { status: newStatus });
}

function cancelar() {
  if (confirm("¿Cancelar el pedido? La mesa queda libre.")) updateStatus("cancelled");
}

function cobrar() {
  router.post(route("orders.cobrar", props.order.id), { payment_method: metodoPago.value });
}
</script>

<template>
  <Head :title="`Pedido #${order.id}`" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <span>Pedido #{{ order.id }}</span>
        <Link :href="route('orders.index')" class="text-sm text-gray-600 hover:underline">Volver a pedidos</Link>
      </div>
    </template>
    <div class="space-y-6">
      <div class="rounded-lg border border-gray-200 bg-white p-4">
        <p class="text-sm text-gray-500">Mesa: {{ order.table ? `Mesa ${order.table.table_number}` : "—" }}</p>
        <p class="text-sm text-gray-500">
          Tipo: {{ typeLabels[order.type] || order.type }} · Pago: {{ paymentLabels[order.payment_method] || order.payment_method }}
        </p>
        <p class="text-sm text-gray-500">
          Tomado por {{ order.user?.name || "—" }} · {{ new Date(order.created_at).toLocaleString("es") }}
        </p>
        <p class="mt-2 text-lg font-bold">Total: ${{ Number(order.total_amount).toFixed(2) }}</p>
        <p class="text-sm">
          Estado: <span class="font-medium">{{ statusLabels[order.status] || order.status }}</span>
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
            <span>
              {{ d.quantity }} x {{ d.product?.name }}
              <span v-if="d.notes" class="block text-xs text-gray-500">{{ d.notes }}</span>
            </span>
            <span>${{ (Number(d.unit_price) * d.quantity).toFixed(2) }}</span>
          </li>
        </ul>
      </div>
      <div v-if="activo" class="flex flex-wrap items-end gap-3">
        <Link
          :href="agregarUrl"
          class="rounded-md bg-green-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-green-700"
        >
          Agregar ítems
        </Link>
        <PrimaryButton v-if="order.status === 'pending'" @click="updateStatus('in_progress')">En preparación</PrimaryButton>
        <div class="flex items-end gap-2">
          <div>
            <label class="mb-1 block text-xs text-gray-500">Método de pago</label>
            <select v-model="metodoPago" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm">
              <option value="cash">Efectivo</option>
              <option value="card">Tarjeta</option>
              <option value="transfer">Transferencia</option>
            </select>
          </div>
          <PrimaryButton @click="cobrar">Cobrar</PrimaryButton>
        </div>
        <DangerButton @click="cancelar">Cancelar pedido</DangerButton>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
