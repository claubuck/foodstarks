<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    restaurant_name: '',
    restaurant_address: '',
    restaurant_phone: '',
    restaurant_email: '',
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Registro - FoodStarks" />

        <form @submit.prevent="submit" class="space-y-6">
            <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-4">
                <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-600">
                    Datos del restaurante
                </h3>
                <div class="space-y-4">
                    <div>
                        <InputLabel for="restaurant_name" value="Nombre del restaurante" />
                        <TextInput
                            id="restaurant_name"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.restaurant_name"
                            required
                            autofocus
                            autocomplete="organization"
                        />
                        <InputError class="mt-2" :message="form.errors.restaurant_name" />
                    </div>
                    <div>
                        <InputLabel for="restaurant_address" value="Dirección" />
                        <TextInput
                            id="restaurant_address"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.restaurant_address"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.restaurant_address" />
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="restaurant_phone" value="Teléfono" />
                            <TextInput
                                id="restaurant_phone"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.restaurant_phone"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.restaurant_phone" />
                        </div>
                        <div>
                            <InputLabel for="restaurant_email" value="Email del restaurante" />
                            <TextInput
                                id="restaurant_email"
                                type="email"
                                class="mt-1 block w-full"
                                v-model="form.restaurant_email"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.restaurant_email" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-4">
                <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-600">
                    Cuenta del dueño
                </h3>
                <div class="space-y-4">
                    <div>
                        <InputLabel for="name" value="Tu nombre" />
                        <TextInput
                            id="name"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.name"
                            required
                            autocomplete="name"
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>
                    <div>
                        <InputLabel for="email" value="Email (para iniciar sesión)" />
                        <TextInput
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            v-model="form.email"
                            required
                            autocomplete="username"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>
                    <div>
                        <InputLabel for="password" value="Contraseña" />
                        <TextInput
                            id="password"
                            type="password"
                            class="mt-1 block w-full"
                            v-model="form.password"
                            required
                            autocomplete="new-password"
                        />
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>
                    <div>
                        <InputLabel for="password_confirmation" value="Confirmar contraseña" />
                        <TextInput
                            id="password_confirmation"
                            type="password"
                            class="mt-1 block w-full"
                            v-model="form.password_confirmation"
                            required
                            autocomplete="new-password"
                        />
                        <InputError class="mt-2" :message="form.errors.password_confirmation" />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <Link
                    :href="route('login')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    ¿Ya tienes cuenta? Iniciar sesión
                </Link>
                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Crear cuenta
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
