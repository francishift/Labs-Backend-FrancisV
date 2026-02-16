<script setup>
import Card from '@/Components/Card.vue'
import { CurrencyEuroIcon } from '@heroicons/vue/24/outline'
import { useFormatters } from '@/Composables/useFormatters'

const props = defineProps({
    stats: {
        type: Object,
        required: true
    }
})

const { formatCurrency } = useFormatters()
</script>

<template>
    <Card class="p-4 bg-emerald-50 dark:bg-emerald-600/20 text-gray-900 dark:text-white border-none">
        <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
            <CurrencyEuroIcon class="h-5 w-5 text-emerald-600" />
            Resumen Financiero
        </h3>
        <div class="space-y-4">
            <div>
                <p class="text-xs uppercase font-bold text-gray-500 dark:text-emerald-400/60">Presupuesto Proyectos Proceso</p>
                <p class="text-2xl font-black">{{ formatCurrency(stats.active_projects_budget) }}</p>
            </div>
            <div>
                <p class="text-xs uppercase font-bold text-gray-500 dark:text-emerald-400/60">Ingreso Mensual Mant.</p>
                <p class="text-2xl font-black">
                    {{ formatCurrency(stats.monthly_maintenance_income) }}
                </p>
            </div>
        </div>
    </Card>
</template>
