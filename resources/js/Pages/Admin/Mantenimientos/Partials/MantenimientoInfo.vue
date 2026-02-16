<script setup>
import Card from '@/Components/Card.vue'
import Badge from '@/Components/Badge.vue'
import { useFormatters } from '@/Composables/useFormatters'

const props = defineProps({
    mantenimiento: Object,
    totalIncome: Number,
    stats: Object
})

const { formatDate, formatCurrency } = useFormatters()

const getStatusVariant = (status) => {
    switch (status) {
        case 'en curso': return 'green'
        case 'finalizado': return 'red'
        default: return 'gray'
    }
}
</script>

<template>
    <Card class="lg:col-span-2 p-4 sm:p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Información del Mantenimiento</h3>
            <Badge :variant="getStatusVariant(mantenimiento.estado)">
                {{ mantenimiento.estado }}
            </Badge>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Cliente</p>
                <p class="text-base text-gray-900 dark:text-zinc-200">{{ mantenimiento.cliente?.name || 'S/N' }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Importe del Período</p>
                <p class="text-xl font-black text-emerald-900 dark:text-emerald-200">{{ formatCurrency(totalIncome) }} <span class="text-xs font-normal opacity-70">({{ stats.tipo_pago }})</span></p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Fecha de Inicio</p>
                <p class="text-base text-gray-900 dark:text-zinc-200">{{ formatDate(mantenimiento.fecha_inicio) }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Fecha de Fin</p>
                <p class="text-base text-gray-900 dark:text-zinc-200">{{ mantenimiento.fecha_fin ? formatDate(mantenimiento.fecha_fin) : 'Activo' }}</p>
            </div>
            <div class="md:col-span-2">
                <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Descripción</p>
                <p class="text-base text-gray-900 dark:text-zinc-200 whitespace-pre-wrap">{{ mantenimiento.descripcion || 'Sin descripción' }}</p>
            </div>
        </div>
    </Card>
</template>
