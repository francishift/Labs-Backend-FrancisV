<script setup>
import DataTable from '@/Components/DataTable.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import { Link } from '@inertiajs/vue3'
import { PencilIcon, TrashIcon, EyeIcon } from '@heroicons/vue/24/outline'
import { useFormatters } from '@/Composables/useFormatters'

const props = defineProps({
    items: Array
})

const emit = defineEmits(['row-click', 'edit', 'delete'])

const { formatDate } = useFormatters()

const formatMinutesToHours = (minutes) => {
    const h = Math.floor(minutes / 60)
    const m = minutes % 60
    return m > 0 ? `${h}h ${m}min` : `${h}h`
}

const columns = [
    { key: 'aplicacion', label: 'Mantenimiento' },
    { key: 'fecha', label: 'Fecha' },
    { key: 'descripcion', label: 'Descripción' },
    { key: 'duracion_minutos', label: 'Duración', align: 'center' },
    { key: 'actions', label: '', align: 'right' },
]
</script>

<template>
    <DataTable :columns="columns" :items="items" @row-click="(item) => emit('row-click', item)" hoverable>
        <template #cell-aplicacion="{ item }">
            <div class="flex flex-col">
                <span class="text-gray-900 dark:text-zinc-100 font-medium">{{ item.mantenimiento?.aplicacion || 'N/A' }}</span>
                <span class="text-xs text-gray-500 dark:text-zinc-400">{{ item.mantenimiento?.cliente?.name || 'S/N' }}</span>
            </div>
        </template>

        <template #cell-descripcion="{ item }">
            <span class="text-sm truncate max-w-xs block" :title="item.descripcion">{{ item.descripcion }}</span>
        </template>

        <template #cell-duracion_minutos="{ item }">
            <span class="text-sm font-medium">{{ formatMinutesToHours(item.duracion_minutos) }}</span>
        </template>

        <template #cell-fecha="{ item }">
            <span class="text-sm text-gray-900 dark:text-zinc-300">{{ formatDate(item.fecha) }}</span>
        </template>

        <template #cell-actions="{ item }">
            <div class="flex justify-end gap-2">
                <Link :href="route('admin.mantenimientos.show', item.mantenimiento_id)" @click.stop>
                    <SecondaryButton title="Ver Mantenimiento">
                        <EyeIcon class="h-4 w-4" />
                    </SecondaryButton>
                </Link>
                <SecondaryButton @click.stop="emit('edit', item)" title="Editar">
                    <PencilIcon class="h-4 w-4" />
                </SecondaryButton>
                <DangerButton @click.stop="emit('delete', item)" title="Eliminar">
                    <TrashIcon class="h-4 w-4" />
                </DangerButton>
            </div>
        </template>
    </DataTable>
</template>
