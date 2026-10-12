<script setup>
import { ref } from "vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import { Link, router } from "@inertiajs/vue3";
import Notification from "@/Components/Notification.vue";

const isSidebarOpen = ref(false);

function switchBranch(branchId) {
  router.post(route("branch.switch"), { branch_id: branchId });
}

const navLinkClass = (isActive) =>
  `flex items-center px-3 py-3 rounded-lg mb-2 w-full text-left ${isActive ? 'bg-primary-100 text-primary-700' : 'text-gray-600 hover:bg-gray-100'}`;
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Mobile Sidebar Toggle -->
    <button
      @click="isSidebarOpen = !isSidebarOpen"
      class="fixed z-50 right-4 top-4 p-2 md:hidden text-gray-600 hover:text-gray-900"
    >
      <svg
        class="w-6 h-6"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M4 6h16M4 12h16M4 18h16"
        />
      </svg>
    </button>

    <!-- Sidebar -->
    <aside
      class="fixed inset-y-0 left-0 z-40 w-64 bg-white shadow-lg transform transition-transform duration-300 ease-in-out"
      :class="
        isSidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'
      "
    >
      <!-- Logo -->
      <div class="flex items-center h-16 border-b px-4">
        <Link :href="route('dashboard')" class="flex min-w-0 items-center gap-3">
          <img
            v-if="$page.props.auth.restaurant?.logo_url"
            :src="$page.props.auth.restaurant.logo_url"
            :alt="$page.props.auth.restaurant.name"
            class="h-10 w-10 shrink-0 rounded-lg object-contain"
          />
          <span
            v-else
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-100 text-lg font-bold text-primary-700"
          >
            {{ $page.props.auth.restaurant?.name?.charAt(0)?.toUpperCase() }}
          </span>
          <span class="truncate font-semibold text-gray-900" :title="$page.props.auth.restaurant?.name">
            {{ $page.props.auth.restaurant?.name }}
          </span>
        </Link>
      </div>

      <!-- Branch selector (when multiple branches) -->
      <div v-if="$page.props.auth.branches?.length > 1" class="border-b px-4 py-3">
        <label class="mb-1 block text-xs font-medium text-gray-500">Sucursal</label>
        <select
          class="w-full rounded-lg border border-gray-300 py-2 pl-3 pr-8 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
          :value="$page.props.auth.current_branch_id"
          @change="switchBranch($event.target.value)"
        >
          <option v-for="b in $page.props.auth.branches" :key="b.id" :value="b.id">
            {{ b.name }}
          </option>
        </select>
      </div>

      <!-- Navigation Menu -->
      <nav class="flex-1 overflow-y-auto">
        <div class="flex flex-col px-4 py-4">
          <Link
            :href="route('dashboard')"
            :class="navLinkClass(route().current('dashboard'))"
          >
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            Dashboard
          </Link>

          <Link
            :href="route('sales.create')"
            :class="navLinkClass(route().current('sales.create'))"
          >
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Nuevo pedido
          </Link>

          <Link
            :href="route('orders.index')"
            :class="navLinkClass(route().current('orders.index'))"
          >
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            Órdenes
          </Link>

          <Link
            :href="route('products.index')"
            :class="navLinkClass(route().current('products.index'))"
          >
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>
            Productos
          </Link>

          <Link
            v-if="['owner', 'manager'].includes($page.props.auth.user?.role)"
            :href="route('product-categories.index')"
            :class="navLinkClass(route().current('product-categories.*'))"
          >
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
            Categorías
          </Link>

          <Link
            :href="route('tables.index')"
            :class="navLinkClass(route().current('tables.*'))"
          >
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z" />
            </svg>
            Mesas
          </Link>

          <Link
            :href="route('reservations.index')"
            :class="navLinkClass(route().current('reservations.*'))"
          >
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            Reservas
          </Link>

          <Link
            v-if="['owner', 'manager'].includes($page.props.auth.user?.role)"
            :href="route('employees.index')"
            :class="navLinkClass(route().current('employees.*'))"
          >
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            Empleados
          </Link>

          <Link
            v-if="['owner', 'manager'].includes($page.props.auth.user?.role)"
            :href="route('restaurant.edit')"
            :class="navLinkClass(route().current('restaurant.*') || route().current('branches.*'))"
          >
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Configuración
          </Link>

          <Link
            v-if="$page.props.auth.is_superadmin"
            :href="route('superadmin.dashboard')"
            :class="navLinkClass(route().current('superadmin.*'))"
          >
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            Panel admin
          </Link>
        </div>
      </nav>

      <!-- User Menu -->
      <div class="border-t p-4">
        <div class="flex items-center">
          <div class="flex-1">
            <div class="text-sm font-medium text-gray-800">
              {{ $page.props.auth.user.name }}
            </div>
            <div class="text-xs text-gray-600">
              {{ $page.props.auth.user.email }}
            </div>
          </div>
          <Dropdown align="right" width="48">
            <template #trigger>
              <button class="text-gray-400 hover:text-gray-600">
                <svg
                  class="w-5 h-5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"
                  />
                </svg>
              </button>
            </template>
            <template #content>
              <DropdownLink :href="route('profile.edit')">Perfil</DropdownLink>
              <DropdownLink :href="route('subscription.index')">Mi plan</DropdownLink>
              <DropdownLink :href="route('logout')" method="post" as="button">Cerrar sesión</DropdownLink>
            </template>
          </Dropdown>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="md:ml-64 transition-all duration-300">
      <!-- Header -->
      <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8">
          <!-- w-full: el contenido del slot (título + acciones) usa todo el ancho -->
          <div class="w-full text-xl font-semibold text-gray-900">
            <slot name="header" />
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <main>
        <div class="bg-white rounded-lg shadow-sm p-6">
          <slot />
        </div>
      </main>
    </div>
  </div>

  <!-- Notification -->
  <Notification />
</template>

<style>
/* Smooth transitions */
aside {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Scrollbar styling */
nav::-webkit-scrollbar {
  width: 6px;
}

nav::-webkit-scrollbar-track {
  background: #f1f1f1;
}

nav::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 4px;
}

nav::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>