<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({ title: String })

const tabs = [
  { label: 'Restaurantes', route: 'superadmin.dashboard', active: ['superadmin.dashboard', 'superadmin.restaurants.*'] },
  { label: 'Usuarios', route: 'superadmin.users', active: ['superadmin.users'] },
]
const isActive = (tab) => tab.active.some((name) => route().current(name))
</script>

<template>
  <div class="flex flex-wrap items-center justify-between gap-4">
    <div class="flex flex-wrap items-center gap-6">
      <h2>{{ title }}</h2>
      <nav class="flex gap-1 rounded-lg bg-gray-100 p-1">
        <Link
          v-for="tab in tabs"
          :key="tab.route"
          :href="route(tab.route)"
          :class="['rounded-md px-3 py-1.5 text-sm font-medium transition', isActive(tab) ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900']"
        >
          {{ tab.label }}
        </Link>
      </nav>
    </div>
    <Link
      :href="route('dashboard')"
      class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-medium shadow-sm transition border border-gray-300 bg-white text-gray-700 hover:bg-gray-50"
    >
      Volver al Dashboard
    </Link>
  </div>
</template>
