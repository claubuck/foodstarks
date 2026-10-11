<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { CpuChipIcon, SignalIcon } from '@heroicons/vue/24/outline'
import { reactive, ref, watch } from 'vue'

const props = defineProps({
  restaurants: Object,
  plans: Array,
  filters: Object,
  horizon_url: String,
})

const search = ref(props.filters?.search || '')
let searchTimer = null
watch(search, (value) => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    router.get(route('superadmin.dashboard'), { search: value || undefined }, { preserveState: true, replace: true })
  }, 300)
})

// Plan y días elegidos por fila (días vacío o 0 = sin vencimiento)
const seleccion = reactive({})
watch(
  () => props.restaurants.data,
  (rows) => {
    for (const r of rows) {
      seleccion[r.id] ??= { plan_id: r.plan?.id ?? props.plans[0]?.id, days: r.plan?.slug === 'free' ? '' : 30 }
    }
  },
  { immediate: true },
)
const fila = (r) => seleccion[r.id]

function asignar(r) {
  const { plan_id, days } = fila(r)
  const plan = props.plans.find((p) => p.id === plan_id)
  const vence = Number(days) > 0 ? `${days} días` : 'sin vencimiento'
  if (!confirm(`¿Asignar el plan ${plan?.name} (${vence}) a ${r.name}?`)) return
  router.post(route('superadmin.restaurants.plan', r.id), { plan_id, days: days || 0 }, { preserveScroll: true })
}
</script>

<template>
  <Head title="Panel de administración" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900">Panel de administración</h2>
        <Link
          :href="route('dashboard')"
          class="rounded bg-gray-200 px-4 py-2 text-gray-800 hover:bg-gray-300"
        >
          Volver al Dashboard
        </Link>
      </div>
    </template>

    <div class="space-y-10 px-4 py-8 md:px-8">
      <section>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
          <h3 class="text-lg font-semibold text-gray-900">Restaurantes ({{ restaurants.total }})</h3>
          <input
            v-model="search"
            type="search"
            placeholder="Buscar por nombre o email..."
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm sm:w-72"
          />
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-200">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Restaurante</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Dueño</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Uso</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Plan actual</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Asignar plan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
              <tr v-for="r in restaurants.data" :key="r.id" class="align-top">
                <td class="px-4 py-3 text-sm">
                  <p class="font-medium text-gray-900">{{ r.name }}</p>
                  <p class="text-gray-500">{{ r.email }}</p>
                  <p class="text-xs text-gray-400">Alta {{ new Date(r.created_at).toLocaleDateString('es') }}</p>
                </td>
                <td class="px-4 py-3 text-sm text-gray-700">
                  <p>{{ r.owner?.name || '—' }}</p>
                  <p class="text-gray-500">{{ r.owner?.email }}</p>
                </td>
                <td class="px-4 py-3 text-sm text-gray-700">
                  {{ r.branches_count }} suc. · {{ r.users_count }} usuarios
                </td>
                <td class="px-4 py-3 text-sm">
                  <template v-if="r.plan">
                    <p class="font-medium text-gray-900">{{ r.plan.name }}</p>
                    <p class="text-gray-500">
                      {{ r.period_end ? `Vence ${new Date(r.period_end).toLocaleDateString('es')}` : 'Sin vencimiento' }}
                    </p>
                  </template>
                  <span v-else class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">Sin plan activo</span>
                </td>
                <td class="px-4 py-3 text-sm">
                  <div class="flex flex-wrap items-center gap-2">
                    <select v-model="fila(r).plan_id" class="rounded border border-gray-300 px-2 py-1 text-sm">
                      <option v-for="p in plans" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                    <input
                      v-model="fila(r).days"
                      type="number"
                      min="0"
                      placeholder="días"
                      title="Días de vigencia (vacío = sin vencimiento)"
                      class="w-20 rounded border border-gray-300 px-2 py-1 text-sm"
                    />
                    <button type="button" class="rounded bg-gray-800 px-3 py-1 text-white hover:bg-gray-700" @click="asignar(r)">
                      Asignar
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!restaurants.data.length">
                <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">No hay restaurantes.</td>
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
