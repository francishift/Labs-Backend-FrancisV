<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { BellAlertIcon, BellSlashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    class: {
        type: String,
        default: ''
    }
});

const isSubscribed = ref(false);
const isProcessing = ref(true);

const VAPID_PUBLIC_KEY = import.meta.env.VITE_VAPID_PUBLIC_KEY || ''; 

const urlBase64ToUint8Array = (base64String) => {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
};

const checkSubscriptionStatus = async () => {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        isProcessing.value = false;
        return;
    }

    try {
        await navigator.serviceWorker.register('/sw.js');
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();
        isSubscribed.value = !!subscription;
    } catch (e) {
        console.error('Error al revisar suscripción', e);
    } finally {
        isProcessing.value = false;
    }
};

const togglePush = async () => {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        alert('Este navegador no soporta notificaciones push');
        return;
    }

    isProcessing.value = true;

    try {
        await navigator.serviceWorker.register('/sw.js');
        const registration = await navigator.serviceWorker.ready;
        let subscription = await registration.pushManager.getSubscription();

        if (isSubscribed.value && subscription) {
            // Unsubscribe
            await subscription.unsubscribe();
            // Notificar al backend deshabilita endpoint actual
            await axios.post(route('admin.push-subscriptions.destroy'), {
                endpoint: subscription.endpoint
            });
            isSubscribed.value = false;
            alert('Notificaciones Push deshabilitadas.');
        } else {
            // Subscribe
            const permission = await window.Notification.requestPermission();
            
            if (permission !== 'granted') {
                alert('Permiso denegado para notificaciones.');
                isProcessing.value = false;
                return;
            }

            if(!VAPID_PUBLIC_KEY) {
                alert('Falta la clave VAPID_PUBLIC_KEY en .env');
                isProcessing.value = false;
                return;
            }

            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
            });

            await axios.post(route('admin.push-subscriptions.store'), subscription);
            isSubscribed.value = true;
            alert('¡Notificaciones Push habilitadas con éxito!');
        }
    } catch (e) {
        console.error('Error gestionando suscripción push', e);
        alert('Hubo un error al modificar la suscripción push. Asegúrate de tener permisos habilitados en Configuración.');
    } finally {
        isProcessing.value = false;
    }
};

onMounted(() => {
    checkSubscriptionStatus();
});
</script>

<template>
    <SecondaryButton 
        type="button" 
        @click="togglePush" 
        :disabled="isProcessing"
        :class="props.class"
        :title="isSubscribed ? 'Deshabilitar Notificaciones' : 'Habilitar Notificaciones'"
    >
        <template v-if="isProcessing">
            <svg class="animate-spin h-4 w-4 text-gray-500 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Procesando...
        </template>
        <template v-else-if="isSubscribed">
            <BellSlashIcon class="h-4 w-4 text-red-500 mr-2" />
            Deshabilitar Push
        </template>
        <template v-else>
            <BellAlertIcon class="h-4 w-4 text-emerald-500 mr-2" />
            Habilitar Push
        </template>
    </SecondaryButton>
</template>
