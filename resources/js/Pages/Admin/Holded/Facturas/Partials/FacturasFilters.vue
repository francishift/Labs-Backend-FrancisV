<script setup>
import { reactive, watch } from 'vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import SelectInput from '@/Components/SelectInput.vue'
import SearchInput from '@/Components/SearchInput.vue'
import SearchableSelect from '@/Components/SearchableSelect.vue'
import debounce from 'lodash/debounce'

import { ArrowPathIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    modelValue: Object,
    search: String,
    clients: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['update:modelValue', 'update:search', 'change', 'reset'])

const filters = reactive({ ...props.modelValue })
const statusOptions = [
    { value: '', label: 'Todos' },
    { value: 'pagada', label: 'Pagada' },
    { value: 'pendiente', label: 'Pendiente' },
    { value: 'parcial', label: 'Parcial' },
]

import { computed } from 'vue'
const clientsOptions = computed(() => props.clients.map(client => ({ id: String(client.id), name: client.name })))

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

const resetFilters = () => {
    emit('reset')
}
</script>

<template>
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6">
        <div class="flex flex-col md:flex-row gap-4 w-full items-end">
            <div class="space-y-1 w-full md:flex-1">
                <InputLabel for="search-facturas" value="Buscar" />
                <SearchInput 
                    id="search-facturas"
                    name="search"
                    placeholder="Buscar por contacto o nº..."
                    class="w-full"
                    :model-value="search"
                    @update:model-value="onSearchUpdate"
                />
            </div>
            <div class="space-y-1 w-full md:flex-1 z-50">
                <InputLabel for="client" value="Cliente" />
                <SearchableSelect
                    id="client"
                    class="w-full"
                    v-model="filters.client"
                    :options="clientsOptions"
                    placeholder="Todos los clientes"
                />
            </div>
            <div class="space-y-1 w-full md:w-32">
                <InputLabel for="status" value="Estado" />
                <SelectInput
                    id="status"
                    class="w-full"
                    v-model="filters.status"
                    :options="statusOptions"
                />
            </div>
            <div class="space-y-1 w-full md:w-32">
                <InputLabel for="start_date" value="Fecha inicio" />
                <TextInput
                    id="start_date"
                    type="date"
                    class="w-full"
                    v-model="filters.start"
                />
            </div>
            <div class="space-y-1 w-full md:w-32">
                <InputLabel for="end_date" value="Fecha fin" />
                <TextInput
                    id="end_date"
                    type="date"
                    class="w-full"
                    v-model="filters.end"
                />
            </div>
            
            <!-- Clear Filters Button -->
            <div class="w-full md:w-auto flex justify-end">
                <button 
                    @click="resetFilters" 
                    title="Limpiar filtros"
                    class="h-[42px] px-3 flex items-center justify-center bg-gray-100 hover:bg-gray-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-gray-500 hover:text-emerald-600 transition-colors rounded-lg border border-gray-200 dark:border-zinc-700"
                >
                    <ArrowPathIcon class="w-5 h-5" />
                </button>
            </div>
        </div>
    </div>
</template>
