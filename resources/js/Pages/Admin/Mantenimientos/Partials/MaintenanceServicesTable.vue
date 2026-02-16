<script setup>
import Card from '@/Components/Card.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import DataTable from '@/Components/DataTable.vue'
import Pagination from '@/Components/Pagination.vue'
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { useFormatters } from '@/Composables/useFormatters'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
    servicios: Object,
    totalCost: Number,
    monthLabel: String,
    year: [String, Number],
    mantenimiento: Object,
    precioHoraConDescuento: Number
})

const emit = defineEmits(['create', 'edit', 'delete'])

const page = usePage()
const { formatDate, formatCurrency } = useFormatters()

const columns = [
    { key: 'descripcion', label: 'Descripción' },
    { key: 'fecha', label: 'Fecha' },
    { key: 'duracion_minutos', label: 'Duración', align: 'center' },
    { key: 'coste_est', label: 'Coste Est.', align: 'right', class: 'font-medium' },
    { key: 'actions', label: '', align: 'right' }
]

const formatMinutesToHours = (minutes) => {
    const h = Math.floor(minutes / 60)
    const m = minutes % 60
    return m > 0 ? `${h}h ${m}min` : `${h}h`
}

const getHourPrice = (service) => {
    return service.precio_hora || props.mantenimiento.precio_hora || props.precioHoraConDescuento
}

const calculateServiceCost = (service) => {
    return (service.duracion_minutos / 60) * getHourPrice(service)
}
</script>

<template>
    <Card class="p-4 sm:p-6 overflow-hidden">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                Servicios Mantenimiento {{ monthLabel }} {{ year }}
            </h3>
            <PrimaryButton @click="emit('create')" class="flex items-center gap-2">
                <PlusIcon class="h-4 w-4" />
                Registrar Servicio
            </PrimaryButton>
        </div>

        <DataTable
            :columns="columns"
            :items="servicios.data"
            @row-click="(item) => emit('edit', item)"
        >
            <template #cell-descripcion="{ item }">
                <div class="text-sm text-gray-900 dark:text-zinc-200 whitespace-pre-wrap">{{ item.descripcion }}</div>
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

            <template #cell-coste_est="{ item }">
                <span class="text-red-600 dark:text-red-400">
                    {{ formatCurrency(calculateServiceCost(item)) }}
                </span>
            </template>

            <template #cell-actions="{ item }">
                <div class="flex justify-end gap-2 text-nowrap">
                    <SecondaryButton @click.stop="emit('edit', item)" title="Editar">
                        <PencilIcon class="h-4 w-4" />
                    </SecondaryButton>
                    <DangerButton @click.stop="emit('delete', item)" title="Eliminar">
                        <TrashIcon class="h-4 w-4" />
                    </DangerButton>
                </div>
            </template>

            <template #footer>
                <tr>
                    <td colspan="5" class="px-6 py-4 text-left sm:text-right">
                        <div class="inline-flex flex-col sm:flex-row sm:items-baseline sm:justify-end gap-x-3">
                            <span class="text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-widest">Total Coste Servicios</span>
                            <span class="text-lg font-black text-red-600 dark:text-red-400 leading-none">{{ formatCurrency(totalCost) }}</span>
                        </div>
                    </td>
                </tr>
            </template>
        </DataTable>

        <div class="mt-4">
            <Pagination v-if="servicios.links?.length > 3" :links="servicios.links" prefetch />
        </div>
    </Card>
</template>
