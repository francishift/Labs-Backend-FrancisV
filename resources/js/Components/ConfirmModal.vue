<script setup>
import DialogModal from './DialogModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: '¿Estás seguro?',
    },
    content: {
        type: String,
        default: 'Esta acción no se puede deshacer.',
    },
    confirmText: {
        type: String,
        default: 'Eliminar',
    },
    cancelText: {
        type: String,
        default: 'Cancelar',
    },
});

const emit = defineEmits(['close', 'confirm']);

const close = () => {
    emit('close');
};

const confirm = () => {
    emit('confirm');
};
</script>

<template>
    <DialogModal :show="show" @close="close" maxWidth="md">
        <template #title>
            {{ title }}
        </template>

        <template #content>
            {{ content }}
        </template>

        <template #footer>
            <SecondaryButton @click="close"> {{ cancelText }} </SecondaryButton>

            <DangerButton @click="confirm"> {{ confirmText }} </DangerButton>
        </template>
    </DialogModal>
</template>
