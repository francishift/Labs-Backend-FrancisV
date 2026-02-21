<script>
export default {
    inheritAttrs: false
}
</script>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { ChevronUpDownIcon, MagnifyingGlassIcon, CheckIcon, XMarkIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    options: {
        type: Array,
        required: true,
        // Formato esperado: [{ id: 1, name: 'Option 1' }]
    },
    placeholder: {
        type: String,
        default: 'Seleccionar opciones...',
    },
    labelKey: {
        type: String,
        default: 'nombre', // Usando 'nombre' por defecto para las extensiones
    },
    valueKey: {
        type: String,
        default: 'id',
    },
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const search = ref('');
const selectRef = ref(null);

const filteredOptions = computed(() => {
    if (!search.value) return props.options;
    const s = search.value.toLowerCase();
    return props.options.filter(option => 
        String(option[props.labelKey]).toLowerCase().includes(s)
    );
});

const selectedOptions = computed(() => {
    return props.options.filter(option => props.modelValue.includes(option[props.valueKey]));
});

const toggle = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        search.value = '';
    }
};

const select = (option) => {
    const value = option[props.valueKey];
    const newValue = [...props.modelValue];
    const index = newValue.indexOf(value);
    
    if (index === -1) {
        newValue.push(value);
    } else {
        newValue.splice(index, 1);
    }
    
    emit('update:modelValue', newValue);
};

const remove = (value) => {
    const newValue = props.modelValue.filter(v => v !== value);
    emit('update:modelValue', newValue);
};

const handleClickOutside = (event) => {
    if (selectRef.value && !selectRef.value.contains(event.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="relative" ref="selectRef">
        <div
            @click="toggle"
            class="relative w-full cursor-default rounded-md bg-white py-2 pl-3 pr-10 text-left border border-gray-300 shadow-sm focus-within:border-gray-500 focus-within:ring-1 focus-within:ring-gray-500 sm:text-sm dark:bg-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:focus-within:border-zinc-500 dark:focus-within:ring-zinc-500 transition-colors duration-200 min-h-[42px]"
        >
            <div class="flex flex-wrap gap-1">
                <span 
                    v-for="option in selectedOptions" 
                    :key="option[valueKey]"
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 group"
                >
                    {{ option[labelKey] }}
                    <button 
                        type="button" 
                        @click.stop="remove(option[valueKey])"
                        class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-100 transition-colors"
                    >
                        <XMarkIcon class="h-3 w-3" />
                    </button>
                </span>
                <span v-if="selectedOptions.length === 0" class="text-gray-500 dark:text-zinc-500 truncate">
                    {{ placeholder }}
                </span>
            </div>
            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                <ChevronUpDownIcon class="h-5 w-5 text-gray-400 dark:text-zinc-500" aria-hidden="true" />
            </span>
        </div>

        <transition
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isOpen"
                class="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm dark:bg-zinc-800 dark:ring-zinc-700"
            >
                <div class="sticky top-0 z-10 bg-white dark:bg-zinc-800 px-2 py-2">
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <MagnifyingGlassIcon class="h-4 w-4 text-gray-400 dark:text-zinc-500" aria-hidden="true" />
                        </div>
                        <input
                            v-model="search"
                            v-bind="$attrs"
                            type="text"
                            class="block w-full rounded-md border-gray-300 pl-10 focus:border-gray-500 focus:ring-gray-500 sm:text-sm dark:bg-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:focus:border-zinc-500"
                            placeholder="Buscar..."
                            @click.stop
                        />
                    </div>
                </div>

                <ul class="mt-1">
                    <li
                        v-for="option in filteredOptions"
                        :key="option[valueKey]"
                        @click.stop="select(option)"
                        class="relative cursor-default select-none py-2 pl-10 pr-4 text-gray-900 hover:bg-gray-100 dark:text-zinc-300 dark:hover:bg-zinc-700/50"
                    >
                        <span :class="[modelValue.includes(option[valueKey]) ? 'font-semibold text-gray-900 dark:text-white' : 'font-normal', 'block truncate']">
                            {{ option[labelKey] }}
                        </span>

                        <span
                            v-if="modelValue.includes(option[valueKey])"
                            class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-600 dark:text-zinc-400"
                        >
                            <CheckIcon class="h-5 w-5" aria-hidden="true" />
                        </span>
                    </li>
                    <li v-if="filteredOptions.length === 0" class="px-4 py-2 text-gray-500 dark:text-zinc-500 italic">
                        No se encontraron resultados
                    </li>
                </ul>
            </div>
        </transition>
    </div>
</template>
