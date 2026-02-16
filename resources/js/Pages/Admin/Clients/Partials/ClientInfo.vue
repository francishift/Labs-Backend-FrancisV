<script setup>
import Card from '@/Components/Card.vue'
import { 
    IdentificationIcon, 
    EnvelopeIcon, 
    PhoneIcon, 
    MapPinIcon 
} from '@heroicons/vue/24/outline'

const props = defineProps({
    client: {
        type: Object,
        required: true
    }
})
</script>

<template>
    <Card class="p-4 md:col-span-2">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <IdentificationIcon class="h-5 w-5 text-emerald-600" />
            Información del Cliente
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="flex items-start gap-3">
                <div class="mt-1 p-2 rounded-lg bg-gray-50 dark:bg-zinc-800">
                    <IdentificationIcon class="h-4 w-4 text-gray-500" />
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">CIF/NIF</p>
                    <p class="text-sm font-medium dark:text-zinc-200">{{ client.cif_nif || 'No asignado' }}</p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <div class="mt-1 p-2 rounded-lg bg-gray-50 dark:bg-zinc-800">
                    <EnvelopeIcon class="h-4 w-4 text-gray-500" />
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Email</p>
                    <p class="text-sm font-medium dark:text-zinc-200">{{ client.email || 'No asignado' }}</p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <div class="mt-1 p-2 rounded-lg bg-gray-50 dark:bg-zinc-800">
                    <PhoneIcon class="h-4 w-4 text-gray-500" />
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Teléfono / Móvil</p>
                    <p class="text-sm font-medium dark:text-zinc-200">
                        {{ [client.phone, client.mobile].filter(Boolean).join(' / ') || 'No asignado' }}
                    </p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <div class="mt-1 p-2 rounded-lg bg-gray-50 dark:bg-zinc-800">
                    <MapPinIcon class="h-4 w-4 text-gray-500" />
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Ubicación</p>
                    <p class="text-sm font-medium dark:text-zinc-200">
                        {{ [client.city, client.province].filter(Boolean).join(', ') || 'Desconocida' }}
                    </p>
                </div>
            </div>
        </div>
    </Card>
</template>
