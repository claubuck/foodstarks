<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import {
  CpuChipIcon,
  SignalIcon,
} from '@heroicons/vue/24/outline'

defineProps({
  horizon_url: String,
})
</script>

<template>
  <Head title="Panel de administración" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900">Panel de administración</h2>
        <Link
          :href="route('dashboard')"
          class="rounded bg-gray-200 px-4 py-2 text-gray-800 hover:bg-gray-300"
        >
          Volver al Dashboard
        </Link>
      </div>
    </template>

    <div class="min-h-screen px-4 py-8 md:px-8">
      <p class="mb-8 text-gray-600">
        Herramientas de sistema para administradores: tiempo real (Reverb) y colas (Horizon).
      </p>

      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <Link
          :href="route('realtime.test')"
          class="flex flex-col items-start rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-md transition hover:border-amber-300 hover:shadow-lg"
        >
          <div class="mb-4 rounded-full bg-amber-100 p-3">
            <SignalIcon class="h-8 w-8 text-amber-600" />
          </div>
          <h3 class="mb-2 text-lg font-semibold text-gray-900">Prueba tiempo real</h3>
          <p class="mb-4 text-sm text-gray-600">
            Ver estado de Reverb (WebSocket), suscripción al canal y enviar evento de prueba.
          </p>
          <span class="rounded bg-amber-200 px-3 py-1.5 text-sm font-medium text-amber-900">
            Ir a Prueba tiempo real →
          </span>
        </Link>

        <a
          :href="horizon_url"
          target="_blank"
          rel="noopener noreferrer"
          class="flex flex-col items-start rounded-2xl border border-indigo-200 bg-indigo-50 p-6 shadow-md transition hover:border-indigo-300 hover:shadow-lg"
        >
          <div class="mb-4 rounded-full bg-indigo-100 p-3">
            <CpuChipIcon class="h-8 w-8 text-indigo-600" />
          </div>
          <h3 class="mb-2 text-lg font-semibold text-gray-900">Horizon</h3>
          <p class="mb-4 text-sm text-gray-600">
            Dashboard de colas (Redis): jobs, workers, throughput y fallos.
          </p>
          <span class="rounded bg-indigo-200 px-3 py-1.5 text-sm font-medium text-indigo-900">
            Abrir Horizon (nueva pestaña) →
          </span>
        </a>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
