<script setup>
import { ref, watch, onMounted } from 'vue';

const props = defineProps({
    modelValue: [Number, String],
    id: String,
    placeholder: String,
    required: Boolean,
});

const emit = defineEmits(['update:modelValue']);

const displayValue = ref('');
const input = ref(null);

// Formatea un número a formato español: 3.900,50
const formatToSpanish = (value) => {
    if (value === null || value === undefined || value === '') return '';
    const number = typeof value === 'string' ? parseFloat(value.replace(',', '.')) : value;
    if (isNaN(number)) return '';
    
    return new Intl.NumberFormat('es-ES', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(number);
};

// Limpia el string para obtener un número decimal de JS: 3900.50
const normalizeToNumber = (value) => {
    if (!value) return null;
    // Elimina puntos de miles y cambia coma por punto
    const cleanValue = value.toString().replace(/\./g, '').replace(',', '.');
    const number = parseFloat(cleanValue);
    return isNaN(number) ? null : number;
};

// Al montar o cuando cambie el modelo desde fuera, actualizamos lo que se ve
watch(() => props.modelValue, (newVal) => {
    const normalizedNew = normalizeToNumber(newVal);
    const normalizedCurrent = normalizeToNumber(displayValue.value);
    
    if (normalizedNew !== normalizedCurrent) {
        displayValue.value = formatToSpanish(newVal);
    }
}, { immediate: true });

const handleInput = (e) => {
    const value = e.target.value;
    displayValue.value = value;
    emit('update:modelValue', normalizeToNumber(value));
};

const handleBlur = () => {
    displayValue.value = formatToSpanish(normalizeToNumber(displayValue.value));
};

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <div class="relative">
        <input
            :id="id"
            ref="input"
            type="text"
            :value="displayValue"
            @input="handleInput"
            @blur="handleBlur"
            :placeholder="placeholder"
            :required="required"
            class="rounded-md border-gray-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 shadow-sm focus:border-zinc-500 focus:ring-zinc-500 dark:[color-scheme:dark] block w-full pr-8"
        />
        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
            <span class="text-gray-500 sm:text-sm">€</span>
        </div>
    </div>
</template>
