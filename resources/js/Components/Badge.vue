<script setup>
import { computed } from 'vue'

const props = defineProps({
    variant: {
        type: String,
        default: 'gray',
        validator: (value) => ['indigo', 'green', 'red', 'amber', 'gray', 'blue', 'zinc'].includes(value)
    }
})

const classes = computed(() => {
    const variants = {
        indigo: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
        green: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        red: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        amber: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
        gray: 'bg-gray-100 text-gray-800 dark:bg-zinc-700/50 dark:text-zinc-300 border border-transparent dark:border-zinc-600',
        blue: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        zinc: 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700/50 dark:text-zinc-200 border border-transparent dark:border-zinc-600'
    }
    return variants[props.variant] || variants.gray
})
</script>

<template>
    <span :class="['inline-block whitespace-nowrap px-2 py-1 rounded-full text-xs font-medium transition-colors duration-200', classes]">
        <slot />
    </span>
</template>
