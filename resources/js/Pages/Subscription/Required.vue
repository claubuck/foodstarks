<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PlanGrid from '@/Components/PlanGrid.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    plans: Array,
    support_email: String,
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Suscripción requerida" />
        <div class="mx-auto max-w-4xl space-y-6 py-8">
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-center">
                <h1 class="text-2xl font-bold text-amber-800">Suscripción requerida</h1>
                <p class="mt-2 text-amber-700">
                    Tu plan venció o no tenés una suscripción activa. Elegí un plan para seguir usando FoodStarks.
                </p>
            </div>

            <PlanGrid :plans="plans" :support-email="support_email" />

            <p v-if="support_email" class="text-center text-sm text-gray-500">
                ¿Dudas? Escribinos a <a :href="`mailto:${support_email}`" class="underline">{{ support_email }}</a>.
            </p>
        </div>
    </AuthenticatedLayout>
</template>
