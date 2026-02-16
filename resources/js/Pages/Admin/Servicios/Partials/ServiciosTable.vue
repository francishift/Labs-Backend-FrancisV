<script setup>
import DataTable from '@/Components/DataTable.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import { PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { useFormatters } from '@/Composables/useFormatters'

const props = defineProps({
    items: Array
})

const emit = defineEmits(['row-click', 'edit', 'delete'])

const { formatDate, formatCurrency, formatDuration } = useFormatters()

const columns = [
  { key: 'fecha', label: 'Fecha' },
  { key: 'servicio', label: 'Servicio' },
  { key: 'proyecto', label: 'Proyecto' },
  { key: 'duracion', label: 'Duración' },
  { key: 'precio', label: 'Precio' },
  { key: 'actions', label: 'Acciones', align: 'right' },
]
</script>

<template>
    <DataTable
        :columns="columns"
        :items="items"
        @row-click="(item) => emit('row-click', item)"
        hoverable
    >
        <template #cell-proyecto="{ item }">
            {{ item.proyecto?.proyecto || 'S/N' }}
        </template>
        
        <template #cell-fecha="{ item }">
            {{ formatDate(item.fecha) }}
        </template>
        
        <template #cell-duracion="{ item }">
            {{ formatDuration(item.duracion_minutos) }}
        </template>
        
        <template #cell-precio="{ item }">
            {{ formatCurrency(item.precio) }}
        </template>
        
        <template #cell-actions="{ item }">
            <div class="flex justify-end gap-2">
                <SecondaryButton @click.stop="emit('edit', item)">
                    <PencilIcon class="h-4 w-4" />
                </SecondaryButton>
                <DangerButton @click.stop="emit('delete', item)">
                    <TrashIcon class="h-4 w-4" />
                </DangerButton>
            </div>
        </template>
    </DataTable>
</template>
