<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
  restaurant: Object,
})

const form = useForm({
  name: props.restaurant?.name ?? '',
  address: props.restaurant?.address ?? '',
  phone: props.restaurant?.phone ?? '',
  email: props.restaurant?.email ?? '',
  logo_url: null,
  opening_hours: props.restaurant?.opening_hours ?? {},
})

function submit() {
  form.transform((data) => ({ ...data, _method: 'PUT' }))
  form.post(route('restaurant.update'), { forceFormData: true })
}
</script>

<template>
  <Head title="Configuración del restaurante" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <span>Configuración del restaurante</span>
        <div class="flex gap-4">
          <Link :href="route('branches.index')" class="text-sm text-primary-600 hover:underline">Sucursales</Link>
          <Link :href="route('product-categories.index')" class="text-sm text-primary-600 hover:underline">Categorías</Link>
        </div>
      </div>
    </template>
    <form @submit.prevent="submit" class="max-w-xl space-y-4" enctype="multipart/form-data">
      <div>
        <InputLabel for="name" value="Nombre del restaurante" />
        <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
        <InputError :message="form.errors.name" />
      </div>
      <div>
        <InputLabel for="address" value="Dirección" />
        <TextInput id="address" v-model="form.address" type="text" class="mt-1 block w-full" required />
        <InputError :message="form.errors.address" />
      </div>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <InputLabel for="phone" value="Teléfono" />
          <TextInput id="phone" v-model="form.phone" type="text" class="mt-1 block w-full" required />
          <InputError :message="form.errors.phone" />
        </div>
        <div>
          <InputLabel for="email" value="Email" />
          <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" required />
          <InputError :message="form.errors.email" />
        </div>
      </div>
      <div>
        <InputLabel for="logo_url" value="Logo (opcional)" />
        <input
          id="logo_url"
          type="file"
          accept="image/*"
          class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded file:border-0 file:bg-primary-50 file:px-4 file:py-2 file:text-primary-700 hover:file:bg-primary-100"
          @change="form.logo_url = $event.target.files?.[0]"
        />
        <InputError :message="form.errors.logo_url" />
        <img v-if="restaurant?.logo_url" :src="restaurant.logo_url" alt="Logo" class="mt-2 h-20 object-contain" />
      </div>
      <div class="flex gap-2">
        <PrimaryButton type="submit" :disabled="form.processing">Guardar</PrimaryButton>
        <Link :href="route('dashboard')" class="rounded bg-gray-200 px-4 py-2 hover:bg-gray-300">Cancelar</Link>
      </div>
    </form>
  </AuthenticatedLayout>
</template>
