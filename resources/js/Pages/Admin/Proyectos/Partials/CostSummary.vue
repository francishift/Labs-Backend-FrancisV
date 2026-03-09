<script setup>
import Card from '@/Components/Card.vue'
import { useFormatters } from '@/Composables/useFormatters'

const props = defineProps({
    proyecto: {
        type: Object,
        required: true
    },
    stats: {
        type: Object,
        required: true
    }
})

const { formatCurrency } = useFormatters()
</script>

<template>
    <Card class="p-4 sm:p-6 bg-emerald-50 dark:bg-zinc-800/20 border-emerald-100 dark:border-emerald-800/50 h-fit">
        <h3 class="text-lg font-bold text-emerald-900 dark:text-emerald-400 mb-4">Resumen de Costes</h3>
        <div class="space-y-3">
            <div class="flex justify-between items-center text-sm">
                <span class="text-emerald-700 dark:text-emerald-300">Total Horas</span>
                <span class="font-medium text-emerald-900 dark:text-emerald-200 text-right">{{ stats.formattedTime }}</span>
            </div>
            <div class="flex justify-between items-center text-sm border-b border-emerald-100 dark:border-emerald-800/50 pb-2">
                <span class="text-emerald-700 dark:text-emerald-300 font-bold italic">Total €/hora</span>
                <span class="font-bold text-emerald-900 dark:text-emerald-200 text-right">{{ formatCurrency(stats.hoursCostTotal) }}</span>
            </div>
            <div v-if="stats.hasFixedCost" class="flex justify-between items-center text-sm">
                <span class="text-emerald-700 dark:text-emerald-300">Servicios (Fijo)</span>
                <span class="font-medium text-emerald-900 dark:text-emerald-200 text-right">{{ formatCurrency(stats.fixedCostTotal) }}</span>
            </div>
            <div class="flex justify-between items-center text-sm">
                <span class="text-emerald-700 dark:text-emerald-300">Gasto extensiones</span>
                <span class="font-medium text-emerald-900 dark:text-emerald-200 text-right">{{ formatCurrency(stats.extensionsTotal) }}</span>
            </div>
            <div class="flex justify-between items-center text-sm">
                <span class="text-emerald-700 dark:text-emerald-300">Gasto Software / Hosting</span>
                <span class="font-medium text-emerald-900 dark:text-emerald-200 text-right">{{ formatCurrency(stats.costeSoftware) }}</span>
            </div>
            <div class="border-t border-emerald-200 dark:border-emerald-800 pt-3 flex justify-between items-center">
                <span class="text-base font-bold text-emerald-900 dark:text-emerald-400">Coste</span>
                <span class="text-xl font-black text-emerald-900 dark:text-emerald-200 text-right">{{ formatCurrency(stats.grandTotal) }}</span>
            </div>
            <div v-if="proyecto.presupuesto > 0" class="mt-4">
                <p class="text-xs font-semibold text-emerald-700 dark:text-emerald-300 uppercase tracking-wider mb-2">Utilización del Presupuesto</p>
                <div class="w-full bg-emerald-200 dark:bg-emerald-800 rounded-full h-2.5">
                    <div 
                        class="h-2.5 rounded-full" 
                        :class="stats.grandTotal > proyecto.presupuesto ? 'bg-red-500' : 'bg-emerald-600'" 
                        :style="{ width: Math.min((stats.grandTotal / proyecto.presupuesto) * 100, 100) + '%' }"
                    ></div>
                </div>
                <p class="text-right text-xs mt-1 text-emerald-700 dark:text-emerald-300">{{ ((stats.grandTotal / proyecto.presupuesto) * 100).toFixed(1) }}%</p>
            </div>
        </div>
    </Card>
</template>
