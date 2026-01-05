<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { ChevronUpDownIcon, MagnifyingGlassIcon, CheckIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    modelValue: [String, Number],
    options: {
        type: Array,
        required: true,
        // Expected: [{ id: 1, name: 'Option 1' }]
    },
    placeholder: {
        type: String,
        default: 'Seleccionar opción...',
    },
    labelKey: {
        type: String,
        default: 'name',
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

const selectedOption = computed(() => {
    return props.options.find(option => option[props.valueKey] === props.modelValue);
});

const toggle = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        search.value = '';
    }
};

const select = (option) => {
    emit('update:modelValue', option[props.valueKey]);
    isOpen.value = false;
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
        <button
            type="button"
            @click="toggle"
            class="relative w-full cursor-default rounded-md bg-white py-2 pl-3 pr-10 text-left border border-gray-300 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500 sm:text-sm dark:bg-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:focus:border-zinc-500 dark:focus:ring-zinc-500 transition-colors duration-200"
        >
            <span class="block truncate" :class="{ 'text-gray-500 dark:text-zinc-500': !selectedOption }">
                {{ selectedOption ? selectedOption[labelKey] : placeholder }}
            </span>
            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                <ChevronUpDownIcon class="h-5 w-5 text-gray-400 dark:text-zinc-500" aria-hidden="true" />
            </span>
        </button>

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
                        @click="select(option)"
                        class="relative cursor-default select-none py-2 pl-10 pr-4 text-gray-900 hover:bg-gray-100 dark:text-zinc-300 dark:hover:bg-zinc-700/50"
                    >
                        <span :class="[option[valueKey] === modelValue ? 'font-semibold text-gray-900 dark:text-white' : 'font-normal', 'block truncate']">
                            {{ option[labelKey] }}
                        </span>

                        <span
                            v-if="option[valueKey] === modelValue"
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
