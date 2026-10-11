<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { Link } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
  branch_id: String,
  reverbHost: String,
  reverbPort: Number,
  reverbScheme: String,
})

const connectionStatus = ref('—')
const channelStatus = ref('—')
const debugEstado = ref('') // estado crudo para depurar
const errorMessage = ref(null)
const eventosRecibidos = ref([])
const enviando = ref(false)
const sendError = ref(null)
let channel = null
let connectionCheckInterval = null

function ahora() {
  return new Date().toLocaleTimeString('es-AR', { hour12: false })
}

function agregarEvento(msg) {
  eventosRecibidos.value = [{ time: ahora(), msg }, ...eventosRecibidos.value].slice(0, 20)
}

function setEstadoDesdePusher(state) {
  if (state === 'connected') connectionStatus.value = 'Conectado'
  else if (state === 'connecting' || state === 'unavailable') connectionStatus.value = 'Conectando...'
  else if (state === 'failed' || state === 'disconnected') connectionStatus.value = 'Desconectado'
  else connectionStatus.value = state || '—'
}

function actualizarEstadoConexion() {
  if (typeof window.Echo === 'undefined') {
    connectionStatus.value = 'Echo no cargado'
    debugEstado.value = 'sin Echo'
    return
  }
  try {
    const connector = window.Echo.connector
    const pusher = connector?.pusher
    const conn = pusher?.connection ?? connector?.connection
    const state = conn?.state
    debugEstado.value = state ? `conn.state = "${state}"` : (conn ? 'conn sin state' : 'sin connector.connection')
    if (state) setEstadoDesdePusher(state)
    else if (!conn) connectionStatus.value = 'Conectando...'
    else connectionStatus.value = state || '—'
  } catch (e) {
    connectionStatus.value = '—'
    debugEstado.value = String(e?.message || e)
  }
}

async function enviarEventoPrueba() {
  if (!props.branch_id) {
    sendError.value = 'No hay sucursal seleccionada.'
    return
  }
  enviando.value = true
  sendError.value = null
  try {
    const { data } = await axios.post(route('realtime.send'))
    if (data?.ok) {
      agregarEvento('Evento enviado por el servidor. Esperá recibirlo abajo.')
    } else {
      sendError.value = data?.error || 'Error al enviar'
    }
  } catch (e) {
    const data = e.response?.data
    sendError.value = data?.error || data?.message || e.message || 'Error de red'
  } finally {
    enviando.value = false
  }
}

onMounted(() => {
  if (!props.branch_id) {
    errorMessage.value = 'Entrá al dashboard y elegí una sucursal antes de probar.'
    return
  }

  if (typeof window.Echo === 'undefined') {
    errorMessage.value = 'Laravel Echo no está cargado. Revisá que bootstrap.js cargue antes del app.'
    connectionStatus.value = 'No disponible'
    return
  }

  connectionCheckInterval = setInterval(actualizarEstadoConexion, 1500)
  actualizarEstadoConexion()

  try {
    const pusher = window.Echo.connector?.pusher
    if (pusher?.connection) {
      pusher.connection.bind('state_change', (states) => {
        const current = states?.current
        if (current) setEstadoDesdePusher(current)
      })
      setEstadoDesdePusher(pusher.connection.state)
    }

    channel = window.Echo.private(`branch.${props.branch_id}`)
    channelStatus.value = 'Suscrito'

    channel.listen('.order.updated', () => {
      connectionStatus.value = 'Conectado'
      agregarEvento('order.updated recibido')
    })
  } catch (e) {
    channelStatus.value = 'Error'
    errorMessage.value = e?.message || String(e)
  }
})

onUnmounted(() => {
  if (connectionCheckInterval) clearInterval(connectionCheckInterval)
  if (channel) {
    window.Echo?.leave(`branch.${props.branch_id}`)
  }
})

watch(
  () => props.branch_id,
  () => {
    if (!props.branch_id) errorMessage.value = 'Seleccioná una sucursal en el dashboard.'
  }
)
</script>

