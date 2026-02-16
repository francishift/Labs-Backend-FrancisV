<script setup>
import Card from '@/Components/Card.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import DataTable from '@/Components/DataTable.vue'
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { useFormatters } from '@/Composables/useFormatters'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
    servicios: {
        type: Array,
        required: true
    },
    servicesTotal: {
        type: Number,
        required: true
    }
})

const emit = defineEmits(['create', 'edit', 'delete'])

const page = usePage()
const { formatDate, formatCurrency } = useFormatters()

const columns = [
    { key: 'servicio', label: 'Servicio' },
    { key: 'fecha', label: 'Fecha' },
    { key: 'duracion_minutos', label: 'Duración', align: 'center' },
    { key: 'coste_horas', label: 'Coste Horas', align: 'right' },
    { key: 'precio', label: 'Importe Fijo', align: 'right' },
    { key: 'total', label: 'Total', align: 'right', class: 'font-bold' },
    { key: 'actions', label: '', align: 'right' }
]

const formatMinutesToHours = (minutes) => {
    const h = Math.floor(minutes / 60)
    const m = minutes % 60
    return m > 0 ? `${h}h ${m}min` : `${h}h`
}

const getHourPrice = (service) => {
    return service.precio_hora || page.props.config?.precio_hora || 0
}

const calculateServiceCost = (service) => {
    return (service.duracion_minutos / 60) * getHourPrice(service)
}

const calculateServiceTotal = (service) => {
    return calculateServiceCost(service) + parseFloat(service.precio || 0)
}
</script>

<template>
    <Card class="p-4 sm:p-6 overflow-hidden">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Servicios Realizados</h3>
            <PrimaryButton @click="emit('create')" class="flex items-center gap-2">
                <PlusIcon class="h-4 w-4" />
                Registrar Servicio
            </PrimaryButton>
        </div>

        <DataTable
            :columns="columns"
            :items="servicios"
            @row-click="(item) => emit('edit', item)"
        >
            <template #cell-servicio="{ item }">
                <div class="text-sm font-medium text-gray-900 dark:text-zinc-200">{{ item.servicio }}</div>
                <div v-if="item.descripcion" class="text-xs text-gray-500 dark:text-zinc-400 truncate max-w-xs">{{ item.descripcion }}</div>
            </template>

            <template #cell-fecha="{ item }">
                {{ formatDate(item.fecha) }}
            </template>

            <template #cell-duracion_minutos="{ item }">
                <div class="flex flex-col items-center">
                    <span>{{ formatMinutesToHours(item.duracion_minutos) }}</span>
                    <span class="text-[10px] text-gray-500">({{ formatCurrency(getHourPrice(item)) }}/h)</span>
                </div>
            </template>

            <template #cell-coste_horas="{ item }">
                <span class="text-emerald-600 dark:text-emerald-400">
                    {{ formatCurrency(calculateServiceCost(item)) }}
                </span>
            </template>

            <template #cell-precio="{ item }">
                <span v-if="item.precio > 0" class="text-emerald-600 dark:text-emerald-400">
                   <span class="whitespace-nowrap"> {{ formatCurrency(item.precio) }}</span>
                </span>
            </template>

            <template #cell-total="{ item }">
                <span class="text-gray-900 dark:text-zinc-200">
                   <span class="whitespace-nowrap"> {{ formatCurrency(calculateServiceTotal(item)) }}</span>
                </span>
            </template>

            <template #cell-actions="{ item }">
                <div class="flex justify-end gap-2">
                    <SecondaryButton @click.stop="emit('edit', item)" title="Editar servicio">
                        <PencilIcon class="h-4 w-4" />
                    </SecondaryButton>
                    <DangerButton @click.stop="emit('delete', item)" title="Eliminar servicio">
                        <TrashIcon class="h-4 w-4" />
                    </DangerButton>
                </div>
            </template>

            <template #footer>
                <tr>
                    <td :colspan="columns.length" class="px-6 py-4 text-left sm:text-right">
                        <div class="inline-flex flex-col sm:flex-row sm:items-baseline sm:justify-end gap-x-3">
                            <span class="text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-widest">Subtotal Servicios</span>
                            <span class="text-lg font-black text-emerald-600 dark:text-emerald-400 leading-none">{{ formatCurrency(servicesTotal) }}</span>
                        </div>
                    </td>
                </tr>
            </template>
        </DataTable>
    </Card>
</template>
