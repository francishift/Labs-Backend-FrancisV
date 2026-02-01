<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage, Link } from '@inertiajs/vue3';
import QrcodeVue from 'qrcode.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import DialogModal from '@/Components/DialogModal.vue';
import { ShieldCheckIcon, PlusIcon, TrashIcon, QrCodeIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    user: Object,
});

const vpnConfig = computed(() => usePage().props.flash?.vpn_config);
const showQrModal = ref(false);
const newDeviceForm = useForm({
    name: '',
});

const addDevice = () => {
    newDeviceForm.post(route('admin.vpn.store', props.user.id), {
        preserveScroll: true,
        onSuccess: () => {
            newDeviceForm.reset();
            showQrModal.value = true;
        },
    });
};

const closeQrModal = () => {
    showQrModal.value = false;
};

const downloadConfig = () => {
    const element = document.createElement('a');
    const file = new Blob([vpnConfig.value], {type: 'text/plain'});
    element.href = URL.createObjectURL(file);
    element.download = `${newDeviceForm.name || 'vpn'}.conf`;
    document.body.appendChild(element);
    element.click();
    document.body.removeChild(element);
};
</script>

<template>
    <div class="mt-8 border-t border-gray-200 dark:border-gray-700 pt-8">
        <div class="flex items-center gap-2 mb-4">
            <ShieldCheckIcon class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Gestión de Acceso VPN</h3>
        </div>

        <!-- Listado de dispositivos -->
        <div v-if="user.vpn_devices?.length > 0" class="space-y-4 mb-6">
            <div 
                v-for="device in user.vpn_devices" 
                :key="device.id"
                class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700"
            >
                <div class="flex items-center gap-4">
                    <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-full">
                        <ShieldCheckIcon class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">{{ device.name }}</p>
                        <div class="flex items-center gap-2 text-xs text-gray-500 font-mono">
                            <span>{{ device.internal_ip }}</span>
                            <span class="text-gray-300">|</span>
                            <span :class="device.last_handshake_at ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-gray-400'">
                                {{ device.last_handshake_at ? 'Última conexión: ' + new Date(device.last_handshake_at).toLocaleString() : 'Nunca conectado' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2">
                    <!-- Usamos Link de Inertia directamente para garantizar POST -->
                    <Link
                        :href="route('admin.vpn.destroy', device.id)"
                        method="post"
                        as="button"
                        class="inline-flex items-center justify-center px-2 py-1 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                        preserve-scroll
                        @click="e => { if(!confirm('CONFIRMAR: ¿Borrar definitivamente este dispositivo VPN?')) e.preventDefault() }"
                    >
                        <TrashIcon class="h-4 w-4" />
                    </Link>
                </div>
            </div>
        </div>
        <div v-else class="text-sm text-gray-500 mb-6 italic">
            Este usuario no tiene dispositivos VPN vinculados.
        </div>

        <!-- Formulario nuevo dispositivo -->
        <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg border border-dashed border-gray-300 dark:border-gray-600">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Generar nuevo acceso</p>
            <div class="flex gap-3">
                <div class="flex-1">
                    <TextInput
                        id="device_name"
                        v-model="newDeviceForm.name"
                        type="text"
                        placeholder="Ej: Portátil Trabajo"
                        class="w-full text-sm"
                        @keyup.enter="addDevice"
                    />
                </div>
                <PrimaryButton :disabled="newDeviceForm.processing" @click="addDevice" class="flex items-center gap-2">
                    <PlusIcon class="h-4 w-4" />
                    Generar
                </PrimaryButton>
            </div>
        </div>

        <!-- Modal para el QR -->
        <DialogModal :show="showQrModal" @close="closeQrModal">
            <template #title>
                <div class="flex items-center gap-2">
                    <QrCodeIcon class="h-6 w-6 text-green-600" />
                    <span>Configuración VPN Generada</span>
                </div>
            </template>

            <template #content>
                <div class="text-center py-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        Escanea el código con el móvil o descarga el archivo para PC/Mac.
                    </p>
                    
                    <div class="inline-block p-6 bg-white rounded-2xl shadow-xl border-8 border-white mb-6">
                        <QrcodeVue 
                            v-if="vpnConfig" 
                            :value="vpnConfig" 
                            :size="300" 
                            level="H" 
                            background="#ffffff"
                            foreground="#000000"
                            class="mx-auto"
                        />
                    </div>

                    <div class="flex justify-center mb-6">
                        <SecondaryButton @click="downloadConfig" class="flex items-center gap-2">
                            <PlusIcon class="h-4 w-4 rotate-45" /> <!-- Usando icono de plus rotado como descarga o similar -->
                            Descargar archivo .conf
                        </SecondaryButton>
                    </div>

                    <div class="text-left bg-gray-900 text-gray-300 p-4 rounded-lg overflow-x-auto max-h-40 text-xs font-mono mb-4">
                        <pre>{{ vpnConfig }}</pre>
                    </div>

                    <div class="bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-400 p-4 text-left">
                        <p class="text-xs text-amber-700 dark:text-amber-400">
                            <strong>Aviso:</strong> Esta configuración solo se muestra una vez por seguridad. Si la pierdes, deberás borrar el dispositivo y generar uno nuevo.
                        </p>
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeQrModal">Cerrar</SecondaryButton>
            </template>
        </DialogModal>
    </div>
</template>
