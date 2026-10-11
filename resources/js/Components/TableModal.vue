<template>
  <div
    v-if="show"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
    @click.self="close"
  >
    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-lg">
      <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-bold text-gray-800">
          {{ mesa?.table_number ? `Mesa ${mesa.table_number}` : `Pedido #${order?.id}` }}
        </h2>
        <button type="button" class="text-xl font-bold text-gray-500 hover:text-red-600" @click="close">×</button>
      </div>

      <div v-if="order?.items?.length" class="mb-4">
        <p class="mb-2 font-semibold text-gray-700">Pedido actual:</p>
        <ul class="list-inside list-disc space-y-1 text-sm">
          <li v-for="(item, i) in order.items" :key="i">
            {{ item.quantity }} x {{ item.product_name }}
          </li>
        </ul>
        <p class="mt-2 font-medium">Total: ${{ Number(order.total_amount).toFixed(2) }}</p>

        <label class="mt-4 mb-1 block text-sm font-medium text-gray-700">Método de pago</label>
        <select v-model="metodoPago" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
          <option value="cash">Efectivo</option>
          <option value="card">Tarjeta</option>
          <option value="transfer">Transferencia</option>
        </select>
      </div>
      <div v-else class="mb-4 italic text-sm text-gray-500">Sin pedido.</div>

      <div class="flex flex-wrap justify-end gap-3">
        <Link
          v-if="order"
          :href="route('orders.show', order.id)"
          class="rounded bg-gray-100 px-4 py-2 text-gray-700 hover:bg-gray-200"
        >
          Ver detalle
        </Link>
        <Link
          :href="agregarUrl"
          class="rounded bg-green-600 px-4 py-2 text-white hover:bg-green-700"
        >
          {{ order ? 'Agregar' : 'Tomar pedido' }}
        </Link>
        <button
          v-if="order"
          type="button"
          class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:opacity-50"
          :disabled="cobrando"
          @click="cobrar"
        >
          Cobrar
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'

// Sirve para una mesa (mesa.current_order) o para un pedido sin mesa (pedido).
const props = defineProps({
  show: Boolean,
  mesa: Object,
  pedido: Object,
})

const emit = defineEmits(['close'])
const close = () => emit('close')

const order = computed(() => props.mesa?.current_order ?? props.pedido ?? null)
const metodoPago = ref('cash')
const cobrando = ref(false)

watch(order, (o) => {
  metodoPago.value = o?.payment_method || 'cash'
})

const agregarUrl = computed(() => {
  const params = new URLSearchParams()
  if (props.mesa?.id) params.set('table_id', props.mesa.id)
  if (order.value?.id) params.set('order_id', order.value.id)
  const qs = params.toString()
  return qs ? `${route('sales.create')}?${qs}` : route('sales.create')
})

function cobrar() {
  if (!order.value?.id) return
  router.post(route('orders.cobrar', order.value.id), { payment_method: metodoPago.value }, {
    onStart: () => (cobrando.value = true),
    onFinish: () => (cobrando.value = false),
    onSuccess: close,
  })
}
</script>
