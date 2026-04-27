<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { BellIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { BellAlertIcon } from '@heroicons/vue/24/solid';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import { useFormatters } from '@/Composables/useFormatters';

const count = ref(0);
const notifications = ref([]);
const isOpen = ref(false);
const isLoading = ref(false);
let pollingInterval = null;

const { formatDateTime } = useFormatters();

const fetchNotifications = async () => {
    try {
        const response = await axios.get('/admin/api/notifications/unread');
        const previousCount = count.value;
        
        count.value = response.data.unreadCount;
        notifications.value = response.data.notifications;

        // Auto-abrir la bandeja si ha llegado una nueva notificación
        if (count.value > previousCount && count.value > 0) {
            isOpen.value = true;
        }
    } catch (e) {
        console.error('Error fetching silent notifications');
    }
};

const markAsRead = async (id, url) => {
    try {
        await axios.patch(`/admin/api/notifications/${id}/read`);
        // Remove from list or decrement count
        notifications.value = notifications.value.filter(n => n.id !== id);
        count.value = Math.max(0, count.value - 1);
        
        if (count.value === 0) {
            isOpen.value = false;
        }
        
        if (url) {
            window.location.href = url; // Navigates safely
        }
    } catch (e) {
        console.error(e);
    }
};

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
};

// Close when clicked outside
const closeOnClickOutside = (e) => {
    if (isOpen.value && !e.target.closest('.notification-container')) {
        isOpen.value = false;
    }
};

onMounted(() => {
    // Initial fetch
    fetchNotifications();
    
    // Polling every 1 minute (60,000 ms)
    pollingInterval = setInterval(fetchNotifications, 60000);
    
    document.addEventListener('click', closeOnClickOutside);
});

onUnmounted(() => {
    if (pollingInterval) clearInterval(pollingInterval);
    document.removeEventListener('click', closeOnClickOutside);
});
</script>

<template>
    <div class="relative notification-container shrink-0">
        <button 
            @click="toggleDropdown" 
            class="relative p-2 rounded-full text-gray-500 hover:bg-gray-100 dark:text-zinc-400 dark:hover:bg-zinc-800 transition focus:outline-none"
        >
            <span class="sr-only">Ver Notificaciones</span>
            <BellAlertIcon v-if="count > 0" class="h-6 w-6 text-emerald-500 animate-pulse" />
            <BellIcon v-else class="h-6 w-6" />
            
            <span v-if="count > 0" class="absolute -top-0 -right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform bg-red-600 rounded-full min-w-5">
                {{ count > 99 ? '99+' : count }}
            </span>
        </button>

        <!-- Dropdown Menu -->
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div 
                v-if="isOpen" 
                class="fixed inset-x-4 top-20 max-w-sm mx-auto sm:static sm:max-w-none sm:mx-0 sm:absolute sm:right-0 sm:top-auto sm:inset-x-auto sm:mt-2 w-auto sm:w-96 bg-white dark:bg-zinc-900 rounded-xl shadow-lg ring-1 ring-black ring-opacity-5 dark:ring-zinc-700/50 focus:outline-none z-50 overflow-hidden"
            >
                <div class="px-4 py-3 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Notificaciones</h3>
                    <button @click="isOpen = false" class="text-gray-400 hover:text-gray-600 dark:text-zinc-400 dark:hover:text-zinc-200 transition">
                        <span class="sr-only">Cerrar panel</span>
                        <XMarkIcon class="h-5 w-5" />
                    </button>
                </div>
                
                <div class="max-h-80 overflow-y-auto">
                    <div v-if="notifications.length === 0" class="p-6 text-center text-gray-500 dark:text-zinc-400 text-sm">
                        No hay notificaciones nuevas.
                    </div>
                    
                    <template v-else>
                        <button 
                            v-for="notification in notifications" 
                            :key="notification.id"
                            @click="markAsRead(notification.id, notification.data.url)"
                            class="w-full text-left px-4 py-3 hover:bg-gray-50 dark:hover:bg-zinc-800/50 border-b border-gray-50 dark:border-zinc-800 last:border-0 transition"
                        >
                            <div class="flex items-start">
                                <div class="shrink-0 pt-0.5">
                                    <div class="h-8 w-8 rounded-full bg-emerald-100 dark:bg-emerald-500/20 flex items-center justify-center">
                                        <BellAlertIcon class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                                    </div>
                                </div>
                                <div class="ml-3 w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-zinc-100">{{ notification.data.title || 'Notificación' }}</p>
                                    <p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5 line-clamp-2">{{ notification.data.message || '' }}</p>
                                    <p class="text-[10px] text-gray-400 mt-1">{{ formatDateTime(notification.created_at) }}</p>
                                </div>
                            </div>
                        </button>
                    </template>
                </div>
                
                <div class="border-t border-gray-100 dark:border-zinc-800 bg-gray-50 dark:bg-zinc-950 p-2 text-center relative z-50">
                    <Link 
                        :href="route('admin.notifications.index')" 
                        @click="isOpen = false"
                        class="text-xs font-semibold text-emerald-600 hover:text-emerald-500 dark:text-emerald-400 hover:underline inline-block w-full"
                    >
                        Ver todo el historial
                    </Link>
                </div>
            </div>
        </transition>
    </div>
</template>
