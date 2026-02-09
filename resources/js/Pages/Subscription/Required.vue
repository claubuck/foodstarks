<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    plans: Array,
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Suscripción requerida" />
        <div class="mx-auto max-w-3xl space-y-6 py-8">
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-center">
                <h1 class="text-2xl font-bold text-amber-800">Suscripción requerida</h1>
                <p class="mt-2 text-amber-700">
                    Tu período de prueba ha terminado o no tienes una suscripción activa. Elige un plan para seguir usando FoodStarks.
                </p>
            </div>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="plan in plans"
                    :key="plan.id"
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
                >
                    <h3 class="text-lg font-semibold text-gray-900">{{ plan.name }}</h3>
                    <p class="mt-2 text-2xl font-bold text-gray-900">
                        ${{ Number(plan.price_monthly).toFixed(2) }}
                        <span class="text-sm font-normal text-gray-500">/mes</span>
                    </p>
                    <ul class="mt-4 space-y-2 text-sm text-gray-600">
                        <li v-for="(f, i) in (plan.features || [])" :key="i">{{ f }}</li>
                    </ul>
                    <Link
                        :href="route('subscription.index')"
                        class="mt-6 block"
                    >
                        <PrimaryButton class="w-full">Seleccionar (próximamente pago)</PrimaryButton>
                    </Link>
                </div>
            </div>
            <p class="text-center text-sm text-gray-500">
                La integración con Mercado Pago se habilitará próximamente. Mientras tanto, contacta al administrador.
            </p>
        </div>
    </AuthenticatedLayout>
</template>
