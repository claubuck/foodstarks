<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PlanGrid from '@/Components/PlanGrid.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    subscription: Object,
    plans: Array,
    support_email: String,
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Mi plan" />
        <div class="mx-auto max-w-4xl space-y-6 py-8">
            <h1 class="text-2xl font-bold text-gray-900">Mi plan</h1>

            <div v-if="subscription" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">{{ subscription.plan?.name }}</h2>
                <p class="mt-2 text-gray-600">
                    Vigente hasta:
                    <span class="font-medium">{{ subscription.current_period_end ? new Date(subscription.current_period_end).toLocaleDateString('es') : 'sin vencimiento' }}</span>
                </p>
                <p class="mt-1 text-sm text-gray-500">
                    {{ subscription.plan?.max_branches }} sucursal(es) · {{ subscription.plan?.max_users }} usuarios
                </p>
            </div>

            <div v-else class="rounded-xl border border-amber-200 bg-amber-50 p-6">
                <p class="text-amber-800">No tenés una suscripción activa. Elegí un plan para seguir usando FoodStarks.</p>
            </div>

            <PlanGrid :plans="plans" :current-plan-id="subscription?.plan_id" :support-email="support_email" />

            <p v-if="support_email" class="text-center text-sm text-gray-500">
                Para cambiar de plan escribinos a <a :href="`mailto:${support_email}`" class="underline">{{ support_email }}</a>.
            </p>
        </div>
    </AuthenticatedLayout>
</template>
