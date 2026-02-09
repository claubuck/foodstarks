<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
  canLogin: { type: Boolean },
  canRegister: { type: Boolean },
});
</script>

<template>
  <Head title="FoodStarks - Software para restaurantes, bares y cafés" />
  <div class="min-h-screen bg-white text-gray-900">
    <!-- Header fijo -->
    <header class="sticky top-0 z-50 bg-white/95 backdrop-blur border-b border-gray-100">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 md:h-20">
          <Link href="/" class="flex items-center gap-2">
            <div class="w-10 h-10 rounded-xl bg-brand-600 flex items-center justify-center">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <span class="text-xl font-bold text-gray-900">FoodStarks</span>
          </Link>

          <nav class="hidden md:flex items-center gap-8">
            <a href="#funcionalidades" class="text-gray-600 hover:text-brand-600 transition">Funcionalidades</a>
            <a href="#precios" class="text-gray-600 hover:text-brand-600 transition">Precios</a>
          </nav>

          <div class="flex items-center gap-3">
            <template v-if="$page.props.auth?.user">
              <Link
                :href="route('dashboard')"
                class="px-4 py-2.5 bg-brand-600 text-white rounded-lg font-medium hover:bg-brand-700 transition"
              >
                Panel de Control
              </Link>
            </template>
            <template v-else>
              <Link
                v-if="canLogin"
                :href="route('login')"
                class="px-4 py-2.5 text-gray-700 hover:text-brand-600 font-medium transition"
              >
                Iniciar sesión
              </Link>
              <Link
                v-if="canRegister"
                :href="route('register')"
                class="px-5 py-2.5 bg-brand-600 text-white rounded-lg font-medium hover:bg-brand-700 transition"
              >
                Probar gratis
              </Link>
            </template>
          </div>
        </div>
      </div>
    </header>

    <!-- Hero -->
    <section class="relative overflow-hidden">
      <div class="absolute inset-0 bg-gradient-to-br from-brand-50 via-white to-brand-50/50" />
      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 md:py-28">
        <div class="text-center max-w-3xl mx-auto">
          <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-gray-900 leading-tight mb-6">
            Software para restaurantes, bares y cafés.
          </h1>
          <p class="text-2xl md:text-3xl font-semibold text-brand-700 mb-4">
            Vos encargate de la comida.
          </p>
          <p class="text-xl text-brand-600 font-medium mb-10">
            FoodStarks resuelve
          </p>
          <Link
            v-if="canRegister && !$page.props.auth?.user"
            :href="route('register')"
            class="inline-flex items-center px-8 py-4 bg-brand-600 text-white rounded-xl text-lg font-semibold hover:bg-brand-700 transition shadow-lg shadow-brand-600/25"
          >
            Probar gratis
          </Link>
        </div>
      </div>
    </section>

    <!-- Funcionalidades - 3 bloques estilo Fudo -->
    <section id="funcionalidades" class="py-20 md:py-28 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Bloque 1: Venta / Pedidos -->
        <div class="grid md:grid-cols-2 gap-12 md:gap-20 items-center mb-24">
          <div>
            <span class="inline-block px-3 py-1 rounded-full text-sm font-medium bg-brand-100 text-brand-700 mb-4">Venta y pedidos</span>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6">
              Impulsá tu operación y simplificá tus cobros
            </h2>
            <p class="text-lg text-gray-600 mb-6">
              Administrá pedidos, mesas y comandas en una sola plataforma. Nuevo pedido, órdenes activas y cobro en segundos.
            </p>
            <ul class="space-y-3 text-gray-600">
              <li class="flex items-center gap-2">
                <span class="w-5 h-5 rounded-full bg-brand-500 flex items-center justify-center flex-shrink-0">
                  <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                </span>
                Pedidos y mesas en tiempo real
              </li>
              <li class="flex items-center gap-2">
                <span class="w-5 h-5 rounded-full bg-brand-500 flex items-center justify-center flex-shrink-0">
                  <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                </span>
                Productos y categorías
              </li>
              <li class="flex items-center gap-2">
                <span class="w-5 h-5 rounded-full bg-brand-500 flex items-center justify-center flex-shrink-0">
                  <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                </span>
                Múltiples sucursales
              </li>
            </ul>
          </div>
          <div class="relative">
            <div class="aspect-square max-w-md mx-auto rounded-2xl bg-gradient-to-br from-brand-100 to-brand-200 flex items-center justify-center shadow-xl">
              <svg class="w-32 h-32 text-brand-600 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Bloque 2: Servicio eficiente -->
        <div class="grid md:grid-cols-2 gap-12 md:gap-20 items-center mb-24">
          <div class="order-2 md:order-1 relative">
            <div class="aspect-square max-w-md mx-auto rounded-2xl bg-gradient-to-br from-brand-100 to-brand-200 flex items-center justify-center shadow-xl">
              <svg class="w-32 h-32 text-brand-600 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <div class="order-1 md:order-2">
            <span class="inline-block px-3 py-1 rounded-full text-sm font-medium bg-brand-100 text-brand-700 mb-4">Servicio eficiente</span>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6">
              Cargá pedidos y visualizá todo en segundos
            </h2>
            <p class="text-lg text-gray-600">
              Dashboard con ventas del día, mesas ocupadas y órdenes activas. Reservas, empleados y configuración de tu restaurante en un solo lugar.
            </p>
          </div>
        </div>

        <!-- Bloque 3: Mejores resultados -->
        <div class="grid md:grid-cols-2 gap-12 md:gap-20 items-center">
          <div>
            <span class="inline-block px-3 py-1 rounded-full text-sm font-medium bg-brand-100 text-brand-700 mb-4">Mejores resultados</span>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6">
              Registrá ventas, mesas y órdenes
            </h2>
            <p class="text-lg text-gray-600 mb-6">
              Información clave para tomar decisiones: ventas del día, mesas totales y ocupadas, órdenes activas. Suscripción por plan y múltiples sucursales.
            </p>
            <Link
              v-if="canRegister && !$page.props.auth?.user"
              :href="route('register')"
              class="inline-flex text-brand-600 font-semibold hover:text-brand-700"
            >
              Ver funcionalidades →
            </Link>
          </div>
          <div class="relative">
            <div class="aspect-square max-w-md mx-auto rounded-2xl bg-gradient-to-br from-brand-100 to-brand-200 flex items-center justify-center shadow-xl">
              <svg class="w-32 h-32 text-brand-600 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Una única solución -->
    <section class="py-20 bg-brand-50 border-y border-brand-100">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
          Una única solución para cualquier tipo de negocio.
        </h2>
        <p class="text-xl text-gray-600 mb-10 max-w-2xl mx-auto">
          ¿Tenés un restaurante, pizzería, parrilla, bar, café o dark kitchen? FoodStarks se hace cargo.
        </p>
        <p class="text-2xl font-semibold text-brand-700">
          FoodStarks se hace cargo
        </p>
      </div>
    </section>

    <!-- CTA Precios -->
    <section id="precios" class="py-20 md:py-28 bg-white">
      <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6">
          Probalo GRATIS y decidí si es para vos.
        </h2>
        <p class="text-lg text-gray-600 mb-10">
          Planes según tus necesidades. Empezá con prueba gratuita.
        </p>
        <Link
          v-if="canRegister && !$page.props.auth?.user"
          :href="route('register')"
          class="inline-flex items-center px-8 py-4 bg-brand-600 text-white rounded-xl text-lg font-semibold hover:bg-brand-700 transition shadow-lg shadow-brand-600/25"
        >
          Probar gratis
        </Link>
        <Link
          v-else-if="canLogin && !$page.props.auth?.user"
          :href="route('login')"
          class="inline-flex items-center px-8 py-4 bg-brand-600 text-white rounded-xl text-lg font-semibold hover:bg-brand-700 transition"
        >
          Ver planes y precios
        </Link>
      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 py-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-4 gap-12 mb-12">
          <div>
            <div class="flex items-center gap-2 mb-4">
              <div class="w-10 h-10 rounded-xl bg-brand-600 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <span class="text-white font-bold text-lg">FoodStarks</span>
            </div>
            <p class="text-sm text-gray-400">
              Software para restaurantes, bares y cafés.
            </p>
          </div>
          <div>
            <h4 class="font-semibold text-white mb-4">Producto</h4>
            <ul class="space-y-2 text-sm">
              <li><a href="#funcionalidades" class="hover:text-brand-400 transition">Funcionalidades</a></li>
              <li><a href="#precios" class="hover:text-brand-400 transition">Precios</a></li>
              <li><Link :href="route('login')" class="hover:text-brand-400 transition">Iniciar sesión</Link></li>
              <li><Link :href="route('register')" class="hover:text-brand-400 transition">Registrarse</Link></li>
            </ul>
          </div>
          <div>
            <h4 class="font-semibold text-white mb-4">Recursos</h4>
            <ul class="space-y-2 text-sm">
              <li><a href="#" class="hover:text-brand-400 transition">Centro de ayuda</a></li>
              <li><a href="#" class="hover:text-brand-400 transition">Preguntas frecuentes</a></li>
            </ul>
          </div>
          <div>
            <h4 class="font-semibold text-white mb-4">Contacto</h4>
            <p class="text-sm text-gray-400">
              Soporte y consultas comerciales.
            </p>
          </div>
        </div>
        <div class="pt-8 border-t border-gray-800 text-center text-sm text-gray-500">
          © FoodStarks {{ new Date().getFullYear() }}. Todos los derechos reservados.
        </div>
      </div>
    </footer>
  </div>
</template>
