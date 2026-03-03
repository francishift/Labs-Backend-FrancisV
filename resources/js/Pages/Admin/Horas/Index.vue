<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import StatCard from '@/Components/StatCard.vue';
import DataTable from '@/Components/DataTable.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import Badge from '@/Components/Badge.vue';
import Card from '@/Components/Card.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { ClockIcon, BanknotesIcon, CalendarDaysIcon, BriefcaseIcon, WrenchScrewdriverIcon } from '@heroicons/vue/24/outline';
import pickBy from 'lodash/pickBy';
import throttle from 'lodash/throttle';

const props = defineProps({
    resumenMensual: Array,
    stats: Object,
    clientes: Array,
    filters: Object
});

// Calculate array of years (from 2026 to current + 1 or at least 2026-2027)
const currentYear = new Date().getFullYear();
const years = Array.from({length: Math.max(2, (currentYear + 2) - 2026)}, (_, i) => 2026 + i);

const filterForm = ref({
    year: props.filters?.year || currentYear,
    client_id: props.filters?.client_id || '',
    tipo_servicio: props.filters?.tipo_servicio || ''
});

// Reactive Filtering
watch(
    filterForm,
    throttle(function () {
        router.get(
            route('admin.resumen-horas.index'),
            pickBy(filterForm.value),
            { preserveState: true, replace: true }
        )
    }, 300),
    { deep: true }
);

const resetFilters = () => {
    filterForm.value.year = currentYear;
    filterForm.value.client_id = '';
    filterForm.value.tipo_servicio = '';
};

const expandedMonth = ref(null);

const toggleMonth = (mes) => {
    if (expandedMonth.value === mes) {
        expandedMonth.value = null;
    } else {
        expandedMonth.value = mes;
    }
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(value);
};

const formatHours = (minutos) => {
    const h = Math.floor(minutos / 60);
    const m = minutos % 60;
    return m > 0 ? `${h}h ${m}m` : `${h}h`;
};

const tableColumns = [
    { key: 'fecha', label: 'Fecha' },
    { key: 'tipo', label: 'Tipo' },
    { key: 'nombre', label: 'Proyecto / Aplicación' },
    { key: 'descripcion', label: 'Descripción' },
    { key: 'minutos', label: 'Duración', align: 'right' },
    { key: 'importe_horas', label: 'Costo (Horas)', align: 'right' }
];
</script>

