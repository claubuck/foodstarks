<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
  reservation: Object,
  tables: Array,
})

const form = useForm({
  table_id: props.reservation.table_id,
  customer_name: props.reservation.customer_name,
  customer_phone: props.reservation.customer_phone,
  reservation_time: props.reservation.reservation_time?.slice(0, 16),
  party_size: props.reservation.party_size,
  status: props.reservation.status,
  notes: props.reservation.notes || '',
})
</script>

<template>
  <Head title="Editar reserva" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <span>Editar reserva</span>
        <Link :href="route('reservations.index')" class="text-sm text-gray-600 hover:underline">Volver</Link>
      </div>
    </template>
    <form @submit.prevent="form.put(route('reservations.update', reservation.id))" class="max-w-md space-y-4">
      <div>
        <InputLabel for="table_id" value="Mesa" />
        <select id="table_id" v-model="form.table_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
          <option value="">Seleccionar mesa</option>
          <option v-for="t in tables" :key="t.id" :value="t.id">Mesa {{ t.table_number }} ({{ t.capacity }} pers.)</option>
        </select>
        <InputError :message="form.errors.table_id" />
      </div>
      <div>
        <InputLabel for="customer_name" value="Nombre del cliente" />
        <TextInput id="customer_name" v-model="form.customer_name" type="text" class="mt-1 block w-full" required />
        <InputError :message="form.errors.customer_name" />
      </div>
      <div>
        <InputLabel for="customer_phone" value="Teléfono" />
        <TextInput id="customer_phone" v-model="form.customer_phone" type="text" class="mt-1 block w-full" required />
        <InputError :message="form.errors.customer_phone" />
      </div>
      <div>
        <InputLabel for="reservation_time" value="Fecha y hora" />
        <input id="reservation_time" v-model="form.reservation_time" type="datetime-local" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required />
        <InputError :message="form.errors.reservation_time" />
      </div>
      <div>
        <InputLabel for="party_size" value="Comensales" />
        <TextInput id="party_size" v-model.number="form.party_size" type="number" min="1" max="50" class="mt-1 block w-full" required />
        <InputError :message="form.errors.party_size" />
      </div>
      <div>
        <InputLabel for="status" value="Estado" />
        <select id="status" v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
          <option value="pending">Pendiente</option>
          <option value="confirmed">Confirmada</option>
          <option value="completed">Completada</option>
          <option value="cancelled">Cancelada</option>
          <option value="no_show">No show</option>
        </select>
        <InputError :message="form.errors.status" />
      </div>
      <div>
        <InputLabel for="notes" value="Notas (opcional)" />
        <textarea id="notes" v-model="form.notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
        <InputError :message="form.errors.notes" />
      </div>
      <div class="flex gap-2">
        <PrimaryButton type="submit" :disabled="form.processing">Guardar</PrimaryButton>
        <Link :href="route('reservations.index')" class="rounded bg-gray-200 px-4 py-2 hover:bg-gray-300">Cancelar</Link>
      </div>
    </form>
  </AuthenticatedLayout>
</template>