<template>
  <Head title="Prueba tiempo real" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2>Prueba de tiempo real (Reverb)</h2>
        <Link
          :href="route('dashboard')"
          class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-medium shadow-sm transition border border-gray-300 bg-white text-gray-700 hover:bg-gray-50"
        >
          Volver al Dashboard
        </Link>
      </div>
    </template>

    <div class="min-h-screen px-4 py-8 md:px-8">
      <div class="mx-auto max-w-2xl space-y-6">
        <div class="rounded-2xl bg-white p-6 shadow-md">
          <h3 class="mb-4 text-lg font-semibold text-gray-800">Estado</h3>
          <ul class="space-y-2 text-sm">
            <li class="flex items-center gap-2">
              <span class="font-medium text-gray-600">Reverb (servidor):</span>
              <span>{{ reverbScheme }}://{{ reverbHost }}:{{ reverbPort }}</span>
            </li>
            <li class="flex items-center gap-2">
              <span class="font-medium text-gray-600">Canal privado:</span>
              <span class="font-mono">branch.{{ branch_id || '—' }}</span>
            </li>
            <li class="flex items-center gap-2">
              <span class="font-medium text-gray-600">WebSocket:</span>
              <span
                class="rounded-full px-2 py-0.5 text-xs font-medium"
                :class="{
                  'bg-green-100 text-green-800': connectionStatus === 'Conectado',
                  'bg-amber-100 text-amber-800': connectionStatus === 'Conectando...',
                  'bg-red-100 text-red-800': connectionStatus === 'Desconectado' || connectionStatus === 'Echo no cargado',
                  'bg-gray-100 text-gray-700': !connectionStatus || connectionStatus === '—'
                }"
              >
                {{ connectionStatus }}
              </span>
            </li>
            <li v-if="debugEstado" class="flex items-center gap-2 text-xs text-gray-500">
              <span class="font-medium">Debug:</span>
              <code class="rounded bg-gray-100 px-1">{{ debugEstado }}</code>
            </li>
            <li class="flex items-center gap-2">
              <span class="font-medium text-gray-600">Suscripción al canal:</span>
              <span
                class="rounded-full px-2 py-0.5 text-xs font-medium"
                :class="channelStatus === 'Suscrito' ? 'bg-green-100 text-green-800' : channelStatus === 'Error' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-700'"
              >
                {{ channelStatus }}
              </span>
            </li>
          </ul>
          <p v-if="errorMessage" class="mt-3 text-sm text-red-600">{{ errorMessage }}</p>

          <div
            v-if="connectionStatus === 'Desconectado' || connectionStatus === 'Conectando...'"
            class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4"
          >
            <h4 class="mb-2 font-semibold text-amber-900">Si WebSocket sigue desconectado:</h4>
            <ol class="list-inside list-decimal space-y-1 text-sm text-amber-800">
              <li>
                <strong>Revisá que Reverb esté corriendo:</strong>
                <code class="ml-1 rounded bg-amber-100 px-1">docker compose ps reverb</code>
              </li>
              <li>
                <strong>Si no está arriba:</strong>
                <code class="ml-1 rounded bg-amber-100 px-1">docker compose up -d reverb</code>
              </li>
              <li>
                <strong>Probá en otra pestaña</strong> abrir
                <a
                  :href="`${reverbScheme}://${reverbHost}:${reverbPort}`"
                  target="_blank"
                  rel="noopener"
                  class="font-mono text-blue-600 underline"
                >
                  {{ reverbScheme }}://{{ reverbHost }}:{{ reverbPort }}
                </a>
                — si no carga nada, el navegador no llega a Reverb (puerto cerrado o Reverb apagado).
              </li>
              <li>
                <strong>Si entrás a la app por otro dominio</strong> (ej. foodstarks.test o una IP), en el servidor configurá
                <code class="rounded bg-amber-100 px-1">REVERB_PUBLIC_HOST</code> con ese host o la IP (y reiniciá la app).
              </li>
              <li>
                <strong>Consola del navegador (F12 → Console):</strong> buscá el error del WebSocket (ej. <em>ERR_CONNECTION_REFUSED</em>).
              </li>
            </ol>
          </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-md">
          <h3 class="mb-4 text-lg font-semibold text-gray-800">Enviar evento de prueba</h3>
          <p class="mb-4 text-sm text-gray-600">
            El servidor enviará un evento <code class="rounded bg-gray-100 px-1">order.updated</code> al canal de tu sucursal. Si el tiempo real funciona, debería aparecer abajo en "Eventos recibidos".
          </p>
          <button
            type="button"
            :disabled="enviando || !branch_id"
            class="rounded-lg bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700 disabled:opacity-50"
            @click="enviarEventoPrueba"
          >
            {{ enviando ? 'Enviando...' : 'Enviar evento de prueba' }}
          </button>
          <p v-if="sendError" class="mt-2 text-sm text-red-600">{{ sendError }}</p>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-md">
          <h3 class="mb-4 text-lg font-semibold text-gray-800">Eventos recibidos</h3>
          <p class="mb-3 text-sm text-gray-600">
            Cada vez que recibas <code class="rounded bg-gray-100 px-1">order.updated</code> aparecerá una línea aquí (máx. 20).
          </p>
          <div
            v-if="eventosRecibidos.length === 0"
            class="rounded-lg border border-dashed border-gray-300 bg-gray-50 py-8 text-center text-sm text-gray-500"
          >
            Aún no se recibió ningún evento. Pulsá "Enviar evento de prueba" y revisá que WebSocket esté "Conectado".
          </div>
          <ul v-else class="space-y-1 font-mono text-sm">
            <li
              v-for="(ev, i) in eventosRecibidos"
              :key="i"
              class="rounded bg-green-50 py-1.5 pl-2 text-green-800"
            >
              [{{ ev.time }}] {{ ev.msg }}
            </li>
          </ul>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
