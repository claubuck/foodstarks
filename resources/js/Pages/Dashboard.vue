<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import TableModal from '@/Components/TableModal.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import {
  CurrencyDollarIcon,
  ClipboardDocumentListIcon,
  TableCellsIcon,
  PlusIcon,
} from '@heroicons/vue/24/outline'
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  setup: Object,
  stats: Object,
  mesas: Array,
  pedidos_sin_mesa: Array,
  branch_id: String,
})

let channel = null
let pollTimer = null
const POLL_INTERVAL_MS = 15000 // fallback: refrescar cada 15s si no hay WebSocket

function refreshDashboard() {
  router.reload({ only: ['stats', 'mesas', 'pedidos_sin_mesa'], preserveState: true })
}

onMounted(() => {
  // WebSocket (Reverb): actualización en tiempo real
  if (props.branch_id && window.Echo) {
    try {
      channel = window.Echo.private(`branch.${props.branch_id}`)
        .listen('.order.updated', () => {
          refreshDashboard()
        })
    } catch (e) {
      console.warn('[Dashboard] Echo no pudo suscribirse al canal:', e)
    }
  }

  // Fallback: polling cada 15s para que las mesas se actualicen aunque Reverb falle
  pollTimer = setInterval(refreshDashboard, POLL_INTERVAL_MS)
})

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer)
  if (channel) {
    window.Echo?.leave(`branch.${props.branch_id}`)
  }
})

const statsList = computed(() => [
  {
    title: 'Ventas del Día',
    value: `$${Number(props.stats?.sales_today ?? 0).toFixed(2)}`,
    icon: CurrencyDollarIcon,
    color: 'bg-emerald-100',
    iconColor: 'text-emerald-600',
  },
  {
    title: 'Mesas Ocupadas',
    value: `${props.stats?.tables_occupied ?? 0} / ${props.stats?.tables_total ?? 0}`,
    icon: TableCellsIcon,
    color: 'bg-orange-100',
    iconColor: 'text-orange-600',
  },
  {
    title: 'Pedidos Activos',
    value: String(props.stats?.orders_active ?? 0),
    icon: ClipboardDocumentListIcon,
    color: 'bg-indigo-100',
    iconColor: 'text-indigo-600',
  },
])

const mesas = computed(() => props.mesas ?? [])
const pedidosSinMesa = computed(() => props.pedidos_sin_mesa ?? [])
const showModal = ref(false)
const mesaSeleccionada = ref(null)
const pedidoSeleccionado = ref(null)

function abrirModal(mesa) {
  mesaSeleccionada.value = mesa
  pedidoSeleccionado.value = null
  showModal.value = true
}

function abrirPedido(pedido) {
  mesaSeleccionada.value = null
  pedidoSeleccionado.value = pedido
  showModal.value = true
}

function cerrarModal() {
  showModal.value = false
  mesaSeleccionada.value = null
  pedidoSeleccionado.value = null
}
</script>

