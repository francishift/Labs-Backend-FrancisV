<script setup>
import { Link } from '@inertiajs/vue3'
import Card from '@/Components/Card.vue'
import Badge from '@/Components/Badge.vue'
import { DocumentTextIcon, EyeIcon } from '@heroicons/vue/24/outline'
import { useFormatters } from '@/Composables/useFormatters'

const props = defineProps({
    presupuestos: {
        type: Array,
        required: true
    },
    currentUrl: String
})

const { formatCurrency, formatDate } = useFormatters()

const getStatusColor = (status) => {
    const colors = {
        '0': 'amber', // Pendiente
        '1': 'emerald', // Aprobado
        '2': 'zinc', // Anulado
        '3': 'rose', // Rechazado
    }
    return colors[status] || 'zinc'
}

const getStatusLabel = (status) => {
    const labels = {
        '0': 'Pendiente',
        '1': 'Aprobado',
        '2': 'Anulado',
        '3': 'Rechazado',
    }
    return labels[status] || 'Desconocido'
}
</script>

<template>
    <Card class="p-0 overflow-hidden h-full flex flex-col">
        <div class="p-4 border-b border-gray-100 dark:border-zinc-700 bg-gray-50/50 dark:bg-zinc-800/50 flex items-center justify-between">
            <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                Presupuestos
            </h3>
            <Badge color="blue">{{ presupuestos.length }}</Badge>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-zinc-700 overflow-y-auto max-h-[400px] flex-1">
            <div v-for="presu in presupuestos" :key="presu.id" class="p-4 hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="font-bold text-gray-900 dark:text-white text-sm">
                            {{ presu.number || presu.raw_data?.docNumber || 'Nº Desconocido' }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">
                            {{ formatDate(presu.date * 1000) }}
                        </p>
                        <Badge :color="getStatusColor(presu.status)" size="xs" class="mt-2">
                            {{ getStatusLabel(presu.status) }}
                        </Badge>
                    </div>
                    <div class="text-right">
                        <p class="font-black text-gray-900 dark:text-white leading-tight">{{ formatCurrency(presu.total) }}</p>
                        <Link 
                            :href="route('admin.visor-pdf', { 
                                url: route('admin.presupuestos.pdf', presu.id),
                                title: `Presupuesto: ${presu.number || presu.raw_data?.docNumber || presu.id}`,
                                backUrl: currentUrl
                            })" 
                            class="inline-flex items-center gap-1.5 text-[10px] uppercase font-bold text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200 transition-colors mt-3 px-2 py-1 rounded-md bg-blue-50 dark:bg-blue-900/30"
                        >
                            <EyeIcon class="h-3 w-3" />
                            PDF
                        </Link>
                    </div>
                </div>
            </div>
            <div v-if="presupuestos.length === 0" class="p-8 text-center text-gray-500 italic">
                No hay presupuestos asociados a este cliente.
            </div>
        </div>
    </Card>
</template>
