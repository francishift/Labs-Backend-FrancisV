<script setup>
import { reactive, watch } from 'vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import SearchInput from '@/Components/SearchInput.vue'
import SearchableSelect from '@/Components/SearchableSelect.vue'

import { ArrowPathIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    modelValue: Object,
    search: String
})

const emit = defineEmits(['update:modelValue', 'update:search', 'change', 'reset'])

const filters = reactive({ 
    start: props.modelValue.start,
    end: props.modelValue.end,
    quickFilter: props.modelValue.quickFilter || ''
})

import { ref } from 'vue'
const selectedQuickFilter = ref(props.modelValue.quickFilter || '')

watch(() => props.modelValue, (newVal) => {
    // Only update dates from parent to avoid killing the local quick filter select option
    filters.start = newVal.start
    filters.end = newVal.end
    
    // Only update local quick filter if parent explicitly changes it (like a clear all)
    if (newVal.quickFilter !== undefined && newVal.quickFilter !== selectedQuickFilter.value) {
        selectedQuickFilter.value = newVal.quickFilter
        filters.quickFilter = newVal.quickFilter
    }
}, { deep: true })

watch(filters, (newVal) => {
    emit('update:modelValue', { ...newVal, quickFilter: selectedQuickFilter.value })
    emit('change')
}, { deep: true })

const onSearchUpdate = (val) => {
    emit('update:search', val)
    emit('change')
}

const resetFilters = () => {
    emit('reset')
}

const quickFilterOptions = [
    { value: 'this_year', label: 'Este año' },
    { value: 'this_quarter', label: 'Este trimestre' },
    { value: 'last_12_months', label: 'Últimos 12 meses' },
]

watch(selectedQuickFilter, (newVal) => {
    if (!newVal) return

    const today = new Date()
    let startDate = new Date()

    if (newVal === 'last_12_months') {
        startDate.setFullYear(today.getFullYear() - 1)
    } else if (newVal === 'this_year') {
        startDate = new Date(today.getFullYear(), 0, 1)
    } else if (newVal === 'this_quarter') {
        const currentQuarter = Math.floor(today.getMonth() / 3)
        startDate = new Date(today.getFullYear(), currentQuarter * 3, 1)
    }

    // Helper to format date as YYYY-MM-DD using local time
    const formatDate = (date) => {
        const year = date.getFullYear()
        const month = String(date.getMonth() + 1).padStart(2, '0')
        const day = String(date.getDate()).padStart(2, '0')
        return `${year}-${month}-${day}`
    }

    filters.end = formatDate(today)
    filters.start = formatDate(startDate)
})
</script>

<template>
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6">
        <div class="flex flex-col md:flex-row gap-4 w-full items-end">
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
            <div class="space-y-1 w-full md:flex-1 z-50">
                <InputLabel for="quick-filter" value="Filtro rápido" />
                <SearchableSelect 
                    id="quick-filter" 
                    class="w-full"
                    v-model="selectedQuickFilter"
                    :options="quickFilterOptions"
                    valueKey="value"
                    labelKey="label"
                    placeholder="Filtro rápido..."
                />
            </div>
            <div class="space-y-1 w-full md:flex-1">
                <InputLabel for="search-budgets" value="Buscar" />
                <SearchInput 
                    id="search-budgets"
                    name="search"
                    placeholder="Buscar por contacto o nº..."
                    class="w-full"
                    :model-value="search"
                    @update:model-value="onSearchUpdate"
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
