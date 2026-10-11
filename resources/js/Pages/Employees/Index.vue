<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
  employees: Array,
  max_users: Number,
})

const currentUserId = computed(() => usePage().props.auth.user?.id)

const showModal = ref(false)
const editing = ref(null)
const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'waiter',
})

function abrirAlta() {
  editing.value = null
  form.reset()
  form.clearErrors()
  showModal.value = true
}

function abrirEdicion(e) {
  editing.value = e
  form.clearErrors()
  form.name = e.name
  form.email = e.email
  form.role = e.role
  form.password = ''
  form.password_confirmation = ''
  showModal.value = true
}

function cerrar() {
  showModal.value = false
  editing.value = null
}

function submit() {
  const options = {
    onSuccess: () => {
      form.reset()
      cerrar()
    },
  }
  if (editing.value) {
    form.put(route('employees.update', editing.value.id), options)
  } else {
    form.post(route('employees.store'), options)
  }
}

function eliminar(e) {
  if (confirm(`¿Eliminar a ${e.name}? Sus pedidos quedarán a tu nombre.`)) {
    router.delete(route('employees.destroy', e.id))
  }
}

const gestionable = (e) => e.role !== 'owner' && e.id !== currentUserId.value

const roleLabels = {
  owner: 'Dueño',
  admin: 'Administrador',
  manager: 'Encargado',
  chef: 'Cocinero',
  waiter: 'Mozo',
}
</script>

<template>
  <Head title="Empleados" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <span>
          Empleados
          <span v-if="max_users" class="ml-2 text-sm font-normal text-gray-500">({{ employees.length }} / {{ max_users }})</span>
        </span>
        <button type="button" class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-medium shadow-sm transition bg-primary-600 text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2" @click="abrirAlta">Agregar empleado</button>
      </div>
    </template>
    <div class="space-y-4">
      <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Nombre</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Email</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Rol</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Alta</th>
              <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-for="e in employees" :key="e.id" class="hover:bg-gray-50">
              <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ e.name }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ e.email }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ roleLabels[e.role] || e.role }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ new Date(e.created_at).toLocaleDateString('es') }}</td>
              <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                <template v-if="gestionable(e)">
                  <button type="button" class="text-blue-600 hover:underline" @click="abrirEdicion(e)">Editar</button>
                  <button type="button" class="ml-3 text-red-600 hover:underline" @click="eliminar(e)">Eliminar</button>
                </template>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        @click.self="cerrar"
      >
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-lg">
          <h3 class="mb-4 text-lg font-bold text-gray-800">{{ editing ? 'Editar empleado' : 'Agregar empleado' }}</h3>
          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <InputLabel for="name" value="Nombre" />
              <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
              <InputError :message="form.errors.name" />
            </div>
            <div>
              <InputLabel for="email" value="Email" />
              <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" required />
              <InputError :message="form.errors.email" />
            </div>
            <div>
              <InputLabel for="password" :value="editing ? 'Nueva contraseña (opcional)' : 'Contraseña temporal'" />
              <TextInput id="password" v-model="form.password" type="password" class="mt-1 block w-full" :required="!editing" />
              <InputError :message="form.errors.password" />
            </div>
            <div>
              <InputLabel for="password_confirmation" value="Confirmar contraseña" />
              <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password" class="mt-1 block w-full" :required="!editing || !!form.password" />
              <InputError :message="form.errors.password_confirmation" />
            </div>
            <div>
              <InputLabel for="role" value="Rol" />
              <select id="role" v-model="form.role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                <option value="waiter">Mozo</option>
                <option value="chef">Cocinero</option>
                <option value="manager">Encargado</option>
              </select>
              <InputError :message="form.errors.role" />
            </div>
            <div class="flex justify-end gap-2">
              <button type="button" class="rounded bg-gray-200 px-4 py-2 hover:bg-gray-300" @click="cerrar">
                Cancelar
              </button>
              <PrimaryButton type="submit" :disabled="form.processing">Guardar</PrimaryButton>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
