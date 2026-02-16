<script setup>
import Card from '@/Components/Card.vue'
import { useFormatters } from '@/Composables/useFormatters'

const props = defineProps({
    extensiones: {
        type: Array,
        required: true
    }
})

const { formatCurrency } = useFormatters()
</script>

<template>
    <Card v-if="extensiones?.length" class="p-4 sm:p-6 overflow-hidden">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Extensiones de Terceros</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="extension in extensiones" :key="extension.id" class="p-4 border border-gray-200 dark:border-zinc-700 rounded-lg bg-gray-50 dark:bg-zinc-800/50">
                <div class="flex flex-col h-full">
                    <div class="flex items-start justify-between">
                        <div class="font-bold text-gray-900 dark:text-zinc-100">{{ extension.nombre }}</div>
                        <a v-if="extension.url" :href="extension.url" target="_blank" class="text-[10px] text-emerald-600 dark:text-emerald-400 hover:underline">Ver fuente</a>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-emerald-600 dark:text-emerald-400 font-bold text-lg">
                            {{ formatCurrency(extension.pivot?.precio_aplicado || extension.precio) }}
                        </span>
                        <span class="text-[10px] text-gray-500 dark:text-zinc-500 uppercase font-bold tracking-tighter">
                            {{ extension.tipo_licencia }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </Card>
</template>
