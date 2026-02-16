<script setup>
import Card from '@/Components/Card.vue'
import { useFormatters } from '@/Composables/useFormatters'

const props = defineProps({
    totalIncome: Number,
    totalExtensionsCost: Number,
    totalSoftwareCost: Number,
    totalCost: Number,
    balance: Number
})

const { formatCurrency } = useFormatters()
</script>

<template>
    <Card class="p-4 sm:p-6 bg-zinc-50 dark:bg-zinc-800/20 border-zinc-100 dark:border-zinc-800/50">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Balance del Período</h3>
        <div class="space-y-3">
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-600 dark:text-zinc-400">Ingresos (Cuota)</span>
                <span class="font-medium text-emerald-600 dark:text-emerald-400">{{ formatCurrency(totalIncome) }}</span>
            </div>
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-600 dark:text-zinc-400">Coste Extensiones</span>
                <span class="font-medium text-red-600 dark:text-red-400">{{ formatCurrency(totalExtensionsCost) }}</span>
            </div>
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-600 dark:text-zinc-400">Coste Software / Hosting</span>
                <span class="font-medium text-red-600 dark:text-red-400">{{ formatCurrency(totalSoftwareCost) }}</span>
            </div>
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-600 dark:text-zinc-400">Coste Servicios</span>
                <span class="font-medium text-red-600 dark:text-red-400">{{ formatCurrency(totalCost) }}</span>
            </div>
            <div class="border-t border-gray-200 dark:border-zinc-700 pt-3 flex justify-between items-center">
                <span class="text-base font-bold text-gray-900 dark:text-white">Balance Neto</span>
                <span class="text-xl font-black" :class="balance >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
                    {{ formatCurrency(balance) }}
                </span>
            </div>
            <div v-if="totalIncome > 0" class="mt-4">
                <p class="text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-2">Rentabilidad</p>
                <div class="w-full bg-gray-200 dark:bg-zinc-800 rounded-full h-2.5">
                    <div 
                        class="h-2.5 rounded-full" 
                        :class="balance < 0 ? 'bg-red-500' : 'bg-emerald-600'" 
                        :style="{ width: Math.min((Math.abs(balance) / totalIncome) * 100, 100) + '%' }"
                    ></div>
                </div>
                <p class="text-right text-xs mt-1" :class="balance < 0 ? 'text-red-600' : 'text-emerald-600'">
                    Margen: {{ ((balance / totalIncome) * 100).toFixed(1) }}% de la cuota
                </p>
            </div>
        </div>
    </Card>
</template>
