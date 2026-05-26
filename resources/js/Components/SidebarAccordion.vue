<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    group: {
        type: Object,
        required: true,
    },
    auth: {
        type: Object,
        required: true,
    },
    isMobile: {
        type: Boolean,
        default: false
    },
    isOpen: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['close', 'toggle'])

const hasRole = (role) => {
    if (!props.auth?.roles) return false
    if (role === 'coordinador') {
        return props.auth.roles.some(r => ['admin', 'coordinador'].includes(r))
    }
    return props.auth.roles.includes(role)
}

const visibleItems = computed(() => {
    return props.group.items.filter(item => !item.role || hasRole(item.role))
})
</script>

<template>
    <template v-if="group.show !== false && visibleItems.length > 0">
        <!-- Grupo sin título (ej. Dashboard, links sueltos) -->
        <div v-if="!group.title" class="mb-4">
            <div class="space-y-1">
                <template v-for="item in visibleItems" :key="item.name">
                    <Link
                        :href="item.href"
                        @click="$emit('close')"
                        :class="[
                            item.active
                                ? 'bg-zinc-800 text-white border border-zinc-700/50'
                                : 'text-zinc-400 hover:text-white hover:bg-zinc-800',
                            isMobile 
                                ? 'group flex items-center px-3 py-3 text-base font-medium rounded-xl transition-all duration-200' 
                                : 'group flex items-center px-3 py-2.5 text-sm font-medium rounded-xl transition-all duration-200'
                        ]"
                        prefetch
                    >
                        <component 
                            :is="item.icon" 
                            :class="isMobile ? 'mr-4 flex-shrink-0 h-6 w-6' : 'me-3 flex-shrink-0 h-5 w-5'" 
                            aria-hidden="true" 
                        />
                        {{ item.name }}
                    </Link>
                </template>
            </div>
        </div>

        <!-- Grupo con título (Acordeón) -->
        <div v-else class="mb-2">
            <button
                @click="$emit('toggle')"
                class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left font-semibold uppercase tracking-wider transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/50"
                :class="[
                    isOpen ? 'text-zinc-300 bg-zinc-800/40' : 'text-zinc-500 hover:text-zinc-300 hover:bg-zinc-800/20',
                    isMobile ? 'text-sm mt-3' : 'text-xs'
                ]"
            >
                <div class="flex items-center gap-x-2">
                    <component v-if="group.icon" :is="group.icon" class="h-4 w-4" aria-hidden="true" />
                    <span>{{ group.title }}</span>
                </div>
                <ChevronDownIcon
                    :class="[isOpen ? 'rotate-180 text-zinc-300' : 'text-zinc-500', 'h-4 w-4 flex-shrink-0 transition-transform duration-300 ease-in-out']"
                />
            </button>
            
            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="transform -translate-y-2 opacity-0"
                enter-to-class="transform translate-y-0 opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="transform translate-y-0 opacity-100"
                leave-to-class="transform -translate-y-2 opacity-0"
            >
                <!-- Contenedor del panel con línea vertical guía a la izquierda -->
                <div v-show="isOpen" class="space-y-1 pt-1 ml-2 relative before:absolute before:inset-y-0 before:left-[11px] before:w-px before:bg-zinc-800/50">
                    <template v-for="item in visibleItems" :key="item.name">
                        <Link
                            :href="item.href"
                            @click="$emit('close')"
                            :class="[
                                item.active
                                    ? 'bg-zinc-800 text-white border border-zinc-700/50 relative z-10'
                                    : 'text-zinc-400 hover:text-white hover:bg-zinc-800 relative z-10',
                                isMobile 
                                    ? 'group flex items-center px-3 py-3 text-base font-medium rounded-xl transition-all duration-200' 
                                    : 'group flex items-center px-3 py-2 text-sm font-medium rounded-xl transition-all duration-200'
                            ]"
                            prefetch
                        >
                            <component 
                                :is="item.icon" 
                                :class="[
                                    isMobile ? 'mr-4 h-6 w-6' : 'me-3 h-5 w-5',
                                    'flex-shrink-0 transition-colors duration-200',
                                    item.active ? 'text-white' : 'text-zinc-500 group-hover:text-zinc-300'
                                ]" 
                                aria-hidden="true" 
                            />
                            {{ item.name }}
                        </Link>
                    </template>
                </div>
            </transition>
        </div>
    </template>
</template>
