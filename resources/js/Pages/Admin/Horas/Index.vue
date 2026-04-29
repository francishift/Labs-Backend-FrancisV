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
import SelectInput from '@/Components/SelectInput.vue';
import { ClockIcon, BanknotesIcon, CalendarDaysIcon, BriefcaseIcon, WrenchScrewdriverIcon, ChevronDownIcon } from '@heroicons/vue/24/outline';
import pickBy from 'lodash/pickBy';
import debounce from 'lodash/debounce';
import axios from 'axios';

const props = defineProps({
    resumenMensual: Array,
    stats: Object,
    clientes: Array,
    filters: Object
});

// Calcular arreglo de años (desde 2026 al año actual + 1, o al menos 2026-2027)
const currentYear = new Date().getFullYear();
const yearsOptions = Array.from({length: Math.max(2, (currentYear + 2) - 2026)}, (_, i) => {
    const y = 2026 + i;
    return { label: y.toString(), value: y };
});

const filterForm = ref({
    year: props.filters?.year || currentYear,
    client_id: props.filters?.client_id || '',
    tipo_servicio: props.filters?.tipo_servicio || ''
});

// Filtrado Reactivo
watch(
    filterForm,
    debounce(function () {
        router.get(
            route('admin.resumen-horas.index'),
            pickBy(filterForm.value),
            { preserveState: true, replace: true }
        )
    }, 500),
    { deep: true }
);

const resetFilters = () => {
    filterForm.value.year = currentYear;
    filterForm.value.client_id = '';
    filterForm.value.tipo_servicio = '';
};

const expandedMonth = ref(null);

