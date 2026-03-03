<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { useFormatters } from '@/Composables/useFormatters'
import Card from '@/Components/Card.vue'
import { 
    UsersIcon, 
    UserGroupIcon, 
    CheckCircleIcon,
    ChartBarIcon,
    BriefcaseIcon,
    ClockIcon,
    CurrencyEuroIcon,
    PuzzlePieceIcon,
    WrenchScrewdriverIcon,
    ClipboardDocumentCheckIcon
} from '@heroicons/vue/24/outline'
import StatCard from '@/Components/StatCard.vue'

const props = defineProps({
    auth: Object,
    stats: Object,
    charts: Object,
})

const { formatCurrency, truncate } = useFormatters()
const userRoles = computed(() => props.auth?.roles || [])
const isDark = ref(false)
const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1200)
const isMobile = computed(() => windowWidth.value < 768)

const handleResize = () => {
    windowWidth.value = window.innerWidth
}

onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark')
    windowWidth.value = window.innerWidth
    window.addEventListener('resize', handleResize)
    
    // Observador para cambios en el modo oscuro
    const observer = new MutationObserver(() => {
        isDark.value = document.documentElement.classList.contains('dark')
    })
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
})

onUnmounted(() => {
    window.removeEventListener('resize', handleResize)
})

const proyectosChartOption = computed(() => ({
    backgroundColor: 'transparent',
    title: {
        show: false
    },
    tooltip: {
        trigger: 'item',
        confine: true,
        backgroundColor: isDark.value ? '#18181b' : '#ffffff',
        borderColor: isDark.value ? '#3f3f46' : '#e5e7eb',
        textStyle: { color: isDark.value ? '#e4e4e7' : '#111827' },
        position: (point, params, dom, rect, size) => {
            if (isMobile.value) {
                return [point[0], '50%'];
            }
            return null;
        },
        formatter: (params) => {
            return `${params.name}<br/><b>${formatCurrency(params.value)}</b> (${params.percent}%)`;
        }
    },
    legend: {
        orient: isMobile.value ? 'horizontal' : 'vertical',
        left: isMobile.value ? 'center' : 'right',
        top: isMobile.value ? 'bottom' : 'middle',
        bottom: isMobile.value ? 0 : 'auto',
        textStyle: { color: isDark.value ? '#a1a1aa' : '#6b7280', fontSize: isMobile.value ? 9 : 11 },
        type: 'scroll',
        formatter: (name) => truncate(name, isMobile.value ? 5 : 12)
    },
    series: [
        {
            name: 'Presupuesto',
            type: 'pie',
            radius: isMobile.value ? ['40%', '65%'] : ['50%', '85%'],
            center: isMobile.value ? ['50%', '42%'] : ['40%', '50%'],
            avoidLabelOverlap: true,
            itemStyle: {
                borderRadius: 10
            },
            label: {
                show: false
            },
            emphasis: {
                label: {
                    show: true,
                    fontSize: '14',
                    fontWeight: 'bold',
                    color: isDark.value ? '#e4e4e7' : '#111827'
                }
            },
            data: props.charts.proyectos_activos
        }
    ]
}));

const extensionesChartOption = computed(() => ({
    backgroundColor: 'transparent',
    title: {
        show: false
    },
    tooltip: {
        trigger: 'axis',
        axisPointer: { type: 'shadow' },
        confine: true,
        backgroundColor: isDark.value ? '#18181b' : '#ffffff',
        borderColor: isDark.value ? '#3f3f46' : '#e5e7eb',
        textStyle: { color: isDark.value ? '#e4e4e7' : '#111827' },
        position: (point, params, dom, rect, size) => {
            if (isMobile.value) {
                return [point[0], '50%'];
            }
            return null;
        },
        formatter: (params) => {
            const data = props.charts.repercutido_fijos[params[0].dataIndex];
            return `${data.name}<br/><b>Repercutido: ${formatCurrency(data.value)}</b>`;
        }
    },
    grid: {
        left: '3%',
        right: '4%',
        bottom: '3%',
        top: '5%',
        containLabel: true
    },
    xAxis: {
        type: 'value',
        axisLabel: { 
            show: false
        },
        splitLine: { lineStyle: { color: isDark.value ? '#27272a' : '#f3f4f6' } }
    },
    yAxis: {
        type: 'category',
        data: props.charts.repercutido_fijos.map(i => i.name),
        axisLabel: { 
            color: isDark.value ? '#a1a1aa' : '#6b7280',
            fontSize: isMobile.value ? 9 : 11,
            formatter: (value) => truncate(value, isMobile.value ? 5 : 12)
        },
        inverse: true
    },
    series: [
        {
            data: props.charts.repercutido_fijos.map((i, idx) => ({
                value: i.value,
                itemStyle: {
                    color: [
                        '#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', 
                        '#ec4899', '#06b6d4', '#f97316', '#14b8a6'
                    ][idx % 9]
                }
            })),
            type: 'bar',
            itemStyle: {
                borderRadius: [0, 4, 4, 0]
            },
            barWidth: '60%'
        }
    ]
}));

