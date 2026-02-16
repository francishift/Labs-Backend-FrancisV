<script setup>
import { Link } from '@inertiajs/vue3'
import Card from '@/Components/Card.vue'
import Badge from '@/Components/Badge.vue'
import { ClockIcon, CalendarIcon } from '@heroicons/vue/24/outline'
import { useFormatters } from '@/Composables/useFormatters'

const props = defineProps({
    maintenance: {
        type: Array,
        required: true
    }
})

const { formatCurrency, formatDate } = useFormatters()

const getStatusColor = (status) => {
    const colors = {
        'en curso': 'emerald',
        'pendiente': 'amber',
        'cancelado': 'rose'
    }
    return colors[status] || 'zinc'
}
</script>

<template>
    <Card class="p-0 overflow-hidden">
        <div class="p-4 border-b border-gray-100 dark:border-zinc-700 bg-gray-50/50 dark:bg-zinc-800/50 flex items-center justify-between">
            <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <ClockIcon class="h-5 w-5 text-emerald-600" />
                Mantenimientos Activos
            </h3>
            <Badge color="emerald">{{ maintenance.length }}</Badge>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-zinc-700">
            <div v-for="mante in maintenance" :key="mante.id" class="p-4 hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors">
                <div class="flex justify-between items-start mb-1">
                    <Link :href="route('admin.mantenimientos.show', mante.id)" class="font-bold text-gray-900 dark:text-white hover:text-emerald-600 transition-colors">
                        {{ mante.aplicacion }}
                    </Link>
                    <div class="text-right">
                        <p class="font-black text-gray-900 dark:text-white">{{ formatCurrency(mante.importe) }}</p>
                        <p class="text-[10px] uppercase font-bold text-gray-400">{{ mante.tipo_pago }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-zinc-400">
                    <span class="flex items-center gap-1">
                        <CalendarIcon class="h-3 w-3" />
                        Desde: {{ formatDate(mante.fecha_inicio) }}
                    </span>
                    <Badge :color="getStatusColor(mante.estado)" size="xs">{{ mante.estado }}</Badge>
                </div>
            </div>
            <div v-if="maintenance.length === 0" class="p-8 text-center text-gray-500 italic">
                No hay servicios de mantenimiento contratados.
            </div>
        </div>
    </Card>
</template>
