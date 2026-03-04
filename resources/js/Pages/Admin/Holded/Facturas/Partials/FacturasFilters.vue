<script setup>
import { reactive, watch } from 'vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import SelectInput from '@/Components/SelectInput.vue'
import SearchInput from '@/Components/SearchInput.vue'
import SearchableSelect from '@/Components/SearchableSelect.vue'
import debounce from 'lodash/debounce'

const props = defineProps({
    modelValue: Object,
    search: String,
    clients: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['update:modelValue', 'update:search', 'change'])

const filters = reactive({ ...props.modelValue })
const statusOptions = [
    { value: '', label: 'Todos' },
    { value: 'pagada', label: 'Pagada' },
    { value: 'pendiente', label: 'Pendiente' },
    { value: 'parcial', label: 'Parcial' },
]

import { computed } from 'vue'
const clientsOptions = computed(() => [
    { id: '', name: 'Todos los clientes' },
    ...props.clients.map(client => ({ id: client, name: client }))
])

watch(() => props.modelValue, (newVal) => {
    Object.assign(filters, newVal)
}, { deep: true })

watch(filters, (newVal) => {
    emit('update:modelValue', { ...newVal })
    emit('change')
}, { deep: true })

const onSearchUpdate = (val) => {
    emit('update:search', val)
    emit('change')
}
</script>

<template>
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6">
        <div class="flex flex-col sm:flex-row gap-4 w-full">
            <div class="w-full sm:w-40">
                <InputLabel for="start_date" value="Fecha inicio" />
                <TextInput
                    id="start_date"
                    type="date"
                    class="mt-1 block w-full"
                    v-model="filters.start"
                />
            </div>
            <div class="w-full sm:w-40">
                <InputLabel for="end_date" value="Fecha fin" />
                <TextInput
                    id="end_date"
                    type="date"
                    class="mt-1 block w-full"
                    v-model="filters.end"
                />
            </div>
            <div class="w-full sm:w-40">
                <InputLabel for="status" value="Estado" />
                <SelectInput
                    id="status"
                    class="mt-1 block w-full"
                    v-model="filters.status"
                    :options="statusOptions"
                />
            </div>
            <div class="w-full sm:w-48 z-50">
                <InputLabel for="client" value="Cliente" />
                <SearchableSelect
                    id="client"
                    class="mt-1 block w-full"
                    v-model="filters.client"
                    :options="clientsOptions"
                    placeholder="Todos los clientes"
                />
            </div>
            <div class="w-full sm:w-64">
                <InputLabel for="search-facturas" value="Buscar" />
                <SearchInput 
                    id="search-facturas"
                    name="search"
                    placeholder="Buscar por contacto o nº..."
                    class="mt-1 block w-full"
                    :model-value="search"
                    @update:model-value="onSearchUpdate"
                />
            </div>
        </div>
    </div>
</template>
