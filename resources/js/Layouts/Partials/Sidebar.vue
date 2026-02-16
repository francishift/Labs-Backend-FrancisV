<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
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
    DocumentTextIcon
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
        show: props.auth?.roles?.some(r => ['admin', 'coordinador'].includes(r)),
        items: [
            { name: 'Proyectos', href: route('admin.proyectos.index'), icon: BriefcaseIcon, active: route().current('admin.proyectos.*'), role: 'coordinador' },
            { name: 'Mantenimientos', href: route('admin.mantenimientos.index'), icon: ClockIcon, active: route().current('admin.mantenimientos.*'), role: 'admin' },
            { name: 'Extensiones', href: route('admin.extensiones.index'), icon: PuzzlePieceIcon, active: route().current('admin.extensiones.*'), role: 'admin' },
            { name: 'Software / Hosting', href: route('admin.softwares.index'), icon: ComputerDesktopIcon, active: route().current('admin.softwares.*'), role: 'admin' },
            { name: 'Tareas de Mantenimiento', href: route('admin.mantenimiento-servicios.index'), icon: ClipboardDocumentCheckIcon, active: route().current('admin.mantenimiento-servicios.*'), role: 'admin' },
            { name: 'Servicios', href: route('admin.servicios.index'), icon: WrenchScrewdriverIcon, active: route().current('admin.servicios.*'), role: 'coordinador' },
            { name: 'Resumen horas', href: route('admin.resumen-horas.index'), icon: ClockIcon, active: route().current('admin.resumen-horas.*'), role: 'admin' }
        ]
    },
    {
        title: 'Accesos y Seguridad',
        show: props.auth?.roles?.includes('admin'),
        items: [
            { name: 'Usuarios', href: route('admin.usuarios.index'), icon: UsersIcon, active: route().current('admin.usuarios.*'), role: 'admin' },
            { name: 'Logs VPN', href: route('admin.logs.index'), icon: ShieldCheckIcon, active: route().current('admin.logs.*'), role: 'admin' }
        ]
    },
    {
        title: 'Contabilidad',
        show: props.auth?.roles?.includes('admin'),
        items: [
            { name: 'Clientes', href: route('admin.clientes.index'), icon: UserGroupIcon, active: route().current('admin.clientes.*'), role: 'admin' },
            { name: 'Presupuestos', href: route('admin.holded.presupuestos.index'), icon: DocumentTextIcon, active: route().current('admin.holded.presupuestos.*'), role: 'admin' },
            { name: 'Facturas ventas', href: route('admin.holded.facturas.index'), icon: DocumentTextIcon, active: route().current('admin.holded.facturas.*'), role: 'admin' },
            { name: 'Facturas compras', href: route('admin.purchase-facturas.index'), icon: DocumentTextIcon, active: route().current('admin.purchase-facturas.*'), role: 'admin' }
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
            <nav class="flex-1 px-4 py-6 space-y-8 overflow-y-auto custom-scrollbar">
                <div v-for="(group, idx) in navigationGroups" :key="idx">
                    <template v-if="group.show !== false">
                        <h3 v-if="group.title" class="px-3 text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-3">
                            {{ group.title }}
                        </h3>
                        <div class="space-y-1">
                            <template v-for="item in group.items" :key="item.name">
                                <Link
                                    v-if="!item.role || hasRole(item.role)"
                                    :href="item.href"
                                    :class="[
                                        item.active
                                            ? 'bg-zinc-800 text-white border border-zinc-700/50'
                                            : 'text-zinc-400 hover:text-white hover:bg-zinc-800',
                                        'group flex items-center px-3 py-2.5 text-sm font-medium rounded-xl transition-all duration-200'
                                    ]"
                                    prefetch
                                >
                                    <component :is="item.icon" class="me-3 flex-shrink-0 h-5 w-5" aria-hidden="true" />
                                    {{ item.name }}
                                </Link>
                            </template>
                        </div>
                    </template>
                </div>
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

    <!-- Mobile Sidebar (Drawer) -->
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
            <div @click="$emit('close')" class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>
        </transition>

        <div class="fixed inset-0 flex">
            <!-- Sidebar panel -->
            <transition
                appear
                enter-active-class="transition ease-in-out duration-300 transform"
                enter-from-class="-translate-x-full"
                enter-to-class="translate-x-0"
                leave-active-class="transition ease-in-out duration-300 transform"
                leave-from-class="translate-x-0"
                leave-to-class="-translate-x-full"
            >
                <div class="relative flex-1 flex flex-col max-w-xs w-full bg-zinc-900 pt-5 pb-4 will-change-transform translate-z-0">
                    <div class="absolute top-0 right-0 -mr-12 pt-2">
                        <button 
                            @click="$emit('close')"
                            class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white"
                        >
                            <span class="sr-only">Cerrar menú</span>
                            <XMarkIcon class="h-6 w-6 text-white" aria-hidden="true" />
                        </button>
                    </div>

                    <div class="flex-shrink-0 flex items-center px-6">
                        <Link :href="route('dashboard')" class="flex items-center justify-start w-full" @click="$emit('close')">
                            <ApplicationLogo class="h-10 w-auto fill-current text-white" />
                        </Link>
                    </div>

                    <div class="mt-8 flex-1 h-0 overflow-y-auto px-4 custom-scrollbar">
                        <nav class="space-y-8">
                            <div v-for="(group, idx) in navigationGroups" :key="idx">
                                <template v-if="group.show !== false">
                                    <h3 v-if="group.title" class="px-3 text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-3">
                                        {{ group.title }}
                                    </h3>
                                    <div class="space-y-1">
                                        <template v-for="item in group.items" :key="item.name">
                                            <Link
                                                v-if="!item.role || hasRole(item.role)"
                                                :href="item.href"
                                                @click="$emit('close')"
                                                :class="[
                                                    item.active
                                                        ? 'bg-zinc-800 text-white border border-zinc-700/50'
                                                        : 'text-zinc-400 hover:text-white hover:bg-zinc-800',
                                                    'group flex items-center px-3 py-3 text-base font-medium rounded-xl transition-all duration-200'
                                                ]"
                                            >
                                                <component :is="item.icon" class="mr-4 flex-shrink-0 h-6 w-6 text-zinc-300" aria-hidden="true" />
                                                {{ item.name }}
                                            </Link>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </nav>
                    </div>

                    <!-- Pie del Sidebar Móvil -->
                    <div class="mt-auto p-4 border-t border-zinc-800/50 flex items-center justify-between gap-x-2">
                        <div class="flex items-center gap-x-3 overflow-hidden">
                            <ShieldCheckIcon class="h-5 w-5 text-emerald-500/80 flex-shrink-0" />
                            <span class="text-zinc-300 text-sm font-semibold truncate">
                                {{ auth.user.name }}
                            </span>
                        </div>

                        <Link
                            v-if="hasRole(settingsLink.role)"
                            :href="settingsLink.href"
                            @click="$emit('close')"
                            title="Ajustes de sistema"
                            :class="[
                                settingsLink.active
                                    ? 'text-white bg-zinc-800 border border-zinc-700/50'
                                    : 'text-zinc-400 hover:text-white hover:bg-zinc-800',
                                'p-2.5 rounded-xl transition-all duration-200 flex-shrink-0'
                            ]"
                        >
                            <component :is="settingsLink.icon" class="h-6 w-6" aria-hidden="true" />
                        </Link>
                    </div>
                </div>
            </transition>

            <div class="flex-shrink-0 w-14" aria-hidden="true">
                <!-- Dummy element to force sidebar to shrink to fit close button -->
            </div>
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
