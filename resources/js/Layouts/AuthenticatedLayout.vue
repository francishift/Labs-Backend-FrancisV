<script setup>
import { ref, watch, onUnmounted, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { Cog6ToothIcon, Bars3Icon } from '@heroicons/vue/24/outline';
import Footer from '@/Components/Footer.vue';

// Parciales
import Sidebar from './Partials/Sidebar.vue';
import UserDropdown from './Partials/UserDropdown.vue';
import DarkModeToggle from './Partials/DarkModeToggle.vue';
import FlashMessages from '@/Components/FlashMessages.vue';

const page = usePage();
const isDark = ref(false);
const showingNavigationDropdown = ref(false);

const toggleDarkMode = () => {
    isDark.value = !isDark.value;
    document.documentElement.classList.toggle('dark', isDark.value);
    localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
};

onMounted(() => {
    isDark.value = localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches);
    document.documentElement.classList.toggle('dark', isDark.value);

    // Sistema Robusto de Enrutamiento para iOS PWA (PostMessage Async Protocol)
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', (event) => {
            if (event.data && event.data.type === 'PWA_ROUTING' && event.data.url) {
                const targetUrl = event.data.url;
                const currentPath = window.location.pathname;
                const targetPath = new URL(targetUrl, window.location.origin).pathname;
                
                // Solo navegar si realmente es distinto al actual
                if (currentPath !== targetPath) {
                    router.visit(targetUrl);
                }
            }
        });
    }

});

watch(showingNavigationDropdown, (value) => {
    document.body.style.overflow = value ? 'hidden' : '';
});

const removeFinishEventListener = router.on('finish', () => {
    showingNavigationDropdown.value = false;
});

onUnmounted(() => {
    document.body.style.overflow = '';
    removeFinishEventListener();
});
</script>

<template>
    <div class="min-h-screen bg-gray-100 dark:bg-zinc-950 transition-colors duration-300 scroll-pt-20">
        <!-- Sidebar component (Desktop and Mobile) -->
        <Sidebar 
            :auth="$page.props.auth" 
            :show-mobile="showingNavigationDropdown" 
            @close="showingNavigationDropdown = false" 
        />

        <!-- Main Area -->
        <div class="lg:pl-72 flex flex-col flex-1 min-h-screen">
            <!-- Topbar -->
            <nav class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 dark:border-zinc-800 bg-gray-50/80 dark:bg-zinc-900/50 backdrop-blur-md px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
                <!-- Mobile Toggle -->
                <button 
                    type="button" 
                    class="-m-2.5 p-2.5 text-gray-700 dark:text-zinc-400 lg:hidden" 
                    @click="showingNavigationDropdown = true"
                >
                    <span class="sr-only">Abrir menú lateral</span>
                    <Bars3Icon class="h-6 w-6" aria-hidden="true" />
                </button>

                <!-- Separator on mobile -->
                <div class="h-6 w-px bg-gray-200 dark:border-zinc-800 lg:hidden" aria-hidden="true"></div>

                <!-- Mobile Logo Icon -->
                <div class="lg:hidden flex items-center">
                    <img src="/logo-icono.png" alt="Logo" class="h-8 w-auto ml-2 dark:invert" />
                </div>

                <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
                    <!-- Spacer for alignment -->
                    <div class="flex flex-1"></div>

                    <div class="flex items-center gap-x-4 lg:gap-x-6">

                        <!-- Separator -->
                        <div class="hidden lg:block lg:h-6 lg:w-px lg:bg-gray-200 dark:lg:bg-zinc-800" aria-hidden="true"></div>

                        <!-- Dark Mode -->
                        <DarkModeToggle :is-dark="isDark" @toggle="toggleDarkMode" />

                        <!-- Separator -->
                        <div class="h-6 w-px bg-gray-200 dark:bg-zinc-800 lg:ms-2" aria-hidden="true"></div>

                        <!-- User Dropdown -->
                        <UserDropdown :user="$page.props.auth.user" />
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header class="bg-gray-50 dark:bg-zinc-900 border-b border-gray-200 dark:border-zinc-800 px-3 sm:px-5" v-if="$slots.header">
                <div class="mx-auto max-w-7xl py-3">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 pb-8 px-3 sm:px-5">
                <div class="mx-auto max-w-7xl">
                    <FlashMessages />
                    <Transition name="page-fade" mode="out-in">
                        <div :key="$page.url">
                            <slot />
                        </div>
                    </Transition>
                </div>
            </main>

            <Footer class="mt-auto" />
        </div>
    </div>
</template>

<style>
/* Estilos globales para scrolls en diseño de panel */
.scroll-pt-bar {
    scroll-padding-top: 5rem;
}
</style>