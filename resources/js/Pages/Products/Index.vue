<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { ref, watch } from "vue";
import { router, usePage, Link } from "@inertiajs/vue3";
import { Head } from "@inertiajs/vue3";
import ProductFormModal from "@/Pages/Products/Partials/ProductFormModal.vue";
import { PencilIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
  products: Object,
  filters: Object,
  categories: Array,
  editingProduct: Object,
});

const search = ref(props.filters?.search || "");
const showModal = ref(!!props.editingProduct);
const editingProduct = ref(props.editingProduct ?? null);

const openEdit = (product) => {
  editingProduct.value = product;
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  editingProduct.value = null;
};

watch(search, (value) => {
  router.get(
    route("products.index"),
    { search: value },
    { preserveState: true, replace: true }
  );
});

watch(
  () => props.editingProduct,
  (val) => {
    editingProduct.value = val ?? null;
    showModal.value = !!val;
  },
  { immediate: true }
);
</script>

<template>
  <Head title="Productos" />
  <AuthenticatedLayout>
    <div class="min-h-screen py-10 px-6">
      <div class="max-w-7xl mx-auto">
        <!-- Header y botón -->
        <div class="flex justify-between items-center mb-6">
          <input
            v-model="search"
            type="text"
            placeholder="Buscar producto..."
            class="w-full md:w-1/3 px-4 py-2 border rounded shadow-sm focus:outline-none focus:ring focus:border-blue-300"
          />
          <button
            @click="showModal = true"
            class="ml-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
          >
            + Nuevo Producto
          </button>
        </div>

        <!-- Grid de productos -->
        <div
          class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6"
        >
          <div
            v-for="product in products.data"
            :key="product.id"
            class="bg-white rounded-lg shadow hover:shadow-md transition overflow-hidden relative"
          >
            <div class="relative">
              <img
                :src="product.image_url || '/images/noimage.jpg'"
                alt="Imagen del producto"
                class="w-full h-48 object-cover"
              />
              <button
                @click="openEdit(product)"
                class="absolute top-2 right-2 p-2 bg-white/80 rounded-full hover:bg-white transition"
              >
                <PencilIcon class="w-5 h-5 text-gray-700" />
              </button>
            </div>
            <div class="p-4">
              <h2 class="text-lg font-semibold text-gray-800">
                {{ product.name }}
              </h2>
              <p class="text-gray-600 mt-1">${{ product.price.toFixed(2) }}</p>
              <span
                class="inline-block px-2 py-1 text-sm mt-2 rounded"
                :class="
                  product.is_active
                    ? 'bg-green-100 text-green-800'
                    : 'bg-red-100 text-red-800'
                "
              >
                {{ product.is_active ? "Activo" : "Inactivo" }}
              </span>
            </div>
          </div>
        </div>

        <!-- Modal -->
        <ProductFormModal
          :show="showModal"
          :product="editingProduct"
          :categories="categories"
          @close="closeModal"
        />

        <!-- Paginación -->
        <div class="mt-8 flex justify-center space-x-2">
          <template v-for="(link, i) in products.links" :key="i">
            <component
              :is="link.url ? Link : 'span'"
              :href="link.url"
              v-html="link.label"
              class="px-4 py-2 text-sm border rounded"
              :class="{
                'bg-blue-500 text-white': link.active,
                'text-gray-700 hover:bg-gray-100': !link.active && link.url,
                'text-gray-400': !link.url,
              }"
            />
          </template>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>