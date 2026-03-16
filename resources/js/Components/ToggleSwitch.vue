<script setup>
import { computed } from 'vue';

const props = defineProps({
    checked: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    }
});

const emit = defineEmits(['update:checked']);

const proxyChecked = computed({
    get() {
        return props.checked;
    },
    set(val) {
        emit('update:checked', val);
    },
});
</script>

<template>
    <button 
        type="button" 
        @click="!disabled && (proxyChecked = !proxyChecked)"
        :class="[
            proxyChecked ? 'bg-emerald-500' : 'bg-gray-200 dark:bg-zinc-700',
            disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer hover:bg-gray-300 dark:hover:bg-zinc-600',
            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-800 focus:ring-offset-white'
        ]"
        role="switch" 
        :aria-checked="proxyChecked"
    >
        <span class="sr-only">Toggle</span>
        <span 
            aria-hidden="true" 
            :class="[
                proxyChecked ? 'translate-x-5' : 'translate-x-0',
                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
            ]"
        />
    </button>
</template>
