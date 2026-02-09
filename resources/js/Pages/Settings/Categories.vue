<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  categories: Array,
})

const formAdd = useForm({
  name: '',
  description: '',
})

const editingId = ref(null)
const formEdit = useForm({
  name: '',
  description: '',
})

function submitAdd() {
  formAdd.post(route('product-categories.store'), {
    preserveScroll: true,
    onSuccess: () => formAdd.reset(),
  })
}

function startEdit(cat) {
  editingId.value = cat.id
  formEdit.name = cat.name
  formEdit.description = cat.description || ''
}

function cancelEdit() {
  editingId.value = null
  formEdit.reset()
}

function submitEdit() {
  formEdit.put(route('product-categories.update', editingId.value), {
    preserveScroll: true,
    onSuccess: () => {
      editingId.value = null
      formEdit.reset()
    },
  })
}

function destroy(id) {
  if (confirm('¿Eliminar esta categoría? Solo se puede si no tiene productos asociados.')) {
    router.delete(route('product-categories.destroy', id), { preserveScroll: true })
  }
}
</script>

<template>
  <Head title="Categorías de productos" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <span>Categorías de productos</span>
        <div class="flex gap-2">
          <Link :href="route('restaurant.edit')" class="text-sm text-primary-600 hover:underline">Restaurante</Link>
          <Link :href="route('branches.index')" class="text-sm text-primary-600 hover:underline">Sucursales</Link>
        </div>
      </div>
    </template>
    <div class="space-y-6">
      <!-- Formulario nueva categoría -->
      <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
        <h2 class="mb-3 text-sm font-medium text-gray-700">Nueva categoría</h2>
        <form @submit.prevent="submitAdd" class="flex flex-wrap items-end gap-4">
          <div class="min-w-[200px] flex-1">
            <InputLabel for="name" value="Nombre" />
            <TextInput
              id="name"
              v-model="formAdd.name"
              type="text"
              class="mt-1 block w-full"
              placeholder="Ej: Bebidas"
              required
            />
            <InputError :message="formAdd.errors.name" />
          </div>
          <div class="min-w-[200px] flex-1">
            <InputLabel for="description" value="Descripción (opcional)" />
            <TextInput
              id="description"
              v-model="formAdd.description"
              type="text"
              class="mt-1 block w-full"
              placeholder="Ej: Refrescos, jugos, café"
            />
            <InputError :message="formAdd.errors.description" />
          </div>
          <PrimaryButton type="submit" :disabled="formAdd.processing">Agregar</PrimaryButton>
        </form>
      </div>

      <!-- Lista de categorías -->
      <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Nombre</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Descripción</th>
              <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-for="c in categories" :key="c.id" class="hover:bg-gray-50">
              <td class="px-4 py-3">
                <TextInput
                  v-if="editingId === c.id"
                  v-model="formEdit.name"
                  type="text"
                  class="block w-full"
                  required
                />
                <span v-else class="text-sm font-medium text-gray-700">{{ c.name }}</span>
              </td>
              <td class="px-4 py-3">
                <TextInput
                  v-if="editingId === c.id"
                  v-model="formEdit.description"
                  type="text"
                  class="block w-full"
                />
                <span v-else class="text-sm text-gray-600">{{ c.description || '—' }}</span>
              </td>
              <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                <template v-if="editingId === c.id">
                  <button type="button" class="text-primary-600 hover:underline" @click="submitEdit">Guardar</button>
                  <button type="button" class="ml-3 text-gray-600 hover:underline" @click="cancelEdit">Cancelar</button>
                </template>
                <template v-else>
                  <button type="button" class="text-primary-600 hover:underline" @click="startEdit(c)">Editar</button>
                  <button type="button" class="ml-3 text-red-600 hover:underline" @click="destroy(c.id)">Eliminar</button>
                </template>
              </td>
            </tr>
            <tr v-if="!categories?.length">
              <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">
                No hay categorías. Agrega una arriba para usarlas en Productos y en Nuevo pedido.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