const mantenimientosChartOption = computed(() => ({
    backgroundColor: 'transparent',
    title: {
        show: false
    },
    tooltip: {
        trigger: 'axis',
        confine: true,
        backgroundColor: isDark.value ? '#18181b' : '#ffffff',
        borderColor: isDark.value ? '#3f3f46' : '#e5e7eb',
        textStyle: { color: isDark.value ? '#e4e4e7' : '#111827' },
        position: (point, params, dom, rect, size) => {
            if (isMobile.value) {
                return [point[0], '50%'];
            }
            return null;
        },
        formatter: (params) => {
            return `${params[0].name}<br/><b>Valor Anual: ${formatCurrency(params[0].value)}</b>`;
        }
    },
    grid: {
        left: '3%',
        right: '4%',
        bottom: '3%',
        top: '5%',
        containLabel: true
    },
    xAxis: {
        type: 'category',
        data: props.charts.valor_mantenimientos.map(i => i.name),
        axisLabel: { 
            color: isDark.value ? '#a1a1aa' : '#6b7280',
            fontSize: isMobile.value ? 9 : 11,
            rotate: 45,
            interval: 0,
            formatter: (value) => truncate(value, isMobile.value ? 5 : 12)
        },
        axisTick: { show: false },
        axisLine: { lineStyle: { color: isDark.value ? '#3f3f46' : '#e5e7eb' } }
    },
    yAxis: {
        type: 'value',
        axisLabel: { color: isDark.value ? '#a1a1aa' : '#6b7280' },
        splitLine: { lineStyle: { color: isDark.value ? '#27272a' : '#f3f4f6' } }
    },
    series: [
        {
            data: props.charts.valor_mantenimientos.map((i, idx) => ({
                value: i.value,
                itemStyle: {
                    color: {
                        type: 'linear',
                        x: 0, y: 0, x2: 0, y2: 1,
                        colorStops: [
                            { offset: 0, color: '#10b981' },
                            { offset: 1, color: '#059669' }
                        ]
                    }
                }
            })),
            type: 'bar',
            barWidth: '50%',
            itemStyle: {
                borderRadius: [4, 4, 0, 0]
            }
        }
    ]
}));

const clientesChartOption = computed(() => ({
    backgroundColor: 'transparent',
    title: {
        show: false
    },
    tooltip: {
        trigger: 'axis',
        axisPointer: { type: 'shadow' },
        confine: true,
        backgroundColor: isDark.value ? '#18181b' : '#ffffff',
        borderColor: isDark.value ? '#3f3f46' : '#e5e7eb',
        textStyle: { color: isDark.value ? '#e4e4e7' : '#111827' },
        position: (point, params, dom, rect, size) => {
            if (isMobile.value) {
                return [point[0], '50%'];
            }
            return null;
        },
        formatter: (params) => {
            return `${params[0].name}<br/><b>Valor Anual: ${formatCurrency(params[0].value)}</b>`;
        }
    },
    grid: {
        left: '3%',
        right: '4%',
        bottom: '3%',
        top: '5%',
        containLabel: true
    },
    xAxis: {
        type: 'value',
        axisLabel: { 
            show: false
        },
        splitLine: { lineStyle: { color: isDark.value ? '#27272a' : '#f3f4f6' } }
    },
    yAxis: {
        type: 'category',
        data: props.charts.valor_por_cliente.map(i => i.name),
        axisLabel: { 
            color: isDark.value ? '#a1a1aa' : '#6b7280',
            fontSize: isMobile.value ? 9 : 11,
            formatter: (value) => truncate(value, isMobile.value ? 5 : 12)
        },
        inverse: true
    },
    series: [
        {
            data: props.charts.valor_por_cliente.map((i, idx) => ({
                value: i.value,
                itemStyle: {
                    color: [
                        '#3b82f6', '#60a5fa', '#2563eb', '#1d4ed8', '#1e40af'
                    ][idx % 5]
                }
            })),
            type: 'bar',
            barWidth: '60%',
            itemStyle: {
                borderRadius: [0, 4, 4, 0]
            }
        }
    ]
}));

const modules = computed(() => {
    const allModules = [
        {
            name: 'Clientes',
            icon: UserGroupIcon,
            route: 'admin.clientes.index',
            roles: ['admin'],
        },
        {
            name: 'Proyectos',
            icon: BriefcaseIcon,
            route: 'admin.proyectos.index',
            roles: ['admin', 'coordinador'],
        },
        {
            name: 'Mantenimientos',
            icon: ClockIcon,
            route: 'admin.mantenimientos.index',
            roles: ['admin'],
        },
        {
            name: 'Extensiones',
            icon: PuzzlePieceIcon,
            route: 'admin.extensiones.index',
            roles: ['admin'],
        },
        {
            name: 'Servicios',
            icon: WrenchScrewdriverIcon,
            route: 'admin.servicios.index',
            roles: ['admin', 'coordinador'],
        },
        {
            name: 'Servicios Mant.',
            icon: ClipboardDocumentCheckIcon,
            route: 'admin.mantenimiento-servicios.index',
            roles: ['admin'],
        },
        {
            name: 'Usuarios',
            icon: UsersIcon,
            route: 'admin.usuarios.index',
            roles: ['admin'],
        },
    ]

    return allModules.filter(module => 
        module.roles.some(role => userRoles.value.includes(role))
    )
})

