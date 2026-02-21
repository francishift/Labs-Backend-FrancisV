<script>
export default {
    inheritAttrs: false
}
</script>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { ChevronUpDownIcon, CheckIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    modelValue: [String, Number],
    options: {
        type: Array,
        required: true,
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
const query = ref('');
const selectRef = ref(null);
const inputRef = ref(null);

const selectedOption = computed(() => {
    return (props.options || []).find(option => option[props.valueKey] === props.modelValue);
});

// Actualizar query cuando cambia selectedOption (maneja cambios en modelValue y carga asíncrona)
watch(selectedOption, (newOption) => {
    // Solo actualizar query si hay coincidencia, o si queremos limpiarlo (y no estamos buscando/tecleando potencialmente)
    // Como es un comportamiento de selector, si el modelValue coincide con una opción, el input debe mostrar la etiqueta de esa opción.
    if (newOption) {
        query.value = newOption[props.labelKey];
    } else {
        // Si no se encuentra coincidencia (ej. selección limpiada o opción no en lista), limpiar query
        // ¿Pero respetar si el usuario está escribiendo? No, porque escribir actualiza la consulta pero NO el modelValue.
        // modelValue solo se actualiza al seleccionar. Así que si selectedOption es nulo, significa que modelValue es nulo (o inválido).
        query.value = '';
    }
}, { immediate: true });

const filteredOptions = computed(() => {
    const s = query.value.toLowerCase();
    // If query matches the current selected label exactly, show all options (for quick re-selection)
    if (selectedOption.value && selectedOption.value[props.labelKey] === query.value) {
        return props.options;
    }
    
    if (!query.value) return props.options;
    
    return props.options.filter(option => 
        String(option[props.labelKey]).toLowerCase().includes(s)
    );
});

const toggle = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        inputRef.value?.focus();
    }
};

const onInput = (e) => {
    isOpen.value = true;
    query.value = e.target.value;
};

const select = (option) => {
    emit('update:modelValue', option[props.valueKey]);
    query.value = option[props.labelKey];
    isOpen.value = false;
};

const onBlur = () => {
    // Pequeño retardo para permitir que se disparen los eventos de clic en la lista
    setTimeout(() => {
        isOpen.value = false;
        // Restore label if no valid selection was made or input was cleared
        if (!selectedOption.value || query.value !== selectedOption.value[props.labelKey]) {
            query.value = selectedOption.value ? selectedOption.value[props.labelKey] : '';
        }
    }, 200);
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
        <div class="relative">
            <input
                ref="inputRef"
                type="text"
                v-bind="$attrs"
                autocomplete="off"
                class="w-full rounded-lg border-gray-300 py-2 pl-3 pr-10 text-left shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500 sm:text-sm dark:bg-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:focus:border-zinc-500 dark:focus:ring-zinc-500 transition-all duration-200"
                :placeholder="placeholder"
                :value="query"
                @input="onInput"
                @focus="isOpen = true"
                @blur="onBlur"
            />
            <button
                type="button"
                @click="toggle"
                class="absolute inset-y-0 right-0 flex items-center pr-2"
            >
                <ChevronUpDownIcon class="h-5 w-5 text-gray-400 dark:text-zinc-500 hover:text-gray-600 dark:hover:text-zinc-300 transition-colors" aria-hidden="true" />
            </button>
        </div>

        <transition
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <ul
                v-if="isOpen && filteredOptions.length > 0"
                class="absolute z-[100] mt-1 max-h-60 w-full overflow-auto rounded-xl bg-white py-1 text-base shadow-xl ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm dark:bg-zinc-800 dark:ring-zinc-700 custom-scrollbar border border-gray-100 dark:border-zinc-700"
            >
                <li
                    v-for="option in filteredOptions"
                    :key="option[valueKey]"
                    @mousedown="select(option)"
                    class="relative cursor-pointer select-none py-2.5 pl-10 pr-4 text-gray-900 hover:bg-emerald-50 dark:text-zinc-300 dark:hover:bg-emerald-900/20 group transition-colors"
                >
                    <span :class="[option[valueKey] === modelValue ? 'font-bold text-emerald-600 dark:text-emerald-400' : 'font-normal', 'block truncate group-hover:text-emerald-700 dark:group-hover:text-emerald-300']">
                        {{ option[labelKey] }}
                    </span>

                    <span
                        v-if="option[valueKey] === modelValue"
                        class="absolute inset-y-0 left-0 flex items-center pl-3 text-emerald-600 dark:text-emerald-400"
                    >
                        <CheckIcon class="h-5 w-5" aria-hidden="true" />
                    </span>
                </li>
            </ul>
            <div 
                v-else-if="isOpen && query" 
                class="absolute z-50 mt-1 w-full rounded-xl bg-white p-4 text-sm text-gray-500 dark:bg-zinc-800 dark:text-zinc-500 italic shadow-xl border border-gray-100 dark:border-zinc-700"
            >
                No se encontraron resultados para "{{ query }}"
            </div>
            <div 
                v-else-if="isOpen && options.length === 0" 
                class="absolute z-50 mt-1 w-full rounded-xl bg-white p-4 text-sm text-gray-500 dark:bg-zinc-800 dark:text-zinc-500 italic shadow-xl border border-gray-100 dark:border-zinc-700"
            >
                No hay opciones disponibles.
            </div>
        </transition>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #3f3f46;
}
</style>
