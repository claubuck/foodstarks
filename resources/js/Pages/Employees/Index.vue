<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  employees: Array,
})

const showModal = ref(false)
const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'waiter',
})

function submit() {
  form.post(route('employees.store'), {
    onSuccess: () => {
      form.reset()
      showModal.value = false
    },
  })
}

const roleLabels = {
  owner: 'Dueño',
  manager: 'Manager',
  chef: 'Cocinero',
  waiter: 'Mesero',
}
</script>

<template>
  <Head title="Empleados" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <span>Empleados</span>
        <PrimaryButton @click="showModal = true">Agregar empleado</PrimaryButton>
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
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-for="e in employees" :key="e.id" class="hover:bg-gray-50">
              <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ e.name }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ e.email }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ roleLabels[e.role] || e.role }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ new Date(e.created_at).toLocaleDateString('es') }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Modal Agregar empleado -->
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        @click.self="showModal = false"
      >
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-lg">
          <h3 class="mb-4 text-lg font-bold text-gray-800">Agregar empleado</h3>
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
              <InputLabel for="password" value="Contraseña temporal" />
              <TextInput id="password" v-model="form.password" type="password" class="mt-1 block w-full" required />
              <InputError :message="form.errors.password" />
            </div>
            <div>
              <InputLabel for="password_confirmation" value="Confirmar contraseña" />
              <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password" class="mt-1 block w-full" required />
              <InputError :message="form.errors.password_confirmation" />
            </div>
            <div>
              <InputLabel for="role" value="Rol" />
              <select id="role" v-model="form.role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                <option value="waiter">Mesero</option>
                <option value="chef">Cocinero</option>
                <option value="manager">Manager</option>
              </select>
              <InputError :message="form.errors.role" />
            </div>
            <div class="flex justify-end gap-2">
              <button type="button" class="rounded bg-gray-200 px-4 py-2 hover:bg-gray-300" @click="showModal = false">
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
