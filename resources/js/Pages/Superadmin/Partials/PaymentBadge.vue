<script setup>
import { computed } from 'vue'
import { fecha } from './format'

const props = defineProps({ subscription: Object })

const badge = computed(() => {
  const s = props.subscription
  if (!s) return { label: 'Sin plan', class: 'bg-red-100 text-red-700' }
  return {
    free: { label: 'Gratis', class: 'bg-gray-100 text-gray-600' },
    paid: { label: 'Pagado', class: 'bg-emerald-100 text-emerald-700' },
    pending: { label: 'Pago pendiente', class: 'bg-amber-100 text-amber-800' },
  }[s.payment_status]
})
</script>

<template>
  <span
    :class="['inline-flex rounded-full px-2 py-0.5 text-xs font-medium', badge.class]"
    :title="subscription?.paid_at ? `Pagado el ${fecha(subscription.paid_at)}` : undefined"
  >
    {{ badge.label }}
  </span>
</template>
