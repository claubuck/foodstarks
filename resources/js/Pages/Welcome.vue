<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    }
});
</script>

<template>
    <Head title="FoodStarks - Gestión de Restaurantes" />
    <div class="bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100 min-h-screen">
        <div class="relative flex flex-col items-center justify-center min-h-screen">
            <div class="relative w-full max-w-7xl px-6">
                <header class="flex items-center justify-between py-8">
                    <div class="flex items-center gap-2">
                        <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12h1m8-9v1m8 8h1m-9 8v1M5.6 5.6l.7.7m12.1-.7l-.7.7m0 12.1l.7.7m-12.1-.7l-.7.7"/>
                        </svg>
                        <span class="text-2xl font-bold">FoodStarks</span>
                    </div>
                    
                    <nav v-if="canLogin" class="flex gap-4">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition"
                        >
                            Panel de Control
                        </Link>

                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition"
                            >
                                Acceder
                            </Link>

                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition"
                            >
                                Registrar Restaurante
                            </Link>
                        </template>
                    </nav>
                </header>

                <main class="py-20">
                    <div class="text-center mb-20">
                        <h1 class="text-5xl font-bold mb-6">Gestión Integral para Tu Restaurante</h1>
                        <p class="text-xl text-gray-600 dark:text-gray-400 mb-8">
                            Controla menús, pedidos, reservas y más en una sola plataforma
                        </p>
                        
                        <div class="flex justify-center gap-4">
                            <Link
                                v-if="!$page.props.auth.user"
                                :href="route('register')"
                                class="px-8 py-4 bg-green-600 text-white rounded-lg text-lg hover:bg-green-700 transition"
                            >
                                Comenzar Gratis
                            </Link>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-3 gap-8">
                        <!-- Tarjeta 1: Gestión de Menú -->
                        <div class="bg-white dark:bg-gray-800 p-8 rounded-xl shadow-lg">
                            <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mb-6">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15.546c-.523 0-1.046.151-1.5.454a2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.701 2.701 0 00-1.5-.454M9 6v2m3-2v2m3-2v2M9 3h.01M12 3h.01M15 3h.01M21 21v-7a2 2 0 00-2-2H5a2 2 0 00-2 2v7h18zm-3-9v-2a2 2 0 00-2-2H8a2 2 0 00-2 2v2h12z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold mb-4">Gestión de Menú</h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                Actualiza tu carta digital en tiempo real, controla inventario y organiza por categorías
                            </p>
                        </div>

                        <!-- Tarjeta 2: Pedidos Online -->
                        <div class="bg-white dark:bg-gray-800 p-8 rounded-xl shadow-lg">
                            <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mb-6">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold mb-4">Pedidos Online</h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                Sistema integrado de pedidos para llevar y entrega a domicilio
                            </p>
                        </div>

                        <!-- Tarjeta 3: Reservas -->
                        <div class="bg-white dark:bg-gray-800 p-8 rounded-xl shadow-lg">
                            <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mb-6">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold mb-4">Gestión de Reservas</h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                Controla tu capacidad y organiza turnos con nuestro sistema inteligente
                            </p>
                        </div>
                    </div>

                    <!-- Sección de características -->
                    <div class="mt-20 text-center">
                        <h2 class="text-3xl font-bold mb-12">Todo lo que necesitas en un solo lugar</h2>
                        
                        <div class="grid md:grid-cols-2 gap-8">
                            <div class="text-left p-6">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-xl font-semibold">Reportes y Analytics</h3>
                                </div>
                                <p class="text-gray-600 dark:text-gray-400">
                                    Genera reportes detallados de ventas, productos más populares y rendimiento del personal
                                </p>
                            </div>

                            <div class="text-left p-6">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-xl font-semibold">Multiplataforma</h3>
                                </div>
                                <p class="text-gray-600 dark:text-gray-400">
                                    Accede desde cualquier dispositivo: computadora, tablet o smartphone
                                </p>
                            </div>
                        </div>
                    </div>
                </main>

                <footer class="py-8 text-center text-gray-600 dark:text-gray-400 border-t border-gray-200 dark:border-gray-800">
                    <p>FoodStarks © {{ new Date().getFullYear() }} - Tu solución integral para gestión de restaurantes</p>
                </footer>
            </div>
        </div>
    </div>
</template>