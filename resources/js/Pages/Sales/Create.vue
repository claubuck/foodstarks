<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { ref, computed } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { ShoppingCartIcon, TrashIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
  productos: Object,
  categorias: Array,
  mesas: Array,
  filters: Object,
  table_id: [String, Number],
  order_id: [String, Number],
  current_order: Object,
});

const buscar = ref(props.filters?.buscar || "");
const filtroCategoria = ref(props.filters?.categoria || "");
const mesaSeleccionada = ref(props.table_id ? String(props.table_id) : "");
const tipoPedido = ref("dine_in");
const metodoPago = ref("cash");

// Ítems nuevos a agregar (los ya cargados en el pedido se muestran aparte y no se reenvían)
const pedido = ref([]);
const enviando = ref(false);

const itemsExistentes = computed(() => props.current_order?.order_details ?? []);
const totalExistente = computed(() => parseFloat(props.current_order?.total_amount) || 0);

// Mesa elegida que ya tiene un pedido abierto: el backend agrega los ítems a ese pedido
const mesaConPedido = computed(() => {
  if (props.order_id || !mesaSeleccionada.value) return false;
  return props.mesas?.find((m) => String(m.id) === String(mesaSeleccionada.value))?.status === "occupied";
});

const cambiarPagina = (pagina) => {
  const params = {
    buscar: buscar.value,
    categoria: filtroCategoria.value,
    page: pagina,
    table_id: props.table_id || undefined,
    order_id: props.order_id || undefined,
  };
  router.get(route("sales.create"), params, { preserveState: true, replace: true });
};

const agregarProducto = (producto) => {
  const existe = pedido.value.find((p) => p.id === producto.id);
  if (existe) {
    existe.cantidad++;
  } else {
    pedido.value.push({
      id: producto.id,
      name: producto.name,
      price: parseFloat(producto.price) || 0,
      cantidad: 1,
    });
  }
};

const quitarProducto = (productoId) => {
  pedido.value = pedido.value.filter((p) => p.id !== productoId);
};

const cambiarCantidad = (item, delta) => {
  item.cantidad = Math.max(1, (parseInt(item.cantidad) || 1) + delta);
};

const totalPedido = computed(() =>
  pedido.value.reduce((acc, item) => acc + (parseFloat(item.price) || 0) * (parseInt(item.cantidad) || 0), 0)
);

const cancelarPedido = () => (pedido.value = []);

const aplicarFiltros = () => {
  const params = {
    buscar: buscar.value,
    categoria: filtroCategoria.value,
    table_id: props.table_id || undefined,
    order_id: props.order_id || undefined,
  };
  router.get(route("sales.create"), params, { preserveState: true, replace: true });
};

const confirmarPedido = () => {
  if (!pedido.value.length || enviando.value) return;
  const items = pedido.value.map((item) => ({
    product_id: item.id,
    quantity: item.cantidad,
  }));
  const payload = {
    items,
    type: tipoPedido.value,
    payment_method: metodoPago.value,
    table_id: mesaSeleccionada.value ? parseInt(mesaSeleccionada.value, 10) : null,
  };
  if (props.order_id) payload.order_id = parseInt(props.order_id, 10);
  router.post(route("orders.store"), payload, {
    onStart: () => (enviando.value = true),
    onFinish: () => (enviando.value = false),
  });
};
</script>

