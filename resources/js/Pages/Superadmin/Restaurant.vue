<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import Nav from './Partials/Nav.vue'
import PaymentBadge from './Partials/PaymentBadge.vue'
import { fecha, fechaHora, hace, pesos, roles } from './Partials/format'

const props = defineProps({
  restaurant: Object,
  branches: Array,
  users: Array,
  subscriptions: Array,
  current_subscription_id: Number,
  usage: Object,
  plans: Array,
})

const current = computed(() => props.subscriptions.find((s) => s.id === props.current_subscription_id))

// Asignar plan (días vacío o 0 = sin vencimiento)
const form = useForm({
  plan_id: current.value?.plan?.id ?? props.plans[0]?.id,
  days: current.value?.plan?.slug === 'free' ? '' : 30,
  paid: false,
  amount_paid: '',
  payment_reference: '',
})
const planElegido = () => props.plans.find((p) => p.id === form.plan_id)

function asignar() {
  const plan = planElegido()
  const vence = Number(form.days) > 0 ? `${form.days} días` : 'sin vencimiento'
  if (!confirm(`¿Asignar el plan ${plan?.name} (${vence}) a ${props.restaurant.name}?`)) return
  form
    .transform((data) => ({ ...data, days: data.days || 0, amount_paid: data.amount_paid || null }))
    .post(route('superadmin.restaurants.plan', props.restaurant.id), { preserveScroll: true })
}

function registrarPago(s, paid) {
  const msg = paid
    ? `¿Registrar el pago de ${pesos(s.plan?.price_monthly)} (${s.plan?.name})?`
    : '¿Anular el pago registrado de este periodo?'
  if (!confirm(msg)) return
  const reference = paid ? prompt('Referencia del pago (opcional, ej. nº de transferencia):') : null
  router.post(
    route('superadmin.subscriptions.payment', s.id),
    { paid, payment_reference: reference || null },
    { preserveScroll: true },
  )
}

const statusLabels = { trial: 'Prueba', active: 'Activa', past_due: 'Vencida', cancelled: 'Reemplazada' }
</script>

