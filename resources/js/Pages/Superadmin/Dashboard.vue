<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { CpuChipIcon, SignalIcon } from '@heroicons/vue/24/outline'
import { ref, watch } from 'vue'
import Nav from './Partials/Nav.vue'
import PaymentBadge from './Partials/PaymentBadge.vue'
import { fecha, hace, pesos } from './Partials/format'

const props = defineProps({
  stats: Object,
  restaurants: Object,
  plans: Array,
  filters: Object,
  horizon_url: String,
})

const search = ref(props.filters?.search || '')
const status = ref(props.filters?.status || '')

function filtrar() {
  router.get(
    route('superadmin.dashboard'),
    { search: search.value || undefined, status: status.value || undefined },
    { preserveState: true, replace: true },
  )
}

let searchTimer = null
watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(filtrar, 300)
})
watch(status, filtrar)

const statusFilters = [
  { value: '', label: 'Todos' },
  { value: 'pending', label: 'Pago pendiente' },
  { value: 'paid', label: 'Pagados' },
  { value: 'free', label: 'Gratis' },
  { value: 'expiring', label: 'Vencen en 7 días' },
  { value: 'no_plan', label: 'Sin plan / vencidos' },
]

function marcarPagado(r) {
  const plan = r.subscription.plan
  if (!confirm(`¿Registrar el pago de ${pesos(plan.price_monthly)} (${plan.name}) de ${r.name}?`)) return
  router.post(route('superadmin.subscriptions.payment', r.subscription.id), { paid: true }, { preserveScroll: true })
}
</script>

