<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
        default: false,
    },
    activeClass: {
        type: String,
        default: '',
    },
    inactiveClass: {
        type: String,
        default: 'text-gray-600 hover:bg-gray-100',
    },
    class: {
        type: String,
        default: '',
    },
});

const baseClass = 'flex items-center px-3 py-3 rounded-lg mb-2 w-full text-left';
const stateClass = computed(() =>
    props.active ? props.activeClass : props.inactiveClass
);
const classes = computed(() =>
    [baseClass, props.class, stateClass.value].filter(Boolean).join(' ')
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
