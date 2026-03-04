<script>
export default {
    inheritAttrs: false
}
</script>

<script setup>
import { XMarkIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/solid';

const model = defineModel({
    type: String,
    required: true,
});

defineProps({
    placeholder: {
        type: String,
        default: 'Buscar...',
    },
});

const clear = () => {
    model.value = '';
};
</script>

<template>
    <div class="relative">
        <!-- Search Icon (Left) -->
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <MagnifyingGlassIcon class="h-4 w-4 text-gray-400" aria-hidden="true" />
        </div>
        
        <input
            v-bind="$attrs"
            type="text"
            class="w-full rounded-md border-gray-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 shadow-sm focus:border-zinc-500 focus:ring-zinc-500 pl-10 pr-10 py-2 sm:text-sm transition-colors dark:[color-scheme:dark]"
            :placeholder="placeholder"
            v-model="model"
        />
        
        <!-- Clear Button (Right) -->
        <button
            v-if="model"
            type="button"
            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-zinc-200 transition-colors"
            @click="clear"
        >
            <XMarkIcon class="h-4 w-4" />
        </button>
    </div>
</template>