<template>
  <Head title="Panel de administración" />
  <AuthenticatedLayout>
    <template #header>
      <Nav title="Panel de administración" />
    </template>

    <div class="space-y-10 px-4 py-8 md:px-8">
      <!-- Métricas -->
      <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-5">
          <p class="text-sm text-gray-500">Restaurantes</p>
          <p class="mt-1 text-3xl font-bold text-gray-900">{{ stats.restaurants }}</p>
          <p class="mt-1 text-xs text-gray-500">+{{ stats.restaurants_new_30d }} en los últimos 30 días</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5">
          <p class="text-sm text-gray-500">Usuarios</p>
          <p class="mt-1 text-3xl font-bold text-gray-900">{{ stats.users }}</p>
          <p class="mt-1 text-xs text-gray-500">{{ stats.users_active_7d }} ingresaron en los últimos 7 días</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5">
          <p class="text-sm text-gray-500">Ingreso mensual estimado</p>
          <p class="mt-1 text-3xl font-bold text-gray-900">{{ pesos(stats.mrr) }}</p>
          <p class="mt-1 text-xs text-gray-500">Suma de planes pagos vigentes</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5">
          <p class="text-sm text-gray-500">Cobrado este mes</p>
          <p class="mt-1 text-3xl font-bold text-emerald-700">{{ pesos(stats.collected_month) }}</p>
          <p class="mt-1 text-xs text-gray-500">{{ stats.paying }} restaurantes al día</p>
        </div>

        <button
          type="button"
          class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-left transition hover:border-amber-300"
          @click="status = 'pending'"
        >
          <p class="text-sm text-amber-800">Pagos pendientes</p>
          <p class="mt-1 text-3xl font-bold text-amber-900">{{ stats.pending_payment }}</p>
          <p class="mt-1 text-xs text-amber-700">Planes pagos sin cobro registrado</p>
        </button>
        <button
          type="button"
          class="rounded-xl border border-orange-200 bg-orange-50 p-5 text-left transition hover:border-orange-300"
          @click="status = 'expiring'"
        >
          <p class="text-sm text-orange-800">Vencen en 7 días</p>
          <p class="mt-1 text-3xl font-bold text-orange-900">{{ stats.expiring_7d }}</p>
          <p class="mt-1 text-xs text-orange-700">Hay que renovar o avisar</p>
        </button>
        <button
          type="button"
          class="rounded-xl border border-red-200 bg-red-50 p-5 text-left transition hover:border-red-300"
          @click="status = 'no_plan'"
        >
          <p class="text-sm text-red-800">Sin plan / vencidos</p>
          <p class="mt-1 text-3xl font-bold text-red-900">{{ stats.no_plan }}</p>
          <p class="mt-1 text-xs text-red-700">No pueden usar el panel</p>
        </button>
        <div class="rounded-xl border border-gray-200 bg-white p-5">
          <p class="text-sm text-gray-500">Restaurantes por plan</p>
          <ul class="mt-2 space-y-1 text-sm">
            <li v-for="(count, plan) in stats.by_plan" :key="plan" class="flex justify-between">
              <span class="text-gray-700">{{ plan }}</span>
              <span class="font-semibold text-gray-900">{{ count }}</span>
            </li>
            <li v-if="!Object.keys(stats.by_plan).length" class="text-gray-400">—</li>
          </ul>
          <p class="mt-2 text-xs text-gray-500">{{ stats.orders_today }} pedidos hoy en la plataforma</p>
        </div>
      </section>

      <!-- Restaurantes -->
      <section>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
          <h3 class="text-lg font-semibold text-gray-900">Restaurantes ({{ restaurants.total }})</h3>
          <div class="flex w-full flex-wrap gap-2 sm:w-auto">
            <select v-model="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
              <option v-for="f in statusFilters" :key="f.value" :value="f.value">{{ f.label }}</option>
            </select>
            <input
              v-model="search"
              type="search"
              placeholder="Buscar por nombre o email..."
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm sm:w-72"
            />
          </div>
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-200">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Restaurante</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Dueño</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Plan</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Pago</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Uso</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Actividad</th>
                <th class="px-4 py-2" />
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
              <tr v-for="r in restaurants.data" :key="r.id" class="align-top">
                <td class="px-4 py-3 text-sm">
                  <Link :href="route('superadmin.restaurants.show', r.id)" class="font-medium text-gray-900 hover:text-brand-700">
                    {{ r.name }}
                  </Link>
                  <p class="text-gray-500">{{ r.email }}</p>
                  <p class="text-xs text-gray-400">Alta {{ fecha(r.created_at) }}</p>
                </td>
                <td class="px-4 py-3 text-sm text-gray-700">
                  <p>{{ r.owner?.name || '—' }}</p>
                  <p class="text-gray-500">{{ r.owner?.email }}</p>
                </td>
                <td class="px-4 py-3 text-sm">
                  <template v-if="r.subscription">
                    <p class="font-medium text-gray-900">{{ r.subscription.plan?.name }}</p>
                    <p class="text-gray-500">
                      {{ r.subscription.period_end ? `Vence ${fecha(r.subscription.period_end)}` : 'Sin vencimiento' }}
                    </p>
                  </template>
                  <span v-else class="text-gray-400">—</span>
                </td>
                <td class="px-4 py-3 text-sm">
                  <PaymentBadge :subscription="r.subscription" />
                  <button
                    v-if="r.subscription?.payment_status === 'pending'"
                    type="button"
                    class="mt-1 block text-xs font-medium text-brand-700 hover:underline"
                    @click="marcarPagado(r)"
                  >
                    Marcar pagado
                  </button>
                  <p v-else-if="r.subscription?.amount_paid" class="mt-1 text-xs text-gray-500">
                    {{ pesos(r.subscription.amount_paid) }}
                  </p>
                </td>
                <td class="px-4 py-3 text-sm text-gray-700">
                  <p>{{ r.branches_count }} suc. · {{ r.users_count }} usuarios</p>
                  <p class="text-gray-500">{{ r.orders_30d_count }} pedidos (30 días)</p>
                </td>
                <td class="px-4 py-3 text-sm text-gray-700">
                  <p>Ingreso: {{ hace(r.last_login) }}</p>
                  <p class="text-gray-500">Último pedido: {{ hace(r.last_order_at) }}</p>
                </td>
                <td class="px-4 py-3 text-right text-sm">
                  <Link :href="route('superadmin.restaurants.show', r.id)" class="font-medium text-brand-700 hover:underline">
                    Ver
                  </Link>
                </td>
              </tr>
              <tr v-if="!restaurants.data.length">
                <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">No hay restaurantes.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="restaurants.last_page > 1" class="mt-4 flex flex-wrap gap-1">
          <Link
            v-for="link in restaurants.links"
            :key="link.label"
            :href="link.url || ''"
            :class="['rounded px-3 py-1 text-sm', link.active ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-700', !link.url && 'pointer-events-none opacity-50']"
            v-html="link.label"
            preserve-state
          />
        </div>
      </section>

      <section>
        <h3 class="mb-4 text-lg font-semibold text-gray-900">Sistema</h3>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
          <Link
            :href="route('realtime.test')"
            class="flex flex-col items-start rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-md transition hover:border-amber-300 hover:shadow-lg"
          >
            <div class="mb-4 rounded-full bg-amber-100 p-3">
              <SignalIcon class="h-8 w-8 text-amber-600" />
            </div>
            <h3 class="mb-2 text-lg font-semibold text-gray-900">Prueba tiempo real</h3>
            <p class="text-sm text-gray-600">
              Estado de Reverb (WebSocket), suscripción al canal y evento de prueba.
            </p>
          </Link>

          <a
            :href="horizon_url"
            target="_blank"
            rel="noopener noreferrer"
            class="flex flex-col items-start rounded-2xl border border-indigo-200 bg-indigo-50 p-6 shadow-md transition hover:border-indigo-300 hover:shadow-lg"
          >
            <div class="mb-4 rounded-full bg-indigo-100 p-3">
              <CpuChipIcon class="h-8 w-8 text-indigo-600" />
            </div>
            <h3 class="mb-2 text-lg font-semibold text-gray-900">Horizon</h3>
            <p class="text-sm text-gray-600">
              Colas en Redis: jobs, workers y fallos. Diagnóstico JSON en
              <code>/superadmin/queue-diagnostic</code>.
            </p>
          </a>
        </div>
      </section>
    </div>
  </AuthenticatedLayout>
</template>
