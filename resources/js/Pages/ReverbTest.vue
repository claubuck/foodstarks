<script setup>
import { Head } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'

defineProps({
  reverbHost: String,
  reverbPort: Number,
  reverbScheme: String,
})

const status = ref('Conectando...')
const error = ref(null)

onMounted(() => {
  if (typeof window.Echo === 'undefined') {
    status.value = 'Echo no disponible'
    error.value = 'Laravel Echo no está cargado. Revisa bootstrap.js y que Reverb esté configurado.'
    return
  }

  // Suscribirse a un canal público prueba la conexión WebSocket a Reverb
  try {
    const channel = window.Echo.channel('reverb-test')
    channel.error((e) => {
      status.value = 'Error'
      error.value = e?.message || 'Error al conectar con Reverb'
    })

    // Si en 3 segundos no hubo error, la conexión WebSocket está bien
    setTimeout(() => {
      if (status.value === 'Conectando...') {
        status.value = 'Conectado'
        error.value = null
      }
    }, 3000)
  } catch (e) {
    status.value = 'Error'
    error.value = e?.message || String(e)
  }
})
</script>

<template>
  <Head title="Prueba Reverb" />
  <div class="min-h-screen bg-gray-100 py-12 px-4">
    <div class="mx-auto max-w-md rounded-xl bg-white p-6 shadow">
      <h1 class="text-xl font-bold text-gray-900">Prueba Laravel Reverb</h1>
      <p class="mt-2 text-sm text-gray-600">
        Servidor: {{ reverbScheme }}://{{ reverbHost }}:{{ reverbPort }}
      </p>
      <div class="mt-4">
        <span
          class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium"
          :class="
            status.startsWith('Conectado')
              ? 'bg-green-100 text-green-800'
              : status === 'Error'
                ? 'bg-red-100 text-red-800'
                : 'bg-amber-100 text-amber-800'
          "
        >
          {{ status }}
        </span>
      </div>
      <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>
      <p class="mt-4 text-xs text-gray-500">
        Si ves "Conectado", Reverb está funcionando. Si el navegador no puede conectar al WebSocket, revisa que el contenedor Reverb esté en marcha y que VITE_REVERB_* apunte al host/puerto correctos.
      </p>
    </div>
  </div>
</template>
