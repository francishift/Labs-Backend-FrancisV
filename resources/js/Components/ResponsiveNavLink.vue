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
    },
});

const classes = computed(() =>
    props.active
        ? 'block w-full ps-3 pe-4 py-2 text-start text-base font-medium text-gray-900 dark:text-zinc-100 bg-gray-100 dark:bg-zinc-900 focus:outline-none focus:text-gray-950 dark:focus:text-white focus:bg-gray-200 dark:focus:bg-zinc-800 transition duration-150 ease-in-out'
        : 'block w-full ps-3 pe-4 py-2 text-start text-base font-medium text-gray-600 dark:text-zinc-400 hover:text-gray-800 dark:hover:text-zinc-200 hover:bg-gray-50 dark:hover:bg-zinc-700 focus:outline-none focus:text-gray-800 dark:focus:text-zinc-200 focus:bg-gray-50 dark:focus:bg-zinc-700 transition duration-150 ease-in-out',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
