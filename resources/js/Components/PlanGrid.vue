<script setup>
import { usePage } from '@inertiajs/vue3'

// Mientras no haya cobro online, contratar o cambiar de plan se pide por email al soporte
// y el operador lo asigna desde /superadmin.
const props = defineProps({
  plans: Array,
  currentPlanId: [Number, String],
  supportEmail: String,
})

const page = usePage()

function solicitarUrl(plan) {
  const restaurant = page.props.auth.restaurant?.name ?? ''
  const subject = `Solicitud de plan ${plan.name} - ${restaurant}`
  const body = `Hola, quiero contratar el plan ${plan.name} para el restaurante "${restaurant}" (cuenta ${page.props.auth.user?.email}).`
  return `mailto:${props.supportEmail}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`
}
</script>

<template>
  <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
    <div
      v-for="plan in plans"
      :key="plan.id"
      class="flex flex-col rounded-xl border bg-white p-6 shadow-sm"
      :class="plan.id === currentPlanId ? 'border-green-400 ring-2 ring-green-200' : 'border-gray-200'"
    >
      <h3 class="text-lg font-semibold text-gray-900">{{ plan.name }}</h3>
      <p class="mt-2 text-2xl font-bold text-gray-900">
        <template v-if="Number(plan.price_monthly) > 0">
          ${{ Number(plan.price_monthly).toFixed(2) }}
          <span class="text-sm font-normal text-gray-500">/mes</span>
        </template>
        <template v-else>Gratis</template>
      </p>
      <ul class="mt-4 flex-1 space-y-2 text-sm text-gray-600">
        <li v-for="(f, i) in (plan.features || [])" :key="i">{{ f }}</li>
      </ul>
      <span v-if="plan.id === currentPlanId" class="mt-6 block text-center text-sm font-medium text-green-700">Tu plan actual</span>
      <a
        v-else-if="supportEmail"
        :href="solicitarUrl(plan)"
        class="mt-6 block rounded-md bg-gray-800 px-4 py-2 text-center text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700"
      >
        Solicitar este plan
      </a>
    </div>
  </div>
</template>
