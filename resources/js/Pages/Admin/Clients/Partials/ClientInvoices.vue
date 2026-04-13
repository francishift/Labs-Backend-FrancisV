<script setup>
import { Link } from '@inertiajs/vue3'
import Card from '@/Components/Card.vue'
import Badge from '@/Components/Badge.vue'
import { DocumentTextIcon, EyeIcon } from '@heroicons/vue/24/outline'
import { useFormatters } from '@/Composables/useFormatters'

const props = defineProps({
    facturas: {
        type: Array,
        required: true
    },
    currentUrl: String
})

const { formatCurrency, formatDate } = useFormatters()

const getStatusLabel = (item) => {
  if (item.status === 0) return 'Pendiente'
  if (item.status === 1) return 'Pagada'
  if (item.status === 2) return 'Parcial'
  if (item.status === 3) return 'Anulada'
  return 'Desconocido'
}

const getStatusColor = (item) => {
  if (item.status === 0) return 'rose'
  if (item.status === 1) return 'emerald'
  if (item.status === 2) return 'blue'
  if (item.status === 3) return 'zinc'
  return 'zinc'
}
</script>

<template>
    <Card class="p-0 overflow-hidden h-full flex flex-col">
        <div class="p-4 border-b border-gray-100 dark:border-zinc-700 bg-gray-50/50 dark:bg-zinc-800/50 flex items-center justify-between">
            <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <DocumentTextIcon class="h-5 w-5 text-emerald-500" />
                Facturas Ventas
            </h3>
            <Badge color="emerald">{{ facturas.length }}</Badge>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-zinc-700 overflow-y-auto max-h-[400px] flex-1">
            <div v-for="factura in facturas" :key="factura.id" class="p-4 hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="font-bold text-gray-900 dark:text-white text-sm">
                            {{ factura.number }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">
                            {{ formatDate(factura.date * 1000) }}
                        </p>
                        <Badge :color="getStatusColor(factura)" size="xs" class="mt-2">
                            {{ getStatusLabel(factura) }}
                        </Badge>
                    </div>
                    <div class="text-right">
                        <p class="font-black text-gray-900 dark:text-white leading-tight">{{ formatCurrency(factura.total) }}</p>
                        <Link 
                            :href="route('admin.visor-pdf', { 
                                url: route('admin.facturas.pdf', factura.id),
                                title: `Factura: ${factura.number}`,
                                backUrl: currentUrl
                            })" 
                            class="inline-flex items-center gap-1.5 text-[10px] uppercase font-bold text-emerald-600 dark:text-emerald-400 hover:text-emerald-800 dark:hover:text-emerald-200 transition-colors mt-3 px-2 py-1 rounded-md bg-emerald-50 dark:bg-emerald-900/30"
                        >
                            <EyeIcon class="h-3 w-3" />
                            PDF
                        </Link>
                    </div>
                </div>
            </div>
            <div v-if="facturas.length === 0" class="p-8 text-center text-gray-500 italic">
                No hay facturas asociadas a este cliente.
            </div>
        </div>
    </Card>
</template>
