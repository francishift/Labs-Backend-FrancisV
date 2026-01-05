<script setup>
defineProps({
    title: String,
    inline: {
        type: Boolean,
        default: false
    }
})
</script>

<template>
    <div :class="[
        'flex gap-3 sm:flex-row sm:items-center sm:justify-between',
        inline ? 'flex-row items-center justify-between' : 'flex-col'
    ]">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-zinc-200 truncate">
            {{ title }}
        </h2>

        <div :class="[
            'flex gap-2 sm:flex-row sm:items-center sm:gap-3',
            inline ? 'flex-row items-center' : 'flex-col'
        ]">
            <slot name="actions" />
        </div>
    </div>
</template>
