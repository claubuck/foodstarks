<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { ref, computed, onMounted } from "vue";
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

const pedido = ref([]);

onMounted(() => {
  if (props.current_order?.order_details?.length) {
    pedido.value = props.current_order.order_details.map((d) => ({
      id: d.product_id,
      name: d.product?.name,
      price: parseFloat(d.unit_price) || 0,
      cantidad: d.quantity,
    }));
  }
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
  const items = pedido.value.map((item) => ({
    product_id: item.id,
    quantity: item.cantidad,
    price: item.price,
  }));
  const payload = {
    items,
    type: tipoPedido.value,
    payment_method: metodoPago.value,
    table_id: mesaSeleccionada.value ? parseInt(mesaSeleccionada.value, 10) : null,
  };
  if (props.order_id) payload.order_id = parseInt(props.order_id, 10);
  router.post(route("orders.store"), payload);
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
              :src="producto.image_url || '/images/noimage.jpg'"
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
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
          >
            <option value="">Sin mesa</option>
            <option v-for="m in mesas" :key="m.id" :value="m.id">
              Mesa {{ m.table_number }} ({{ m.capacity }} pers.)
            </option>
          </select>
        </div>
        <div class="mb-4 flex gap-4">
          <div class="flex-1">
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
                <p class="text-sm text-gray-500">{{ item.cantidad }} x ${{ item.price }}</p>
              </div>
              <button type="button" class="text-red-500 hover:text-red-700" @click="quitarProducto(item.id)">
                <TrashIcon class="h-5 w-5" />
              </button>
            </li>
          </ul>
          <div class="mb-4 text-right">
            <p class="text-lg font-bold text-gray-800">Total: ${{ totalPedido.toFixed(2) }}</p>
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
              class="rounded bg-green-600 px-4 py-2 text-white hover:bg-green-700"
              @click="confirmarPedido"
            >
              {{ order_id ? "Agregar al pedido" : "Confirmar pedido" }}
            </button>
          </div>
        </div>
        <p v-else class="italic text-gray-400">No hay productos en el pedido.</p>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
