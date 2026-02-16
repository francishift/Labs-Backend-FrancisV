<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import Card from '@/Components/Card.vue'
import Badge from '@/Components/Badge.vue'
import { BriefcaseIcon, CalendarIcon } from '@heroicons/vue/24/outline'
import { useFormatters } from '@/Composables/useFormatters'

const props = defineProps({
    projects: {
        type: Array,
        required: true
    }
})

const { formatCurrency, formatDate } = useFormatters()

const activeProjects = computed(() => 
    props.projects.filter(p => p.estado === 'En proceso')
)

const finishedProjects = computed(() => 
    props.projects.filter(p => p.estado === 'Finalizado')
)

const getStatusColor = (status) => {
    const colors = {
        'En proceso': 'indigo',
        'Finalizado': 'emerald'
    }
    return colors[status] || 'zinc'
}
</script>

<template>
    <div class="space-y-6">
        <!-- Active Projects -->
        <Card class="p-0 overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-zinc-700 bg-gray-50/50 dark:bg-zinc-800/50 flex items-center justify-between">
                <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <BriefcaseIcon class="h-5 w-5 text-indigo-600" />
                    Proyectos en Curso
                </h3>
                <Badge color="indigo">{{ activeProjects.length }}</Badge>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-zinc-700">
                <div v-for="proyecto in activeProjects" :key="proyecto.id" class="p-4 hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors">
                    <div class="flex justify-between items-start mb-1">
                        <Link :href="route('admin.proyectos.show', proyecto.id)" class="font-bold text-gray-900 dark:text-white hover:text-emerald-600 transition-colors">
                            {{ proyecto.proyecto }}
                        </Link>
                        <span class="font-black text-gray-900 dark:text-white">{{ formatCurrency(proyecto.presupuesto) }}</span>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-zinc-400">
                        <span class="flex items-center gap-1">
                            <CalendarIcon class="h-3 w-3" />
                            Inicio: {{ formatDate(proyecto.fecha_inicio) }}
                        </span>
                        <Badge :color="getStatusColor(proyecto.estado)" size="xs">{{ proyecto.estado }}</Badge>
                    </div>
                </div>
                <div v-if="activeProjects.length === 0" class="p-8 text-center text-gray-500 italic">
                    No hay proyectos en curso.
                </div>
            </div>
        </Card>

        <!-- Finished Projects -->
        <Card v-if="finishedProjects.length > 0" class="p-0 overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-zinc-700 bg-gray-50/50 dark:bg-zinc-800/50 flex items-center justify-between">
                <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <BriefcaseIcon class="h-5 w-5 text-gray-400" />
                    Proyectos Finalizados
                </h3>
                <Badge color="zinc">{{ finishedProjects.length }}</Badge>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-zinc-700 max-h-80 overflow-y-auto">
                <div v-for="proyecto in finishedProjects" :key="proyecto.id" class="p-4 opacity-75 grayscale hover:grayscale-0 hover:opacity-100 transition-all">
                    <div class="flex justify-between items-start mb-1">
                        <span class="font-bold text-gray-900 dark:text-white">{{ proyecto.proyecto }}</span>
                        <span class="font-black text-gray-900 dark:text-white">{{ formatCurrency(proyecto.presupuesto) }}</span>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-gray-500">
                        <span>Fin: {{ formatDate(proyecto.fecha_fin || proyecto.updated_at) }}</span>
                    </div>
                </div>
            </div>
        </Card>
    </div>
</template>
