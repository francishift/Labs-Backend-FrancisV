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
    },
    totals: Object
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

const setDateRange = (rangeType) => {
    const today = new Date();
    if (rangeType === 'thisYear') {
        filters.start = new Date(today.getFullYear(), 0, 2).toISOString().split('T')[0];
        filters.end = new Date(today.getFullYear(), 11, 31).toISOString().split('T')[0];
    } else if (rangeType === 'lastYear') {
        filters.start = new Date(today.getFullYear() - 1, 0, 2).toISOString().split('T')[0];
        filters.end = new Date(today.getFullYear() - 1, 11, 31).toISOString().split('T')[0];
    } else if (rangeType === 'last12Months') {
        const lastYear = new Date();
        lastYear.setFullYear(today.getFullYear() - 1);
        filters.start = lastYear.toISOString().split('T')[0];
        filters.end = today.toISOString().split('T')[0];
    }
}
</script>

<template>
    <!-- Unified Filters & Totals -->
    <div class="mb-6 flex flex-col lg:flex-row gap-6 lg:items-start justify-between">
        
        <!-- Left Side: General Filters -->
        <div class="w-full lg:flex-1 flex flex-col sm:flex-row flex-wrap gap-4 items-end">
            <!-- Search -->
            <div class="space-y-1 w-full sm:min-w-[200px] sm:flex-[2]">
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
            
            <!-- Client -->
            <div class="space-y-1 w-full sm:min-w-[250px] sm:flex-[3] z-50">
                <InputLabel for="client" value="Cliente" />
                <SearchableSelect
                    id="client"
                    class="w-full"
                    v-model="filters.client"
                    :options="clientsOptions"
                    placeholder="Todos los clientes"
                />
            </div>
            
            <!-- Status -->
            <div class="space-y-1 w-full sm:w-32">
                <InputLabel for="status" value="Estado" />
                <SelectInput
                    id="status"
                    class="w-full"
                    v-model="filters.status"
                    :options="statusOptions"
                />
            </div>
            
            <!-- Clear Filters Button -->
            <div class="w-full sm:w-auto flex justify-start sm:justify-end mt-2 sm:mt-0">
                <button 
                    @click="resetFilters" 
                    title="Limpiar filtros"
                    class="h-[42px] px-3 flex items-center justify-center bg-gray-100 hover:bg-gray-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-gray-500 hover:text-emerald-600 transition-colors rounded-lg border border-gray-200 dark:border-zinc-700"
                >
                    <ArrowPathIcon class="w-5 h-5" />
                </button>
            </div>
        </div>

        <!-- Right Side: Dates & Totals Column -->
        <div class="w-full lg:w-max flex flex-col gap-4">
            
            <!-- Dates -->
            <div class="flex flex-col gap-2 w-full">
                <div class="flex gap-3">
                    <div class="space-y-1 flex-1">
                        <InputLabel for="start_date" value="Fecha inicio" />
                        <TextInput
                            id="start_date"
                            type="date"
                            class="w-full text-sm"
                            v-model="filters.start"
                        />
                    </div>
                    <div class="space-y-1 flex-1">
                        <InputLabel for="end_date" value="Fecha fin" />
                        <TextInput
                            id="end_date"
                            type="date"
                            class="w-full text-sm"
                            v-model="filters.end"
                        />
                    </div>
                </div>

                <!-- Quick Date Filters -->
                <div class="flex items-center justify-between gap-2 mt-1">
                    <button @click="setDateRange('thisYear')" class="text-[11px] text-gray-500 hover:text-emerald-600 dark:text-zinc-400 dark:hover:text-emerald-500 transition-colors">Este año</button>
                    <button @click="setDateRange('lastYear')" class="text-[11px] text-gray-500 hover:text-emerald-600 dark:text-zinc-400 dark:hover:text-emerald-500 transition-colors">Año pasado</button>
                    <button @click="setDateRange('last12Months')" class="text-[11px] text-gray-500 hover:text-emerald-600 dark:text-zinc-400 dark:hover:text-emerald-500 transition-colors">Últimos 12 meses</button>
                </div>
            </div>

            <!-- Totals Card -->
            <div class="bg-gray-50 dark:bg-zinc-800/50 border border-gray-200 dark:border-zinc-700 p-3 rounded-lg w-full">
              <div class="flex items-center gap-x-6 justify-between lg:justify-end">
                <div>
                  <div class="text-[10px] text-gray-500 dark:text-zinc-400 font-bold uppercase tracking-wider mb-0.5">Base</div>
                  <div class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">{{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(totals?.subtotal || 0) }}</div>
                </div>
                <div>
                  <div class="text-[10px] text-gray-500 dark:text-zinc-400 font-bold uppercase tracking-wider mb-0.5">IVA</div>
                  <div class="text-sm font-semibold text-blue-600 dark:text-blue-400">{{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(totals?.tax_amount || 0) }}</div>
                </div>
                <div class="pl-4 border-l border-gray-200 dark:border-zinc-700">
                  <div class="text-[10px] text-gray-500 dark:text-zinc-400 font-bold uppercase tracking-wider mb-0.5">Total</div>
                  <div class="text-base font-bold text-gray-900 dark:text-zinc-100">{{ new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(totals?.total || 0) }}</div>
                </div>
              </div>
            </div>
        </div>
    </div>
</template>
