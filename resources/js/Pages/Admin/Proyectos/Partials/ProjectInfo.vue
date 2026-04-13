<script setup>
import Card from '@/Components/Card.vue'
import Badge from '@/Components/Badge.vue'
import { DocumentTextIcon } from '@heroicons/vue/24/outline'
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

const { formatDate, formatCurrency } = useFormatters()

const getStatusVariant = (status) => {
    switch (status) {
        case 'En proceso': return 'green'
        case 'Finalizado': return 'red'
        case 'Cancelado': return 'gray'
        default: return 'gray'
    }
}
</script>

<template>
    <Card class="lg:col-span-2 p-4 sm:p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Información General</h3>
            <Badge :variant="getStatusVariant(proyecto.estado)">
                {{ proyecto.estado }}
            </Badge>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-y-4 gap-x-8">
            <!-- Fila 1 -->
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Cliente</p>
                <p class="text-base text-gray-900 dark:text-zinc-200">{{ proyecto.client?.name || 'S/N' }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Presupuesto</p>
                <p class="text-xl font-black text-emerald-900 dark:text-emerald-200">{{ formatCurrency(proyecto.presupuesto) }}</p>
                <div v-if="proyecto.presupuesto_asociado" class="mt-1">
                        <a :href="route('admin.visor-pdf', { 
                            url: route('admin.presupuestos.pdf', { presupuesto: proyecto.presupuesto_asociado.id }),
                            title: `Presupuesto: ${proyecto.presupuesto_asociado.number || proyecto.presupuesto_asociado.raw_data?.docNumber || proyecto.presupuesto_asociado.id}`,
                            backUrl: route('admin.proyectos.show', proyecto.id)
                        })" 
                        class="inline-flex items-center gap-1 text-xs text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300 font-medium transition-colors"
                        title="Ver Presupuesto PDF"
                        >
                        <DocumentTextIcon class="h-3 w-3" />
                        Ref: {{ proyecto.presupuesto_asociado.number || proyecto.presupuesto_asociado.raw_data?.docNumber || 'Ver PDF' }}
                        </a>
                </div>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Total Facturado (imp. incluidos)</p>
                <p class="text-xl font-black text-blue-900 dark:text-blue-200">{{ formatCurrency(stats.totalFacturado) }}</p>
                <p v-if="proyecto.presupuesto > 0" class="text-xs font-medium" :class="stats.totalFacturadoNeto >= proyecto.presupuesto ? 'text-green-600' : 'text-gray-500'">
                    {{ ((stats.totalFacturadoNeto / proyecto.presupuesto) * 100).toFixed(1) }}% cubierto
                </p>
            </div>

            <!-- Fila 2 -->
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Fecha de Inicio</p>
                <p class="text-base text-gray-900 dark:text-zinc-200">{{ formatDate(proyecto.fecha_inicio) }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Fecha de Fin</p>
                <p class="text-base text-gray-900 dark:text-zinc-200">{{ proyecto.fecha_fin ? formatDate(proyecto.fecha_fin) : 'Pendiente' }}</p>
            </div>
            <div><!-- Espacio vacío 3ra columna --></div>

            <div class="md:col-span-2">
                <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Descripción</p>
                <p class="text-base text-gray-900 dark:text-zinc-200 whitespace-pre-wrap">{{ proyecto.descripcion || 'Sin descripción' }}</p>
            </div>
            <div class="md:col-span-3 lg:col-span-3" v-if="proyecto.facturas?.length">
                <p class="text-sm font-medium text-gray-500 dark:text-zinc-400 mb-2">Facturas Asociadas</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <a 
                        v-for="factura in proyecto.facturas" 
                        :key="factura.id" 
                        :href="route('admin.visor-pdf', { 
                            url: route('admin.facturas.pdf', factura.id),
                            title: `Factura: ${factura.number}`,
                            backUrl: route('admin.proyectos.show', proyecto.id)
                        })"
                        class="flex items-center justify-between p-2 bg-gray-50 dark:bg-zinc-800 rounded border border-gray-200 dark:border-zinc-700 hover:bg-emerald-50 dark:hover:bg-zinc-700 transition-colors cursor-pointer group"
                        :title="'Ver PDF Factura ' + factura.number"
                    >
                        <div class="flex flex-col">
                            <span class="text-sm font-medium text-gray-900 dark:text-zinc-200 group-hover:text-emerald-700 dark:group-hover:text-emerald-300 transition-colors">
                                {{ factura.number }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-zinc-400">
                                {{ formatDate(factura.date * 1000) }} • {{ formatCurrency(factura.total) }}
                            </span>
                        </div>
                        <div class="p-1 text-gray-400 group-hover:text-emerald-600 dark:text-zinc-500 dark:group-hover:text-emerald-400 transition-colors">
                            <DocumentTextIcon class="h-5 w-5" />
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </Card>
</template>
