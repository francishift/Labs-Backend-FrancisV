<script setup>
import { SunIcon, MoonIcon } from '@heroicons/vue/24/outline'

defineProps({
    isDark: Boolean
})

defineEmits(['toggle'])
</script>

<template>
    <button
        @click="$emit('toggle')"
        class="inline-flex items-center rounded-md border border-transparent bg-white dark:bg-zinc-800 px-3 py-2 text-sm font-medium leading-4 text-gray-500 dark:text-zinc-400 transition duration-150 ease-in-out hover:text-gray-700 dark:hover:text-zinc-200 focus:outline-none"
    >
        <SunIcon v-if="isDark" class="h-5 w-5" />
        <MoonIcon v-else class="h-5 w-5" />
    </button>
</template>