<template>
  <Head :title="restaurant.name" />
  <AuthenticatedLayout>
    <template #header>
      <Nav title="Panel de administración" />
    </template>

    <div class="space-y-8 px-4 py-8 md:px-8">
      <Link :href="route('superadmin.dashboard')" class="text-sm text-gray-500 hover:text-gray-800">← Restaurantes</Link>

      <!-- Cabecera -->
      <section class="flex flex-wrap items-start justify-between gap-6 rounded-xl border border-gray-200 bg-white p-6">
        <div class="flex items-center gap-4">
          <img v-if="restaurant.logo_url" :src="restaurant.logo_url" alt="" class="h-14 w-14 rounded-lg object-cover" />
          <div v-else class="flex h-14 w-14 items-center justify-center rounded-lg bg-brand-100 text-xl font-bold text-brand-700">
            {{ restaurant.name.charAt(0).toUpperCase() }}
          </div>
          <div>
            <h3 class="text-xl font-semibold text-gray-900">{{ restaurant.name }}</h3>
            <p class="text-sm text-gray-500">{{ [restaurant.email, restaurant.phone].filter(Boolean).join(' · ') }}</p>
            <p class="text-sm text-gray-500">{{ restaurant.address }}</p>
            <p class="text-xs text-gray-400">Alta {{ fecha(restaurant.created_at) }}</p>
          </div>
        </div>
        <div class="text-right">
          <template v-if="current">
            <p class="text-lg font-semibold text-gray-900">{{ current.plan?.name }}</p>
            <p class="text-sm text-gray-500">{{ current.period_end ? `Vence ${fecha(current.period_end)}` : 'Sin vencimiento' }}</p>
          </template>
          <div class="mt-1"><PaymentBadge :subscription="current" /></div>
        </div>
      </section>

      <!-- Uso -->
      <section class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
        <div v-for="item in [
          { label: 'Pedidos (30 días)', value: usage.orders_30d },
          { label: 'Ventas (30 días)', value: pesos(usage.sales_30d) },
          { label: 'Pedidos totales', value: usage.orders_total },
          { label: 'Último pedido', value: hace(usage.last_order_at) },
          { label: 'Productos', value: usage.products },
          { label: 'Mesas', value: usage.tables },
        ]" :key="item.label" class="rounded-xl border border-gray-200 bg-white p-4">
          <p class="text-xs text-gray-500">{{ item.label }}</p>
          <p class="mt-1 text-xl font-bold text-gray-900">{{ item.value }}</p>
        </div>
      </section>

      <div class="grid gap-8 lg:grid-cols-2">
        <!-- Usuarios -->
        <section>
          <h3 class="mb-3 text-lg font-semibold text-gray-900">Usuarios ({{ users.length }})</h3>
          <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Nombre</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Rol</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Último ingreso</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="u in users" :key="u.id">
                  <td class="px-4 py-2">
                    <p class="font-medium text-gray-900">{{ u.name }}</p>
                    <p class="text-gray-500">{{ u.email }}</p>
                  </td>
                  <td class="px-4 py-2 text-gray-700">{{ roles[u.role] ?? u.role }}</td>
                  <td class="px-4 py-2 text-gray-700">{{ hace(u.last_login) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <h3 class="mb-3 mt-8 text-lg font-semibold text-gray-900">Sucursales ({{ branches.length }})</h3>
          <ul class="divide-y divide-gray-200 rounded-lg border border-gray-200 bg-white text-sm">
            <li v-for="b in branches" :key="b.id" class="flex justify-between px-4 py-2">
              <span class="text-gray-900">{{ b.name }}</span>
              <span :class="b.is_active ? 'text-emerald-700' : 'text-gray-400'">{{ b.is_active ? 'Activa' : 'Inactiva' }}</span>
            </li>
          </ul>
        </section>

        <!-- Asignar plan -->
        <section>
          <h3 class="mb-3 text-lg font-semibold text-gray-900">Asignar plan</h3>
          <form class="space-y-4 rounded-lg border border-gray-200 bg-white p-5" @submit.prevent="asignar">
            <div class="grid grid-cols-2 gap-4">
              <label class="block text-sm">
                <span class="text-gray-700">Plan</span>
                <select v-model="form.plan_id" class="mt-1 w-full rounded border border-gray-300 px-2 py-1.5 text-sm">
                  <option v-for="p in plans" :key="p.id" :value="p.id">{{ p.name }} — {{ pesos(p.price_monthly) }}</option>
                </select>
              </label>
              <label class="block text-sm">
                <span class="text-gray-700">Días de vigencia</span>
                <input
                  v-model="form.days"
                  type="number"
                  min="0"
                  placeholder="Sin vencimiento"
                  class="mt-1 w-full rounded border border-gray-300 px-2 py-1.5 text-sm"
                />
              </label>
            </div>
            <template v-if="Number(planElegido()?.price_monthly) > 0">
              <label class="flex items-center gap-2 text-sm text-gray-700">
                <input v-model="form.paid" type="checkbox" class="rounded border-gray-300 text-brand-600" />
                Ya pagó este periodo
              </label>
              <div v-if="form.paid" class="grid grid-cols-2 gap-4">
                <label class="block text-sm">
                  <span class="text-gray-700">Monto</span>
                  <input
                    v-model="form.amount_paid"
                    type="number"
                    min="0"
                    step="0.01"
                    :placeholder="planElegido()?.price_monthly"
                    class="mt-1 w-full rounded border border-gray-300 px-2 py-1.5 text-sm"
                  />
                </label>
                <label class="block text-sm">
                  <span class="text-gray-700">Referencia</span>
                  <input
                    v-model="form.payment_reference"
                    type="text"
                    placeholder="Nº de transferencia..."
                    class="mt-1 w-full rounded border border-gray-300 px-2 py-1.5 text-sm"
                  />
                </label>
              </div>
            </template>
            <button
              type="submit"
              :disabled="form.processing"
              class="rounded-lg bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 disabled:opacity-50"
            >
              Asignar plan
            </button>
          </form>
        </section>
      </div>

      <!-- Historial -->
      <section>
        <h3 class="mb-3 text-lg font-semibold text-gray-900">Historial de suscripciones y pagos</h3>
        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Plan</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Periodo</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Estado</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Pago</th>
                <th class="px-4 py-2" />
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="s in subscriptions" :key="s.id" :class="s.id === current_subscription_id && 'bg-brand-50/50'">
                <td class="px-4 py-2">
                  <p class="font-medium text-gray-900">{{ s.plan?.name }}</p>
                  <p class="text-gray-500">{{ pesos(s.plan?.price_monthly) }}/mes</p>
                </td>
                <td class="px-4 py-2 text-gray-700">
                  {{ fecha(s.period_start) }} → {{ s.period_end ? fecha(s.period_end) : 'sin vencimiento' }}
                </td>
                <td class="px-4 py-2 text-gray-700">
                  {{ s.id === current_subscription_id ? 'Vigente' : statusLabels[s.status] ?? s.status }}
                </td>
                <td class="px-4 py-2">
                  <PaymentBadge :subscription="s" />
                  <p v-if="s.paid_at" class="mt-1 text-xs text-gray-500">
                    {{ pesos(s.amount_paid) }} · {{ fechaHora(s.paid_at) }}
                    <template v-if="s.payment_reference"> · {{ s.payment_reference }}</template>
                  </p>
                </td>
                <td class="px-4 py-2 text-right">
                  <button
                    v-if="s.payment_status === 'pending'"
                    type="button"
                    class="text-xs font-medium text-brand-700 hover:underline"
                    @click="registrarPago(s, true)"
                  >
                    Marcar pagado
                  </button>
                  <button
                    v-else-if="s.payment_status === 'paid'"
                    type="button"
                    class="text-xs font-medium text-red-600 hover:underline"
                    @click="registrarPago(s, false)"
                  >
                    Anular pago
                  </button>
                </td>
              </tr>
              <tr v-if="!subscriptions.length">
                <td colspan="5" class="px-4 py-6 text-center text-gray-500">Sin suscripciones.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </AuthenticatedLayout>
</template>