<template>
    <Head title="Resumen de Horas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col">
                <span class="text-xs text-gray-500 dark:text-zinc-500 font-medium uppercase tracking-wider mb-1">Resumen de Horas</span>
                <div class="flex justify-between items-center">
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-zinc-200 leading-tight">
                        Año {{ filterForm.year }}
                    </h2>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Filters Section -->
                <Card class="p-4 !overflow-visible relative z-20">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Select: Año -->
                        <div class="space-y-1">
                            <InputLabel for="filter_year" value="Año" />
                            <select 
                                id="filter_year"
                                v-model="filterForm.year"
                                class="w-full bg-white dark:bg-zinc-950 border-gray-300 dark:border-zinc-800 text-gray-900 dark:text-zinc-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm"
                            >
                                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                            </select>
                        </div>

                        <!-- Select: Cliente -->
                        <div class="space-y-1">
                            <InputLabel for="filter_client" value="Filtrar por Cliente" />
                            <SearchableSelect
                                id="filter_client"
                                v-model="filterForm.client_id"
                                :options="[{id: '', name: 'Todos los clientes'}, ...clientes]"
                                placeholder="Buscar cliente..."
                                class="w-full bg-white dark:bg-zinc-950 text-gray-900 dark:text-zinc-300"
                            />
                        </div>

                        <!-- Select: Tipo de Servicio -->
                        <div class="space-y-1">
                            <div class="flex justify-between items-center">
                                <InputLabel for="filter_tipo" value="Tipo de Servicio" />
                                <button @click="resetFilters" class="text-xs text-gray-400 hover:text-emerald-500 transition-colors">Limpiar Filtros</button>
                            </div>
                            <select 
                                id="filter_tipo"
                                v-model="filterForm.tipo_servicio"
                                class="w-full bg-white dark:bg-zinc-950 border-gray-300 dark:border-zinc-800 text-gray-900 dark:text-zinc-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm"
                            >
                                <option value="">Todos (Proyectos y Mantenimientos)</option>
                                <option value="proyectos">Sólo Proyectos</option>
                                <option value="mantenimientos">Sólo Mantenimientos</option>
                            </select>
                        </div>
                    </div>
                </Card>
                
                <!-- Stats Grid using StatCard component -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                    <StatCard 
                        title="Importe Proy + Mant"
                        :value="stats.total_facturado"
                        :icon="BanknotesIcon"
                        variant="emerald"
                        :is-currency="true"
                        :small-value="true"
                    >
                        <div class="flex gap-4 mt-2 text-xs font-semibold text-emerald-700 dark:text-emerald-400 opacity-90">
                            <span class="flex items-center gap-1" title="Facturado en Proyectos">
                                <BriefcaseIcon class="w-4 h-4" />
                                {{ formatCurrency(stats.total_proyectos) }}
                            </span>
                            <span class="flex items-center gap-1" title="Facturado en Mantenimientos">
                                <WrenchScrewdriverIcon class="w-4 h-4" />
                                {{ formatCurrency(stats.total_mantenimientos) }}
                            </span>
                        </div>
                    </StatCard>
                    <StatCard 
                        title="Media mensual"
                        :value="stats.promedio_mensual_facturado"
                        :icon="CalendarDaysIcon"
                        variant="emerald"
                        :is-currency="true"
                    />
                    <StatCard 
                        title="Costo (Horas)"
                        :value="stats.total_importe_horas"
                        :icon="ClockIcon"
                        variant="zinc"
                        :is-currency="true"
                    />
                    <StatCard 
                        title="Total Horas"
                        :value="stats.total_horas"
                        :icon="ClockIcon"
                        variant="zinc"
                        suffix="h"
                    />
                </div>

                <!-- Monthly Accordion List -->
                <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow-sm sm:rounded-lg border border-zinc-200 dark:border-zinc-800">
                    <div class="p-6 text-zinc-900 dark:text-zinc-100">
                        <h3 class="text-lg font-medium mb-4">Desglose Mensual</h3>
                        
                        <div class="space-y-4">
                            <div v-if="resumenMensual.length === 0" class="text-center py-8 text-zinc-500">
                                No hay registros de horas para el año seleccionado.
                            </div>

                            <div v-for="mes in resumenMensual" :key="mes.mes" class="border rounded-lg border-zinc-200 dark:border-zinc-800">
                                <button 
                                    @click="toggleMonth(mes.mes)"
                                    class="w-full flex flex-col lg:flex-row lg:items-center justify-between p-4 gap-4 bg-zinc-50 dark:bg-zinc-800/50 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors rounded-t-lg"
                                    :class="{ 'rounded-b-lg': expandedMonth !== mes.mes }"
                                >
                                    <div class="flex flex-col sm:flex-row sm:items-center space-y-3 sm:space-y-0 sm:space-x-4 text-left w-full lg:w-auto">
                                        <span class="text-lg font-bold min-w-[100px]">{{ mes.nombre }}</span>
                                        <div class="flex flex-col">
                                            <span class="bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400 px-2.5 py-1 rounded-full text-xs font-semibold w-max mb-1">
                                                {{ formatCurrency(mes.total_facturado) }}
                                            </span>
                                            <div class="flex gap-3 text-[10px] font-semibold text-emerald-700 dark:text-emerald-400 opacity-80 px-1">
                                                <span class="flex items-center gap-1" title="Proyectos de este mes">
                                                    <BriefcaseIcon class="w-3 h-3" />
                                                    {{ formatCurrency(mes.total_facturado_proyectos) }}
                                                </span>
                                                <span class="flex items-center gap-1" title="Mantenimientos de este mes">
                                                    <WrenchScrewdriverIcon class="w-3 h-3" />
                                                    {{ formatCurrency(mes.total_facturado_mantenimientos) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-4 w-full lg:w-auto justify-between lg:justify-end border-t border-gray-200 dark:border-zinc-700/50 lg:border-none pt-3 lg:pt-0 mt-2 lg:mt-0">
                                        <div class="flex flex-col text-left lg:text-right text-sm">
                                            <span class="font-medium text-gray-400 dark:text-zinc-500 text-xs">Coste interno</span>
                                            <span class="text-zinc-500 dark:text-zinc-400 font-mono">{{ formatCurrency(mes.total_importe_horas) }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="flex items-center justify-center bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 px-3 py-1.5 rounded-lg border border-blue-100 dark:border-blue-800/30">
                                                <ClockIcon class="w-4 h-4 mr-1.5 opacity-70" />
                                                <span class="font-bold text-sm tracking-tight">{{ formatHours(mes.total_minutos) }}</span>
                                            </div>
                                            <svg 
                                                class="w-6 h-6 transition-transform duration-200 text-gray-400" 
                                                :class="{ 'rotate-180': expandedMonth === mes.mes }"
                                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                </button>

                                <div v-if="expandedMonth === mes.mes" class="p-3 sm:p-4 border-t border-zinc-200 dark:border-zinc-800 overflow-x-auto w-full max-w-full">
                                    <DataTable :columns="tableColumns" :items="mes.detalle" :hoverable="true">
                                        <template #cell-fecha="{ item }">
                                            <span class="text-gray-600 dark:text-zinc-400 whitespace-nowrap">{{ item.fecha }}</span>
                                        </template>
                                        <template #cell-tipo="{ item }">
                                            <Badge :variant="item.tipo === 'Proyecto' ? 'blue' : 'indigo'">
                                                {{ item.tipo }}
                                            </Badge>
                                        </template>
                                        <template #cell-nombre="{ item }">
                                            <div class="font-medium text-gray-900 dark:text-zinc-100">{{ item.nombre }}</div>
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400 font-normal">{{ item.cliente }}</div>
                                        </template>
                                        <template #cell-descripcion="{ item }">
                                            <span class="text-gray-600 dark:text-zinc-400 text-sm max-w-md line-clamp-2" :title="item.descripcion">
                                                {{ item.descripcion }}
                                            </span>
                                        </template>
                                        <template #cell-minutos="{ item }">
                                            <span class="tabular-nums text-gray-600 dark:text-zinc-400 font-medium">{{ formatHours(item.minutos) }}</span>
                                        </template>
                                        <template #cell-importe_horas="{ item }">
                                            <span class="tabular-nums font-mono text-zinc-400 dark:text-zinc-500 whitespace-nowrap">
                                                {{ formatCurrency(item.importe_horas) }}
                                            </span>
                                        </template>
                                    </DataTable>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