<template>
  <Head title="Nuevo pedido" />
  <AuthenticatedLayout>
    <div class="flex min-h-screen flex-col gap-6 p-6 lg:flex-row">
      <div class="flex-1">
        <h2 class="mb-4 text-2xl font-bold text-gray-800">Menú</h2>
        <div class="mb-4 flex flex-wrap items-center gap-4">
          <input
            v-model="buscar"
            type="text"
            placeholder="Buscar producto..."
            @input="aplicarFiltros"
            class="w-full rounded-lg border border-gray-300 px-4 py-2 shadow-sm sm:w-1/3"
          />
          <select v-model="filtroCategoria" @change="aplicarFiltros">
            <option value="">Todas las categorías</option>
            <option v-for="c in categorias" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
          <div
            v-for="producto in productos.data"
            :key="producto.id"
            class="cursor-pointer rounded-xl bg-white shadow transition hover:shadow-lg"
            @click="agregarProducto(producto)"
          >
            <img
              :src="producto.image_url || '/images/noimage.jfif'"
              :alt="producto.name"
              class="h-32 w-full rounded-t-xl object-cover"
            />
            <div class="p-3">
              <h3 class="truncate text-md font-semibold text-gray-800">{{ producto.name }}</h3>
              <p class="text-sm text-gray-600">$ {{ producto.price }}</p>
            </div>
          </div>
        </div>
        <div class="mt-6 flex items-center justify-center gap-4">
          <button
            v-if="productos.prev_page_url"
            type="button"
            class="rounded bg-gray-200 px-3 py-1 hover:bg-gray-300 disabled:opacity-50"
            @click="cambiarPagina(productos.current_page - 1)"
          >
            Anterior
          </button>
          <span>Página {{ productos.current_page }} de {{ productos.last_page }}</span>
          <button
            v-if="productos.next_page_url"
            type="button"
            class="rounded bg-gray-200 px-3 py-1 hover:bg-gray-300 disabled:opacity-50"
            @click="cambiarPagina(productos.current_page + 1)"
          >
            Siguiente
          </button>
        </div>
      </div>

      <div class="w-full rounded-2xl bg-white p-6 shadow-md lg:w-1/3">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-xl font-bold text-gray-800">Pedido Actual</h2>
          <ShoppingCartIcon class="h-6 w-6 text-gray-600" />
        </div>

        <div v-if="mesas?.length" class="mb-4">
          <label class="mb-1 block text-sm font-medium text-gray-700">Mesa</label>
          <select
            v-model="mesaSeleccionada"
            :disabled="!!order_id"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm disabled:bg-gray-100"
          >
            <option value="">Sin mesa</option>
            <option v-for="m in mesas" :key="m.id" :value="m.id">
              Mesa {{ m.table_number }} ({{ m.capacity }} pers.){{ m.status === "occupied" ? " · ocupada" : m.status === "reserved" ? " · reservada" : "" }}
            </option>
          </select>
          <p v-if="mesaConPedido" class="mt-1 text-xs text-amber-700">
            La mesa tiene un pedido abierto: los productos se agregarán a ese pedido.
          </p>
        </div>

        <div v-if="current_order" class="mb-4 rounded-lg bg-gray-50 p-3">
          <p class="mb-2 text-sm font-semibold text-gray-700">Ya pedido (#{{ current_order.id }})</p>
          <ul class="space-y-1 text-sm text-gray-600">
            <li v-for="d in itemsExistentes" :key="d.id" class="flex justify-between">
              <span>{{ d.quantity }} x {{ d.product?.name }}</span>
              <span>${{ (d.quantity * d.unit_price).toFixed(2) }}</span>
            </li>
          </ul>
          <p class="mt-2 text-right text-sm font-medium text-gray-700">Subtotal: ${{ totalExistente.toFixed(2) }}</p>
        </div>
        <div v-if="!current_order" class="mb-4 flex gap-4">
          <div v-if="!mesaSeleccionada" class="flex-1">
            <label class="mb-1 block text-xs text-gray-500">Tipo</label>
            <select v-model="tipoPedido" class="w-full rounded border border-gray-300 px-2 py-1 text-sm">
              <option value="dine_in">Comer aquí</option>
              <option value="takeaway">Para llevar</option>
              <option value="delivery">Domicilio</option>
            </select>
          </div>
          <div class="flex-1">
            <label class="mb-1 block text-xs text-gray-500">Pago</label>
            <select v-model="metodoPago" class="w-full rounded border border-gray-300 px-2 py-1 text-sm">
              <option value="cash">Efectivo</option>
              <option value="card">Tarjeta</option>
              <option value="transfer">Transferencia</option>
            </select>
          </div>
        </div>

        <div v-if="pedido.length">
          <ul class="mb-4 divide-y divide-gray-200">
            <li v-for="item in pedido" :key="item.id" class="flex items-center justify-between py-2">
              <div>
                <p class="font-medium text-gray-800">{{ item.name }}</p>
                <p class="text-sm text-gray-500">${{ item.price.toFixed(2) }} c/u</p>
              </div>
              <div class="flex items-center gap-2">
                <button type="button" class="h-7 w-7 rounded bg-gray-100 hover:bg-gray-200" @click="cambiarCantidad(item, -1)">−</button>
                <span class="w-6 text-center">{{ item.cantidad }}</span>
                <button type="button" class="h-7 w-7 rounded bg-gray-100 hover:bg-gray-200" @click="cambiarCantidad(item, 1)">+</button>
                <button type="button" class="ml-2 text-red-500 hover:text-red-700" @click="quitarProducto(item.id)">
                  <TrashIcon class="h-5 w-5" />
                </button>
              </div>
            </li>
          </ul>
          <div class="mb-4 text-right">
            <p v-if="current_order" class="text-sm text-gray-600">A agregar: ${{ totalPedido.toFixed(2) }}</p>
            <p class="text-lg font-bold text-gray-800">Total: ${{ (totalExistente + totalPedido).toFixed(2) }}</p>
          </div>
          <div class="flex justify-end gap-4">
            <button
              type="button"
              class="rounded bg-red-100 px-4 py-2 text-red-700 hover:bg-red-200"
              @click="cancelarPedido"
            >
              Cancelar
            </button>
            <button
              type="button"
              class="rounded bg-green-600 px-4 py-2 text-white hover:bg-green-700 disabled:opacity-50"
              :disabled="enviando"
              @click="confirmarPedido"
            >
              {{ order_id || mesaConPedido ? "Agregar al pedido" : "Confirmar pedido" }}
            </button>
          </div>
        </div>
        <p v-else class="italic text-gray-400">
          {{ current_order ? "Tocá productos del menú para agregarlos." : "No hay productos en el pedido." }}
        </p>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
