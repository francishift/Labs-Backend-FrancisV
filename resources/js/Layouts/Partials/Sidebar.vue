<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
import SidebarAccordion from '@/Components/SidebarAccordion.vue'
import { 
    Squares2X2Icon, 
    ShieldCheckIcon, 
    UsersIcon, 
    UserGroupIcon, 
    BriefcaseIcon, 
    WrenchScrewdriverIcon,
    PuzzlePieceIcon,
    ClockIcon,
    ClipboardDocumentCheckIcon,
    XMarkIcon,
    Cog6ToothIcon,
    ComputerDesktopIcon,
    DocumentTextIcon,
    FolderIcon,
    BanknotesIcon,
    KeyIcon,
    CalendarIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    auth: Object,
    showMobile: Boolean
})

const emit = defineEmits(['close'])

const hasRole = (role) => {
    if (!props.auth?.roles) return false
    if (role === 'coordinador') {
        return props.auth.roles.some(r => ['admin', 'coordinador'].includes(r))
    }
    return props.auth.roles.includes(role)
}

const navigationGroups = computed(() => [
    {
        title: null,
        items: [
            { name: 'Panel de control', href: route('dashboard'), icon: Squares2X2Icon, active: route().current('dashboard') || route().current('admin.dashboard') }
        ]
    },
    {
        title: 'Gestión Administrativa',
        icon: FolderIcon,
        defaultOpen: true,
        show: props.auth?.roles?.some(r => ['admin', 'coordinador'].includes(r)),
        items: [
            { name: 'Informe general', href: route('admin.resumen-horas.index'), icon: ClockIcon, active: route().current('admin.resumen-horas.*'), role: 'admin' },
            { name: 'Proyectos', href: route('admin.proyectos.index'), icon: BriefcaseIcon, active: route().current('admin.proyectos.*'), role: 'coordinador' },
            { name: 'Mantenimientos', href: route('admin.mantenimientos.index'), icon: ClockIcon, active: route().current('admin.mantenimientos.*'), role: 'admin' },
            { name: 'Extensiones', href: route('admin.extensiones.index'), icon: PuzzlePieceIcon, active: route().current('admin.extensiones.*'), role: 'admin' },
            { name: 'Software / Hosting', href: route('admin.softwares.index'), icon: ComputerDesktopIcon, active: route().current('admin.softwares.*'), role: 'admin' },
            { name: 'Tareas de Mantenimiento', href: route('admin.mantenimiento-servicios.index'), icon: ClipboardDocumentCheckIcon, active: route().current('admin.mantenimiento-servicios.*'), role: 'admin' },
            { name: 'Servicios', href: route('admin.servicios.index'), icon: WrenchScrewdriverIcon, active: route().current('admin.servicios.*'), role: 'coordinador' },
            { name: 'Calendario', href: route('admin.calendar.index'), icon: CalendarIcon, active: route().current('admin.calendar.*'), role: 'coordinador' }
        ]
    },
    {
        title: 'Contabilidad',
        icon: BanknotesIcon,
        show: props.auth?.roles?.includes('admin'),
        items: [
            { name: 'Clientes', href: route('admin.clientes.index'), icon: UserGroupIcon, active: route().current('admin.clientes.*'), role: 'admin' },
            { name: 'Presupuestos', href: route('admin.presupuestos.index'), icon: DocumentTextIcon, active: route().current('admin.presupuestos.*'), role: 'admin' },
            { name: 'Facturas ventas', href: route('admin.facturas.index'), icon: DocumentTextIcon, active: route().current('admin.facturas.*'), role: 'admin' },
            { name: 'Facturas compras', href: route('admin.purchase-facturas.index'), icon: DocumentTextIcon, active: route().current('admin.purchase-facturas.*'), role: 'admin' }
        ]
    },
    {
        title: 'Usuarios y Logs',
        icon: KeyIcon,
        show: props.auth?.roles?.includes('admin'),
        items: [
            { name: 'Usuarios', href: route('admin.usuarios.index'), icon: UsersIcon, active: route().current('admin.usuarios.*'), role: 'admin' },
            { name: 'Logs VPN', href: route('admin.logs.index'), icon: ShieldCheckIcon, active: route().current('admin.logs.*'), role: 'admin' }
        ]
    }
])