const isAdmin = computed(() => userRoles.value.includes('admin'))
</script>

<template>
  <Head title="Panel de Control" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-zinc-200">Panel de Control</h2>
    </template>

    <div class="py-6 space-y-8">
        <!-- Dashboard Stats Cards (Solo Admin) -->
        <div v-if="isAdmin" class="grid grid-cols-1 md:grid-cols-2 min-[1530px]:grid-cols-4 gap-6">
            <StatCard 
                title="Proyectos en Proceso"
                :value="stats.proyectos_en_proceso"
                :is-currency="false"
                :icon="BriefcaseIcon"
                variant="emerald"
                :small-value="true"
            />

            <StatCard 
                title="Proyectos activos"
                :value="stats.presupuesto_total_activo"
                :icon="CurrencyEuroIcon"
                variant="emerald"
                :small-value="true"
            />

            <StatCard 
                :title="`Mant. ${stats.mes_actual} / ${stats.anio_actual}`"
                :value="stats.total_mantenimiento_mes"
                :icon="CurrencyEuroIcon"
                variant="emerald"
                :small-value="true"
            />

            <StatCard 
                :title="`Finalizados ${stats.anio_actual}`"
                :value="stats.proyectos_finalizados_anio"
                :secondary-value="stats.presupuesto_finalizado_anio"
                :is-currency="false"
                :icon="CheckCircleIcon"
                variant="emerald"
                :small-value="true"
            />
        </div>

        <!-- Charts Section (Solo Admin) -->
        <div v-if="isAdmin" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <Card class="p-4 sm:p-6 h-[450px] flex flex-col">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400">
                        <CurrencyEuroIcon class="h-5 w-5" />
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-zinc-100">Valor de Proyectos Activos</h3>
                </div>
                <div class="flex-1">
                    <v-chart class="h-full w-full" :option="proyectosChartOption" :init="{ renderer: 'svg' }" autoresize />
                </div>
            </Card>

            <Card class="p-4 sm:p-6 h-[450px] flex flex-col">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400">
                        <ClockIcon class="h-5 w-5" />
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-zinc-100">Valor Anual de Mantenimientos Activos</h3>
                </div>
                <div class="flex-1">
                    <v-chart class="h-full w-full" :option="mantenimientosChartOption" :init="{ renderer: 'svg' }" autoresize />
                </div>
            </Card>
            
            <Card class="p-4 sm:p-6 h-[450px] flex flex-col">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400">
                        <UserGroupIcon class="h-5 w-5" />
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-zinc-100">Valor Anual por Cliente</h3>
                </div>
                <div class="flex-1">
                    <v-chart class="h-full w-full" :option="clientesChartOption" :init="{ renderer: 'svg' }" autoresize />
                </div>
            </Card>
            
            <Card class="p-4 sm:p-6 h-[450px] flex flex-col">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400">
                        <PuzzlePieceIcon class="h-5 w-5" />
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-zinc-100">Repercutido por Extensiones/Software</h3>
                </div>
                <div class="flex-1">
                    <v-chart class="h-full w-full" :option="extensionesChartOption" :init="{ renderer: 'svg' }" autoresize />
                </div>
            </Card>
        </div>

        <!-- Quick Access Navigation -->
        <div>
            <div class="flex items-center gap-2 mb-4">
                <ChartBarIcon class="h-5 w-5 text-gray-400" />
                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-500 dark:text-zinc-500">Acceso Rápido</h3>
            </div>
            
            <div v-if="modules.length > 0" class="flex flex-wrap gap-4">
              <Link
                v-for="module in modules"
                :key="module.name"
                :href="route(module.route)"
                class="flex items-center gap-3 bg-white dark:bg-zinc-800 shadow-sm rounded-xl border border-gray-100 dark:border-zinc-700 px-5 py-3 hover:shadow-md hover:-translate-y-0.5 hover:border-emerald-300 dark:hover:border-zinc-500 transition-all duration-200"
              >
                <component :is="module.icon" class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                <span class="font-bold text-gray-900 dark:text-zinc-100">{{ module.name }}</span>
              </Link>
            </div>

            <div v-else class="bg-white dark:bg-zinc-800 overflow-hidden shadow-sm rounded-xl p-8 text-center border border-gray-100 dark:border-zinc-700">
                <p class="text-gray-500 dark:text-zinc-400">No tienes acceso a ningún módulo.</p>
            </div>
        </div>
    </div>
  </AuthenticatedLayout>
</template>
