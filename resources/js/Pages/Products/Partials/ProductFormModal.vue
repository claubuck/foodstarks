<!-- resources/js/Components/ProductFormModal.vue -->
<script setup>
import { defineProps, defineEmits, computed, ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import { PencilIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
  show: Boolean,
  product: Object,
  categories: Array,
});

const emit = defineEmits(["close"]);

const form = useForm({
  name: props.product?.name || "",
  restaurant_id: props.product?.restaurant_id || "",
  description: props.product?.description || "",
  price: props.product?.price || 0,
  image_url: null,
  category_id: props.product?.category_id || "",
  is_active: props.product?.is_active ?? true,
  _method: props.product ? "put" : "post",
});

const imagePreview = ref(props.product?.image_url || null);

const title = computed(() =>
  props.product ? "Editar Producto" : "Nuevo Producto"
);
const submitText = computed(() => (props.product ? "Actualizar" : "Crear"));

const handleFileChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    form.image_url = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      imagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const submit = () => {
  const url = props.product
    ? route("products.update", props.product.id)
    : route("products.store");

  form.post(url, {
    preserveScroll: true,
    onSuccess: () => {
      emit("close");
      form.reset();
      imagePreview.value = null;
    },
    onError: (errors) => {
        console.log(errors);
      for (const key in errors) {
        form.setError(key, errors[key]);
      }
    },
  });
};
</script>

<template>
  <Transition name="modal">
    <div
      v-show="show"
      class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-lg w-full max-w-2xl">
        <!-- Header -->
        <div class="flex justify-between items-center p-4 border-b">
          <h3 class="text-lg font-semibold">{{ title }}</h3>
          <button
            @click="$emit('close')"
            class="text-gray-500 hover:text-gray-700"
          >
            &times;
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="submit" class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700"
              >Nombre</label
            >
            <input
              v-model="form.name"
              type="text"
              class="mt-1 block w-full rounded border-gray-300 shadow-sm"
            />
            <p v-if="form.errors.name" class="text-red-500 text-sm mt-1">
              {{ form.errors.name }}
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700"
              >Descripción</label
            >
            <textarea
              v-model="form.description"
              rows="3"
              class="mt-1 block w-full rounded border-gray-300 shadow-sm"
            ></textarea>
            <p v-if="form.errors.description" class="text-red-500 text-sm mt-1">
              {{ form.errors.description }}
            </p>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700"
                >Precio</label
              >
              <input
                v-model="form.price"
                type="number"
                step="0.01"
                class="mt-1 block w-full rounded border-gray-300 shadow-sm"
              />
              <p v-if="form.errors.price" class="text-red-500 text-sm mt-1">
                {{ form.errors.price }}
              </p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700"
                >Categoría</label
              >
              <select
                v-model="form.category_id"
                class="mt-1 block w-full rounded border-gray-300 shadow-sm"
              >
                <option
                  v-for="category in categories"
                  :key="category.id"
                  :value="category.id"
                >
                  {{ category.name }}
                </option>
              </select>
                <p v-if="form.errors.category_id" class="text-red-500 text-sm mt-1">
                    {{ form.errors.category_id }}
                </p>
            </div>
          </div>

          <!-- Sección de imagen -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700"
              >Imagen del producto</label
            >

            <div class="flex items-center gap-4">
              <!-- Vista previa -->
              <div class="relative group">
                <img
                  :src="
                    imagePreview ||
                    'https://via.placeholder.com/300x200?text=Subir+imagen'
                  "
                  class="w-32 h-32 object-cover rounded-lg border-2 border-dashed border-gray-300"
                />
                <div
                  class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity rounded-lg"
                >
                  <span class="text-white text-sm">Cambiar imagen</span>
                </div>
              </div>

              <!-- Input de archivo -->
              <div>
                <input
                  type="file"
                  @change="handleFileChange"
                  accept="image/*"
                  class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                />
                <p class="text-xs text-gray-500 mt-1">
                  Formatos: JPEG, PNG, WEBP (Max 2MB)
                </p>
                <p v-if="form.errors.image" class="text-red-500 text-sm mt-1">
                  {{ form.errors.image }}
                </p>
              </div>
            </div>
          </div>

          <div class="flex items-center space-x-2">
            <input
              v-model="form.is_active"
              type="checkbox"
              id="is_active"
              class="rounded border-gray-300 text-blue-600 shadow-sm"
            />
            <label for="is_active" class="text-sm text-gray-700"
              >Producto activo</label
            >
          </div>

          <!-- Footer -->
          <div class="flex justify-end space-x-3 pt-6">
            <button
              type="button"
              @click="$emit('close')"
              class="px-4 py-2 text-gray-700 bg-gray-100 rounded hover:bg-gray-200"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="form.processing"
              class="px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700 disabled:opacity-50"
            >
              {{ submitText }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>