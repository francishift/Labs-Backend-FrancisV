<script setup>
import Card from '@/Components/Card.vue'
import { useFormatters } from '@/Composables/useFormatters'

const props = defineProps({
    extensiones: Array,
    month: [String, Number],
    calculateExtensionPeriodCost: Function
})

const { formatCurrency } = useFormatters()
</script>

<template>
    <Card v-if="extensiones?.length" class="p-4 sm:p-6 overflow-hidden">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Extensiones de Terceros en Uso</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="extension in extensiones" :key="extension.id" class="p-4 border border-gray-200 dark:border-zinc-700 rounded-lg bg-gray-50 dark:bg-zinc-800/50">
                <div class="font-bold text-gray-900 dark:text-zinc-100">{{ extension.nombre }}</div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-emerald-600 dark:text-emerald-400 font-bold text-lg">
                        {{ formatCurrency(calculateExtensionPeriodCost(extension)) }}
                    </span>
                    <span class="text-[10px] text-gray-500 dark:text-zinc-500 uppercase font-bold tracking-tighter">
                        {{ month === 'all' ? 'Anual' : 'Mensual' }}
                    </span>
                </div>
            </div>
        </div>
    </Card>
</template>
