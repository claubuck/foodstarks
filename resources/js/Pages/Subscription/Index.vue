<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    subscription: Object,
    plans: Array,
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Mi plan" />
        <div class="mx-auto max-w-3xl space-y-6 py-8">
            <h1 class="text-2xl font-bold text-gray-900">Mi plan</h1>

            <div v-if="subscription" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">{{ subscription.plan?.name }}</h2>
                <p class="mt-2 text-gray-600">
                    Período actual hasta:
                    <span class="font-medium">{{ subscription.current_period_end ? new Date(subscription.current_period_end).toLocaleDateString('es') : '—' }}</span>
                </p>
                <p class="mt-1 text-sm text-gray-500">Estado: {{ subscription.status }}</p>
                <PrimaryButton class="mt-4">Renovar / Cambiar plan (próximamente)</PrimaryButton>
            </div>

            <div v-else class="rounded-xl border border-amber-200 bg-amber-50 p-6">
                <p class="text-amber-800">No tienes una suscripción activa.</p>
                <Link :href="route('subscription.required')" class="mt-4 inline-block">
                    <PrimaryButton>Elegir plan</PrimaryButton>
                </Link>
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
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