const settingsLink = { name: 'Ajustes', href: route('admin.settings.index'), icon: Cog6ToothIcon, active: route().current('admin.settings.*'), role: 'admin' }
</script>

<template>
    <!-- Desktop Sidebar -->
    <aside class="hidden lg:flex lg:flex-col lg:w-72 lg:fixed lg:inset-y-0 bg-zinc-900 border-r border-zinc-800 z-50 transition-all duration-300">
        <div class="flex flex-col flex-1 min-h-0">
            <!-- Logo -->
            <div class="flex items-center h-16 flex-shrink-0 px-6 bg-zinc-900 border-b border-zinc-800/50">
                <Link :href="route('dashboard')" class="flex items-center justify-start w-full">
                    <ApplicationLogo class="h-10 w-auto fill-current text-white" />
                </Link>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto custom-scrollbar">
                <SidebarAccordion 
                    v-for="(group, idx) in navigationGroups" 
                    :key="idx"
                    :group="group"
                    :auth="auth"
                    @close="$emit('close')"
                />
            </nav>

            <!-- Bottom Section (Compact Username + Settings) -->
            <div class="p-4 bg-zinc-900/50 border-t border-zinc-800/50 flex items-center justify-between gap-x-2">
                <div class="flex items-center gap-x-2 overflow-hidden">
                    <ShieldCheckIcon class="h-4 w-4 text-emerald-500/80 flex-shrink-0" />
                    <span class="text-zinc-400 text-xs font-medium truncate" :title="auth.user.name">
                        {{ auth.user.name }}
                    </span>
                </div>

                <Link
                    v-if="hasRole(settingsLink.role)"
                    :href="settingsLink.href"
                    title="Ajustes de sistema"
                    :class="[
                        settingsLink.active
                            ? 'text-white bg-zinc-800 border border-zinc-700/50'
                            : 'text-zinc-400 hover:text-white hover:bg-zinc-800',
                        'p-2 rounded-lg transition-all duration-200 flex-shrink-0'
                    ]"
                    prefetch
                >
                    <component :is="settingsLink.icon" class="h-5 w-5" aria-hidden="true" />
                </Link>
            </div>
        </div>
    </aside>

    <!-- Mobile Sidebar (Full Screen Modal Grid) -->
    <div v-if="showMobile" class="relative z-[60] lg:hidden" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <transition
            appear
            enter-active-class="transition-opacity ease-linear duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity ease-linear duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div @click="$emit('close')" class="fixed inset-0 bg-black/80 backdrop-blur-md"></div>
        </transition>

        <div class="fixed inset-0 flex justify-center items-center p-4 sm:p-6">
            <!-- Full-Screen Panel -->
            <transition
                appear
                enter-active-class="transition ease-out duration-300 transform"
                enter-from-class="opacity-0 translate-y-8 scale-95"
                enter-to-class="opacity-100 translate-y-0 scale-100"
                leave-active-class="transition ease-in duration-200 transform"
                leave-from-class="opacity-100 translate-y-0 scale-100"
                leave-to-class="opacity-0 translate-y-8 scale-95"
            >
                <div class="relative w-full h-full max-h-[90vh] bg-zinc-900 rounded-3xl shadow-2xl flex flex-col overflow-hidden border border-zinc-800/60 will-change-transform">
                    <!-- Header -->
                    <div class="flex flex-shrink-0 items-center justify-between px-5 py-4 border-b border-zinc-800/50">
                        <Link :href="route('dashboard')" class="flex items-center" @click="$emit('close')">
                            <ApplicationLogo class="h-7 w-auto fill-current text-white" />
                        </Link>
                        
                        <div class="flex items-center gap-3">
                            <!-- Panel de Control Integrado en el Header -->
                            <Link 
                                :href="route('dashboard')"
                                @click="$emit('close')"
                                :class="[
                                    route().current('dashboard') || route().current('admin.dashboard')
                                        ? 'bg-zinc-800 text-white border-zinc-700'
                                        : 'bg-zinc-900/50 text-zinc-400 border-zinc-800/60 hover:text-white hover:bg-zinc-800',
                                    'flex items-center gap-1.5 px-3 py-2 rounded-xl border transition-colors'
                                ]"
                            >
                                <Squares2X2Icon 
                                    class="h-4 w-4" 
                                    :class="route().current('dashboard') || route().current('admin.dashboard') ? 'text-emerald-500' : 'text-zinc-400'" 
                                />
                                <span class="text-[11px] font-bold tracking-wide">Panel</span>
                            </Link>

                            <button 
                                @click="$emit('close')"
                                class="flex items-center justify-center h-9 w-9 rounded-full bg-zinc-800/50 hover:bg-zinc-800 text-zinc-400 hover:text-white transition-colors focus:outline-none"
                            >
                                <span class="sr-only">Cerrar menú</span>
                                <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                            </button>
                        </div>
                    </div>

                    <!-- Scrollable Content: Grid -->
                    <div class="flex-1 overflow-y-auto px-4 py-6 custom-scrollbar">
                        <div class="space-y-8">
                            <template v-for="(group, idx) in navigationGroups" :key="idx">
                                <!-- Omitimos el idx === 0 porque es el Panel de Control, que ya está en el header -->
                                <div v-if="group.show !== false && idx !== 0">
                                    <h3 v-if="group.title" class="px-2 text-[11px] font-bold text-zinc-500 uppercase tracking-widest mb-4">
                                        {{ group.title }}
                                    </h3>
                                    <div class="grid grid-cols-2 gap-3 sm:gap-4">
                                        <template v-for="item in group.items" :key="item.name">
                                            <Link 
                                                v-if="!item.role || hasRole(item.role)"
                                                :href="item.href"
                                                @click="$emit('close')"
                                                :class="[
                                                    item.active 
                                                        ? 'bg-zinc-800 border-zinc-700 text-white shadow-sm' 
                                                        : 'bg-zinc-900/40 border-zinc-800/50 text-zinc-400 hover:text-white hover:bg-zinc-800 hover:border-zinc-700',
                                                    'flex flex-col items-center justify-center p-4 sm:p-5 rounded-2xl border transition-all duration-200'
                                                ]"
                                            >
                                                <component 
                                                    :is="item.icon" 
                                                    :class="[item.active ? 'text-emerald-500' : 'text-zinc-500', 'h-7 w-7 mb-2.5']" 
                                                    aria-hidden="true" 
                                                />
                                                <span class="text-[11px] sm:text-xs font-semibold text-center leading-tight">{{ item.name }}</span>
                                            </Link>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex-shrink-0 p-5 bg-zinc-900/80 border-t border-zinc-800/50 flex items-center justify-between">
                        <div class="flex items-center gap-x-3 overflow-hidden">
                            <div class="h-10 w-10 rounded-full bg-zinc-800 flex items-center justify-center border border-zinc-700">
                                <ShieldCheckIcon class="h-5 w-5 text-emerald-500" />
                            </div>
                            <div class="flex flex-col truncate">
                                <span class="text-white text-sm font-semibold truncate">{{ auth.user.name }}</span>
                                <span class="text-zinc-500 text-xs truncate">{{ auth.roles?.includes('admin') ? 'Administrador' : 'Usuario' }}</span>
                            </div>
                        </div>

                        <Link
                            v-if="hasRole(settingsLink.role)"
                            :href="settingsLink.href"
                            @click="$emit('close')"
                            class="p-2.5 rounded-xl bg-zinc-800 border border-zinc-700 text-zinc-400 hover:text-white transition-colors flex-shrink-0 ml-2"
                        >
                            <component :is="settingsLink.icon" class="h-5 w-5" aria-hidden="true" />
                        </Link>
                    </div>
                </div>
            </transition>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #3f3f46;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #52525b;
}

.will-change-transform {
    will-change: transform;
}
.translate-z-0 {
    transform: translateZ(0);
}
</style>