<template>
  <Head title="Dashboard Restaurante" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2>Panel del Restaurante</h2>
        <div class="flex gap-2">
        <Link
          :href="route('sales.create')"
          class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-medium shadow-sm transition bg-primary-600 text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
        >
          <PlusIcon class="h-4 w-4" /> Nuevo pedido
        </Link>
        <Link
          :href="route('tables.index')"
          class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-medium shadow-sm transition border border-gray-300 bg-white text-gray-700 hover:bg-gray-50"
        >
          Mesas
        </Link>
        </div>
      </div>
    </template>

    <div class="min-h-screen px-4 py-8 md:px-8">
      <div
        v-if="setup && !(setup.categories && setup.products && setup.tables)"
        class="mb-8 rounded-2xl border border-blue-200 bg-blue-50 p-6"
      >
        <h3 class="mb-3 text-lg font-semibold text-blue-900">Primeros pasos</h3>
        <ol class="space-y-2 text-sm text-blue-900">
          <li>
            <span>{{ setup.categories ? '✅' : '⬜' }}</span>
            Crear las categorías del menú
            <Link v-if="!setup.categories" :href="route('product-categories.index')" class="ml-2 font-medium underline">Ir a categorías</Link>
          </li>
          <li>
            <span>{{ setup.products ? '✅' : '⬜' }}</span>
            Cargar los productos con su precio
            <Link v-if="!setup.products" :href="route('products.index')" class="ml-2 font-medium underline">Ir a productos</Link>
          </li>
          <li>
            <span>{{ setup.tables ? '✅' : '⬜' }}</span>
            Crear las mesas del salón
            <Link v-if="!setup.tables" :href="route('tables.create')" class="ml-2 font-medium underline">Crear mesa</Link>
          </li>
        </ol>
        <p class="mt-3 text-xs text-blue-800">Las categorías las administran el dueño y los encargados.</p>
      </div>

      <div class="mb-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="stat in statsList"
          :key="stat.title"
          class="flex items-center justify-between rounded-2xl bg-white p-6 shadow-md"
        >
          <div>
            <p class="mb-1 text-sm text-gray-500">{{ stat.title }}</p>
            <p class="text-2xl font-bold text-gray-800">{{ stat.value }}</p>
          </div>
          <div :class="['rounded-full p-2', stat.color]">
            <component :is="stat.icon" class="h-8 w-8" :class="stat.iconColor" />
          </div>
        </div>
      </div>

      <div class="rounded-2xl bg-white p-6 shadow-md">
        <h3 class="mb-6 text-xl font-semibold text-gray-800">Estado de las Mesas</h3>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
          <div
            v-for="mesa in mesas"
            :key="mesa.id"
            class="cursor-pointer rounded-xl border p-4 shadow-md transition hover:shadow-lg"
            :class="mesa.status === 'occupied' ? 'border-red-300 bg-red-50' : 'border-green-300 bg-green-50'"
            @click="abrirModal(mesa)"
          >
            <div class="mb-2 flex items-center justify-between">
              <h4 class="text-lg font-bold">Mesa {{ mesa.table_number }}</h4>
              <span
                class="rounded-full px-2 py-1 text-xs font-semibold"
                :class="mesa.status === 'occupied' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'"
              >
                {{ mesa.status === 'occupied' ? 'Ocupada' : 'Disponible' }}
              </span>
            </div>
            <div v-if="mesa.current_order" class="space-y-1 text-sm text-gray-700">
              <p class="mb-1 font-semibold">Pedido:</p>
              <ul class="ml-4 list-disc">
                <li v-for="(item, i) in mesa.current_order.items" :key="i">
                  {{ item.quantity }} x {{ item.product_name }}
                </li>
              </ul>
              <p class="mt-1 font-medium">Total: ${{ Number(mesa.current_order.total_amount).toFixed(2) }}</p>
            </div>
            <div v-else class="italic text-gray-500 text-sm">Disponible</div>
          </div>
        </div>
      </div>

      <div v-if="pedidosSinMesa.length > 0" class="mt-8 rounded-2xl bg-white p-6 shadow-md">
        <h3 class="mb-6 text-xl font-semibold text-gray-800">Pedidos sin mesa (para llevar / delivery)</h3>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="pedido in pedidosSinMesa"
            :key="pedido.id"
            class="cursor-pointer rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm transition hover:shadow-md"
            @click="abrirPedido(pedido)"
          >
            <div class="mb-2 flex items-center justify-between">
              <span class="font-semibold text-gray-800">Pedido #{{ pedido.id }}</span>
              <span class="rounded-full bg-amber-200 px-2 py-0.5 text-xs font-medium text-amber-800">
                {{ pedido.type === 'delivery' ? 'Delivery' : 'Para llevar' }}
              </span>
            </div>
            <ul class="ml-4 list-disc text-sm text-gray-700">
              <li v-for="(item, i) in pedido.items" :key="i">
                {{ item.quantity }} x {{ item.product_name }}
              </li>
            </ul>
            <p class="mt-2 font-medium text-gray-800">Total: ${{ Number(pedido.total_amount).toFixed(2) }}</p>
          </div>
        </div>
      </div>

      <TableModal :show="showModal" :mesa="mesaSeleccionada" :pedido="pedidoSeleccionado" @close="cerrarModal" />
    </div>
  </AuthenticatedLayout>
</template>
