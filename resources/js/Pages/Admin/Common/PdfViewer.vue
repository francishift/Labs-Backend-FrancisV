<script setup>
import { onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { 
    ArrowLeftIcon, 
    ArrowDownTrayIcon 
} from '@heroicons/vue/24/outline'
import { useBodyScrollLock } from '@/Composables/useBodyScrollLock'

const props = defineProps({
    pdfUrl: String,
    title: String,
    backUrl: String,
})

const { lock } = useBodyScrollLock()

onMounted(() => {
    lock()
})

const downloadPdf = () => {
    // Añadir download=1 a la URL para forzar la descarga
    const separator = props.pdfUrl.includes('?') ? '&' : '?'
    window.location.href = `${props.pdfUrl}${separator}download=1`
}
</script>

<template>
    <Head :title="title" />

    <div class="fixed inset-0 flex flex-col bg-zinc-900 overflow-hidden">
        <!-- Professional Header -->
        <header class="h-16 bg-black border-b border-zinc-800 px-4 flex items-center justify-between shrink-0 z-50">
            <div class="flex items-center gap-4">
                <Link 
                    :href="backUrl" 
                    class="p-2 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-300 transition-colors flex items-center gap-2 group"
                >
                    <ArrowLeftIcon class="h-5 w-5 group-hover:-translate-x-0.5 transition-transform" />
                    <span class="hidden sm:inline font-bold text-sm">Volver</span>
                </Link>
                
                <h1 class="text-white font-black text-sm sm:text-lg truncate max-w-[200px] sm:max-w-md">
                    {{ title }}
                </h1>
            </div>

            <button 
                @click="downloadPdf"
                class="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-bold text-sm transition-colors"
                title="Descargar PDF"
            >
                <ArrowDownTrayIcon class="h-5 w-5" />
                <span class="hidden sm:inline">Descargar</span>
            </button>
        </header>

        <!-- PDF Content -->
        <main class="flex-1 relative bg-zinc-800">
            <iframe 
                :src="pdfUrl" 
                class="absolute inset-0 w-full h-full border-none"
                title="Visor de PDF"
            ></iframe>
        </main>
    </div>
</template>