const toggleMonth = async (mesObj) => {
    if (expandedMonth.value === mesObj.mes) {
        expandedMonth.value = null;
    } else {
        expandedMonth.value = mesObj.mes;
        
        // Lazy Loading: Solo cargar si no se ha cargado previamente
        if (!mesObj.detalle_cargado && !mesObj.cargando_detalle) {
            mesObj.cargando_detalle = true;
            try {
                const response = await axios.get(route('admin.resumen-horas.detalle', { month: mesObj.mes }), {
                    params: filterForm.value
                });
                mesObj.detalle = response.data.detalle;
                mesObj.detalle_cargado = true;
            } catch (error) {
                console.error("Error cargando el detalle del mes:", error);
            } finally {
                mesObj.cargando_detalle = false;
            }
        }
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
    <Head title="Informe General" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col">
                <span class="text-xs text-gray-500 dark:text-zinc-500 font-medium uppercase tracking-wider mb-1">Informe General</span>
                <div class="flex justify-between items-center">
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-zinc-200 leading-tight">
                        Año {{ filterForm.year }}
                    </h2>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Sección de Filtros -->
                <Card class="p-4 sm:p-6 !overflow-visible relative z-20">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Select: Año -->
                        <div class="space-y-1">
                            <InputLabel for="filter_year" value="Año" />
                            <SelectInput 
                                id="filter_year"
                                v-model="filterForm.year"
                                :options="yearsOptions"
                                class="w-full"
                            />
                        </div>

                        <!-- Select: Cliente -->
                        <div class="space-y-1">
                            <InputLabel for="filter_client" value="Filtrar por Cliente" />
                            <SearchableSelect
                                id="filter_client"
                                v-model="filterForm.client_id"
                                :options="[{id: '', name: 'Todos los clientes'}, ...clientes]"
                                placeholder="Buscar cliente..."
                                class="w-full"
                            />
                        </div>

                        <!-- Select: Tipo de Servicio -->
                        <div class="space-y-1">
                            <div class="flex justify-between items-center">
                                <InputLabel for="filter_tipo" value="Tipo de Servicio" />
                                <button @click="resetFilters" class="text-xs text-gray-400 hover:text-emerald-500 transition-colors">Limpiar Filtros</button>
                            </div>
                            <SelectInput 
                                id="filter_tipo"
                                v-model="filterForm.tipo_servicio"
                                :options="[
                                    { label: 'Todos (Proyectos y Mantenimientos)', value: '' },
                                    { label: 'Sólo Proyectos', value: 'proyectos' },
                                    { label: 'Sólo Mantenimientos', value: 'mantenimientos' }
                                ]"
                                class="w-full"
                            />
                        </div>
                    </div>
                </Card>
                
                <!-- Cuadrícula de Estadísticas usando el componente StatCard -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <StatCard 
                        title="Proyectos + Previsto Mant."
                        :value="stats.total_facturado"
                        :icon="BanknotesIcon"
                        variant="emerald"
                        :is-currency="true"
                        :small-value="true"
                    >
                        <div class="mt-3">
                            <div class="flex items-start gap-2" title="Proyectos en Ejecución">
                                <svg class="w-4 h-4 shrink-0 mt-0.5 text-emerald-600 dark:text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ formatCurrency(stats.proyectos_en_ejecucion_presupuesto) }}</span>
                                    <span class="text-[10px] text-gray-500 dark:text-zinc-500 font-medium uppercase tracking-wide">Proy. en Ejecución</span>
                                </div>
                            </div>
                        </div>
                    </StatCard>
                    <StatCard 
                        title="Desglose de Ingresos"
                        value=""
                        :is-currency="false"
                        :icon="BriefcaseIcon"
                        variant="emerald"
                    >
                        <div class="flex flex-col gap-3 mt-2">
                            <div class="flex items-start gap-2" title="Proyectos (finalizados)">
                                <BriefcaseIcon class="w-4 h-4 shrink-0 mt-0.5 text-emerald-600 dark:text-emerald-500" />
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ formatCurrency(stats.total_proyectos) }}</span>
                                    <span class="text-[10px] text-gray-500 dark:text-zinc-500 font-medium uppercase tracking-wide">Proy. Finalizados</span>
                                </div>
                            </div>
                            <div class="flex items-start gap-2" title="Mantenimientos">
                                <WrenchScrewdriverIcon class="w-4 h-4 shrink-0 mt-0.5 text-emerald-600 dark:text-emerald-500" />
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ formatCurrency(stats.total_mantenimientos) }}</span>
                                    <span class="text-[10px] text-gray-500 dark:text-zinc-500 font-medium uppercase tracking-wide">Mantenimientos</span>
                                </div>
                            </div>
                        </div>
                    </StatCard>
                    <StatCard 
                        title="Media mensual"
                        :value="stats.promedio_mensual_facturado"
                        :icon="CalendarDaysIcon"
                        variant="emerald"
                        :is-currency="true"
                        :small-value="true"
                    />
                    <StatCard 
                        title="Coste (Horas Reales)"
                        :value="stats.total_importe_horas"
                        :icon="ClockIcon"
                        variant="zinc"
                        :is-currency="true"
                        :small-value="true"
                    >
                        <div class="mt-2 text-sm font-bold text-emerald-600 dark:text-emerald-400">
                            {{ stats.total_horas }} horas 
                        </div>
                    </StatCard>
                </div>

                <!-- Lista de Acordeón Mensual -->
                <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow-sm sm:rounded-lg border border-zinc-200 dark:border-zinc-800">
                    <div class="p-6 text-zinc-900 dark:text-zinc-100">
                        <h3 class="text-lg font-medium mb-4">Desglose Mensual</h3>
                        
                        <div class="space-y-4">
                            <div v-if="resumenMensual.length === 0" class="text-center py-8 text-zinc-500">
                                No hay registros de horas para el año seleccionado.
                            </div>

                            <div v-for="mes in resumenMensual" :key="mes.mes" class="border rounded-lg border-zinc-200 dark:border-zinc-800">
                                <button 
                                    @click="toggleMonth(mes)"
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
                                            <ChevronDownIcon 
                                                class="w-6 h-6 transition-transform duration-200 text-gray-400" 
                                                :class="{ 'rotate-180': expandedMonth === mes.mes }"
                                            />
                                        </div>
                                    </div>
                                </button>

                                <div v-if="expandedMonth === mes.mes" class="p-3 sm:p-4 border-t border-zinc-200 dark:border-zinc-800 overflow-x-auto w-full max-w-full">
                                    <div v-if="mes.cargando_detalle" class="flex flex-col items-center justify-center py-8 text-zinc-500 dark:text-zinc-400 space-y-3">
                                        <svg class="animate-spin h-6 w-6 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span class="text-sm font-medium">Cargando desglose...</span>
                                    </div>
                                    <DataTable v-else :columns="tableColumns" :items="mes.detalle" :hoverable="true">
                                        <template #cell-fecha="{ item }">
                                            <span class="text-gray-600 dark:text-zinc-400 whitespace-nowrap">{{ item.fecha }}</span>
                                        </template>
                                        <template #cell-tipo="{ item }">
                                            <Badge :variant="item.tipo === 'Proyecto' ? 'blue' : 'amber'">
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
