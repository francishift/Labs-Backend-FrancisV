<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { useFormatters } from '@/Composables/useFormatters'
import Card from '@/Components/Card.vue'
import { 
    UsersIcon, 
    UserGroupIcon, 
    BriefcaseIcon, 
    WrenchScrewdriverIcon,
    PuzzlePieceIcon,
    ClockIcon,
    ClipboardDocumentCheckIcon,
    CurrencyEuroIcon,
    CheckCircleIcon,
    ChartBarIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    auth: Object,
    stats: Object,
    charts: Object,
})

const { formatCurrency } = useFormatters()
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
    
    // Observer for dark mode changes
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
        backgroundColor: isDark.value ? '#18181b' : '#ffffff',
        borderColor: isDark.value ? '#3f3f46' : '#e5e7eb',
        textStyle: { color: isDark.value ? '#e4e4e7' : '#111827' },
        formatter: (params) => {
            return `${params.name}<br/><b>${formatCurrency(params.value)}</b> (${params.percent}%)`;
        }
    },
    legend: {
        orient: isMobile.value ? 'horizontal' : 'vertical',
        left: isMobile.value ? 'center' : 'right',
        top: isMobile.value ? 'bottom' : 'middle',
        bottom: isMobile.value ? 0 : 'auto',
        textStyle: { color: isDark.value ? '#a1a1aa' : '#6b7280', fontSize: isMobile.value ? 8 : 10 },
        type: 'scroll'
    },
    series: [
        {
            name: 'Presupuesto',
            type: 'pie',
            radius: isMobile.value ? ['40%', '65%'] : ['50%', '85%'],
            center: isMobile.value ? ['50%', '42%'] : ['40%', '50%'],
            avoidLabelOverlap: true,
            itemStyle: {
                borderRadius: 0
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
        backgroundColor: isDark.value ? '#18181b' : '#ffffff',
        borderColor: isDark.value ? '#3f3f46' : '#e5e7eb',
        textStyle: { color: isDark.value ? '#e4e4e7' : '#111827' },
        formatter: (params) => {
            const data = props.charts.uso_extensiones[params[0].dataIndex];
            return `${data.name}<br/><b>Uso: ${data.value}</b><br/>Adopción: ${data.percentage}%`;
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
        axisLabel: { color: isDark.value ? '#a1a1aa' : '#6b7280' },
        splitLine: { lineStyle: { color: isDark.value ? '#27272a' : '#f3f4f6' } }
    },
    yAxis: {
        type: 'category',
        data: props.charts.uso_extensiones.map(i => i.name),
        axisLabel: { color: isDark.value ? '#a1a1aa' : '#6b7280' },
        inverse: true
    },
    series: [
        {
            data: props.charts.uso_extensiones.map((i, idx) => ({
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
                borderRadius: 0
            },
            barWidth: '60%'
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
            roles: ['admin', 'coordinador'],
        },
        {
            name: 'Extensiones',
            icon: PuzzlePieceIcon,
            route: 'admin.extensiones.index',
            roles: ['admin', 'coordinador'],
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
            roles: ['admin', 'coordinador'],
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
</script>

<template>
  <Head title="Panel de Control" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-zinc-200">Panel de Control</h2>
    </template>

    <div class="py-6 space-y-8">
        <!-- Dashboard Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
            <Card class="p-4 relative overflow-hidden group">
                <div class="flex items-center">
                    <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400">
                        <BriefcaseIcon class="h-8 w-8" />
                    </div>
                    <div class="ml-5">
                        <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Proyectos en Proceso</p>
                        <p class="text-2xl font-black text-gray-900 dark:text-white">{{ stats.proyectos_en_proceso }}</p>
                    </div>
                </div>
                <div class="absolute -right-4 -bottom-4 h-24 w-24 opacity-5 transform rotate-12 transition-transform group-hover:scale-110">
                    <BriefcaseIcon />
                </div>
            </Card>

            <Card class="p-4 relative overflow-hidden group">
                <div class="flex items-center">
                    <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400">
                        <CurrencyEuroIcon class="h-8 w-8" />
                    </div>
                    <div class="ml-5">
                        <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Proyectos activos</p>
                        <p class="text-2xl font-black text-gray-900 dark:text-white">{{ formatCurrency(stats.presupuesto_total_activo) }}</p>
                    </div>
                </div>
                <div class="absolute -right-4 -bottom-4 h-24 w-24 opacity-5 transform rotate-12 transition-transform group-hover:scale-110">
                    <CurrencyEuroIcon />
                </div>
            </Card>

            <Card class="p-4 relative overflow-hidden group">
                <div class="flex items-center">
                    <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400">
                        <CurrencyEuroIcon class="h-8 w-8" />
                    </div>
                    <div class="ml-5">
                        <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Mant. {{ stats.mes_actual }} / {{ stats.anio_actual }}</p>
                        <p class="text-2xl font-black text-gray-900 dark:text-white">{{ formatCurrency(stats.total_mantenimiento_mes) }}</p>
                    </div>
                </div>
                <div class="absolute -right-4 -bottom-4 h-24 w-24 opacity-5 transform rotate-12 transition-transform group-hover:scale-110">
                    <CurrencyEuroIcon />
                </div>
            </Card>

            <Card class="p-4 relative overflow-hidden group">
                <div class="flex items-center">
                    <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400">
                        <CheckCircleIcon class="h-8 w-8" />
                    </div>
                    <div class="ml-5">
                        <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Finalizados {{ stats.anio_actual }}</p>
                        <div class="flex items-baseline gap-2">
                            <p class="text-2xl font-black text-gray-900 dark:text-white">{{ stats.proyectos_finalizados_anio }}</p>
                            <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                {{ formatCurrency(stats.presupuesto_finalizado_anio) }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="absolute -right-4 -bottom-4 h-24 w-24 opacity-5 transform rotate-12 transition-transform group-hover:scale-110">
                    <CheckCircleIcon />
                </div>
            </Card>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <Card class="p-6 h-[450px] flex flex-col">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400">
                        <CurrencyEuroIcon class="h-5 w-5" />
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-zinc-100">Valor de Proyectos Activos</h3>
                </div>
                <div class="flex-1">
                    <v-chart class="h-full w-full" :option="proyectosChartOption" autoresize />
                </div>
            </Card>
            
            <Card class="p-6 h-[450px] flex flex-col">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400">
                        <PuzzlePieceIcon class="h-5 w-5" />
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-zinc-100">Adopción de Extensiones</h3>
                </div>
                <div class="flex-1">
                    <v-chart class="h-full w-full" :option="extensionesChartOption" autoresize />
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
