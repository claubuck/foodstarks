<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import Nav from './Partials/Nav.vue'
import PaymentBadge from './Partials/PaymentBadge.vue'
import { fecha, hace, roles } from './Partials/format'

const props = defineProps({
  users: Object,
  filters: Object,
  stats: Object,
})

const search = ref(props.filters?.search || '')
const role = ref(props.filters?.role || '')

function filtrar() {
  router.get(
    route('superadmin.users'),
    { search: search.value || undefined, role: role.value || undefined },
    { preserveState: true, replace: true },
  )
}

let searchTimer = null
watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(filtrar, 300)
})
watch(role, filtrar)
</script>

<template>
  <Head title="Usuarios" />
  <AuthenticatedLayout>
    <template #header>
      <Nav title="Panel de administración" />
    </template>

    <div class="space-y-8 px-4 py-8 md:px-8">
      <section class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-5">
          <p class="text-sm text-gray-500">Usuarios registrados</p>
          <p class="mt-1 text-3xl font-bold text-gray-900">{{ stats.total }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5">
          <p class="text-sm text-gray-500">Activos (ingresaron en 7 días)</p>
          <p class="mt-1 text-3xl font-bold text-gray-900">{{ stats.active_7d }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5">
          <p class="text-sm text-gray-500">Nuevos (30 días)</p>
          <p class="mt-1 text-3xl font-bold text-gray-900">{{ stats.new_30d }}</p>
        </div>
      </section>

      <section>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
          <h3 class="text-lg font-semibold text-gray-900">Usuarios ({{ users.total }})</h3>
          <div class="flex w-full flex-wrap gap-2 sm:w-auto">
            <select v-model="role" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
              <option value="">Todos los roles</option>
              <option v-for="(label, value) in roles" :key="value" :value="value">{{ label }}</option>
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
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Usuario</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Rol</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Restaurante</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Plan</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Pago</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Último ingreso</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Alta</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
              <tr v-for="u in users.data" :key="u.id">
                <td class="px-4 py-3">
                  <p class="font-medium text-gray-900">{{ u.name }}</p>
                  <p class="text-gray-500">{{ u.email }}</p>
                  <p v-if="!u.email_verified_at" class="text-xs text-amber-700">Email sin verificar</p>
                </td>
                <td class="px-4 py-3 text-gray-700">{{ roles[u.role] ?? u.role }}</td>
                <td class="px-4 py-3">
                  <Link
                    v-if="u.restaurant"
                    :href="route('superadmin.restaurants.show', u.restaurant.id)"
                    class="text-gray-900 hover:text-brand-700"
                  >
                    {{ u.restaurant.name }}
                  </Link>
                  <span v-else class="text-gray-400">—</span>
                </td>
                <td class="px-4 py-3 text-gray-700">
                  {{ u.subscription?.plan?.name ?? '—' }}
                  <p v-if="u.subscription?.period_end" class="text-xs text-gray-500">Vence {{ fecha(u.subscription.period_end) }}</p>
                </td>
                <td class="px-4 py-3"><PaymentBadge :subscription="u.subscription" /></td>
                <td class="px-4 py-3 text-gray-700">{{ hace(u.last_login) }}</td>
                <td class="px-4 py-3 text-gray-700">{{ fecha(u.created_at) }}</td>
              </tr>
              <tr v-if="!users.data.length">
                <td colspan="7" class="px-4 py-6 text-center text-gray-500">No hay usuarios.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="users.last_page > 1" class="mt-4 flex flex-wrap gap-1">
          <Link
            v-for="link in users.links"
            :key="link.label"
            :href="link.url || ''"
            :class="['rounded px-3 py-1 text-sm', link.active ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-700', !link.url && 'pointer-events-none opacity-50']"
            v-html="link.label"
            preserve-state
          />
        </div>
      </section>
    </div>
  </AuthenticatedLayout>
</template>
