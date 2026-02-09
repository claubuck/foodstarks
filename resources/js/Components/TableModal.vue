<template>
  <div
    v-if="show"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
  >
    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-lg">
      <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-bold text-gray-800">Mesa {{ mesa?.table_number }}</h2>
        <button type="button" class="text-xl font-bold text-gray-500 hover:text-red-600" @click="close">×</button>
      </div>

      <div v-if="mesa?.current_order?.items?.length" class="mb-4">
        <p class="mb-2 font-semibold text-gray-700">Pedido actual:</p>
        <ul class="list-inside list-disc space-y-1 text-sm">
          <li v-for="(item, i) in mesa.current_order.items" :key="i">
            {{ item.quantity }} x {{ item.product_name }}
          </li>
        </ul>
        <p class="mt-2 font-medium">Total: ${{ Number(mesa.current_order.total_amount).toFixed(2) }}</p>
      </div>
      <div v-else class="mb-4 italic text-sm text-gray-500">Sin pedido.</div>

      <div class="flex justify-end gap-3">
        <Link
          :href="agregarUrl"
          class="rounded bg-green-600 px-4 py-2 text-white hover:bg-green-700"
        >
          Agregar
        </Link>
        <button
          v-if="mesa?.current_order"
          type="button"
          class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
          @click="cobrar"
        >
          Cobrar
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
  show: Boolean,
  mesa: Object,
})

const emit = defineEmits(['close'])
const close = () => emit('close')

const agregarUrl = computed(() => {
  if (!props.mesa) return route('sales.create')
  const params = new URLSearchParams({ table_id: props.mesa.id })
  if (props.mesa.current_order?.id) params.set('order_id', props.mesa.current_order.id)
  return `${route('sales.create')}?${params.toString()}`
})

function cobrar() {
  if (props.mesa?.current_order?.id) {
    router.post(route('orders.cobrar', props.mesa.current_order.id))
    close()
  }
}
</script>
