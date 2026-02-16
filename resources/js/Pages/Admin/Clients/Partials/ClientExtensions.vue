<script setup>
import Card from '@/Components/Card.vue'
import Badge from '@/Components/Badge.vue'
import { PuzzlePieceIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    extensions: {
        type: Array,
        required: true
    }
})
</script>

<template>
    <Card class="p-0 overflow-hidden h-full">
        <div class="p-4 border-b border-gray-100 dark:border-zinc-700 bg-gray-50/50 dark:bg-zinc-800/50 flex items-center justify-between">
            <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <PuzzlePieceIcon class="h-5 w-5 text-amber-500" />
                Extensiones & Herramientas
            </h3>
            <Badge color="amber">{{ extensions.length }}</Badge>
        </div>
        <div class="p-4">
            <div class="flex flex-wrap gap-2">
                <div v-for="ext in extensions" :key="ext.id" 
                    class="flex flex-col p-3 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-gray-100 dark:border-zinc-700 min-w-[200px] flex-1"
                >
                    <span class="font-bold text-gray-900 dark:text-white text-sm">{{ ext.nombre }}</span>
                    <span class="text-[10px] text-gray-500 mt-1 uppercase leading-tight">{{ ext.source }}</span>
                </div>
            </div>
            <div v-if="extensions.length === 0" class="p-4 text-center text-gray-500 italic">
                No se han detectado extensiones vinculadas.
            </div>
        </div>
    </Card>
</template>
