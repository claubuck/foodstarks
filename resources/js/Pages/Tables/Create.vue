<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const form = useForm({
  table_number: '',
  capacity: 4,
})
</script>

<template>
  <Head title="Nueva mesa" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <span>Nueva mesa</span>
        <Link :href="route('tables.index')" class="text-sm text-gray-600 hover:underline">Volver</Link>
      </div>
    </template>
    <form @submit.prevent="form.post(route('tables.store'))" class="max-w-md space-y-4">
      <div>
        <InputLabel for="table_number" value="Número de mesa" />
        <TextInput id="table_number" v-model="form.table_number" type="text" class="mt-1 block w-full" required />
        <InputError :message="form.errors.table_number" />
      </div>
      <div>
        <InputLabel for="capacity" value="Capacidad (personas)" />
        <TextInput id="capacity" v-model.number="form.capacity" type="number" min="1" max="50" class="mt-1 block w-full" required />
        <InputError :message="form.errors.capacity" />
      </div>
      <div class="flex gap-2">
        <PrimaryButton type="submit" :disabled="form.processing">Guardar</PrimaryButton>
        <Link :href="route('tables.index')" class="rounded bg-gray-200 px-4 py-2 hover:bg-gray-300">Cancelar</Link>
      </div>
    </form>
  </AuthenticatedLayout>
</template>
