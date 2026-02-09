<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
  branch: Object,
})

const isEdit = !!props.branch?.id
const form = useForm({
  name: props.branch?.name ?? '',
  address: props.branch?.address ?? '',
  phone: props.branch?.phone ?? '',
  opening_hours: props.branch?.opening_hours ?? {},
  is_active: props.branch?.is_active ?? true,
})
</script>

<template>
  <Head :title="isEdit ? 'Editar sucursal' : 'Nueva sucursal'" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <span>{{ isEdit ? 'Editar sucursal' : 'Nueva sucursal' }}</span>
        <Link :href="route('branches.index')" class="text-sm text-gray-600 hover:underline">Volver</Link>
      </div>
    </template>
    <form @submit.prevent="isEdit ? form.put(route('branches.update', branch.id)) : form.post(route('branches.store'))" class="max-w-xl space-y-4">
      <div>
        <InputLabel for="name" value="Nombre de la sucursal" />
        <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
        <InputError :message="form.errors.name" />
      </div>
      <div>
        <InputLabel for="address" value="Dirección" />
        <TextInput id="address" v-model="form.address" type="text" class="mt-1 block w-full" />
        <InputError :message="form.errors.address" />
      </div>
      <div>
        <InputLabel for="phone" value="Teléfono" />
        <TextInput id="phone" v-model="form.phone" type="text" class="mt-1 block w-full" />
        <InputError :message="form.errors.phone" />
      </div>
      <div v-if="isEdit" class="flex items-center gap-2">
        <input id="is_active" v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
        <InputLabel for="is_active" value="Sucursal activa" class="!mt-0" />
        <InputError :message="form.errors.is_active" />
      </div>
      <div class="flex gap-2">
        <PrimaryButton type="submit" :disabled="form.processing">Guardar</PrimaryButton>
        <Link :href="route('branches.index')" class="rounded bg-gray-200 px-4 py-2 hover:bg-gray-300">Cancelar</Link>
      </div>
    </form>
  </AuthenticatedLayout>
</template>
