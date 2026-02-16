<script setup>
import Card from '@/Components/Card.vue'

const props = defineProps({
    month: [String, Number],
    year: [String, Number],
    months: Array,
    aniosDisponibles: Array
})

const emit = defineEmits(['update:month', 'update:year'])
</script>

<template>
    <Card class="p-4 flex flex-col sm:flex-row items-center gap-4 bg-zinc-50/50 dark:bg-zinc-800/10">
        <div class="flex items-center gap-2">
            <span class="text-sm font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Período:</span>
            <select 
                :value="month" 
                @change="emit('update:month', $event.target.value)"
                class="rounded-md border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-sm focus:ring-gray-500 focus:border-gray-500 dark:text-zinc-200"
            >
                <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
            </select>
            <select 
                :value="year" 
                @change="emit('update:year', $event.target.value)"
                class="rounded-md border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-sm focus:ring-gray-500 focus:border-gray-500 dark:text-zinc-200"
            >
                <option v-for="y in aniosDisponibles" :key="y" :value="y">{{ y }}</option>
            </select>
        </div>
        <div class="flex-1"></div>
        <div class="text-xs text-gray-500 dark:text-zinc-500 italic">
            Mostrando datos de {{ months.find(m => m.value == month)?.label }} {{ year }}
        </div>
    </Card>
</template>
