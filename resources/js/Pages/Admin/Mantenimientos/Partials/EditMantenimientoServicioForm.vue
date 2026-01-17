<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import TextArea from '@/Components/TextArea.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { formatDateForInput } from '@/Utils/date';

const props = defineProps({
    servicio: Object,
});

const emit = defineEmits(['close']);

const form = useForm({
    descripcion: '',
    horas: 0,
    minutos: 0,
    fecha: '',
});

onMounted(() => {
    if (props.servicio) {
        form.descripcion = props.servicio.descripcion;
        form.horas = Math.floor(props.servicio.duracion_minutos / 60);
        form.minutos = props.servicio.duracion_minutos % 60;
        form.fecha = formatDateForInput(props.servicio.fecha);
    }
});

const submit = () => {
    const duracion_minutos = (parseInt(form.horas) * 60) + parseInt(form.minutos);
    
    form.transform((data) => ({
        ...data,
        duracion_minutos: duracion_minutos,
    })).patch(route('admin.mantenimiento-servicios.update', props.servicio.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
        },
    });
};
</script>

<template>
    <form id="edit-mantenimiento-servicio-form" @submit.prevent="submit" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <InputLabel for="edit_fecha" value="Fecha del servicio" />
                <TextInput id="edit_fecha" v-model="form.fecha" type="date" required class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.fecha" />
            </div>
            <div>
                <InputLabel for="edit_desc" value="Descripción de la tarea" />
                <TextArea id="edit_desc" v-model="form.descripcion" class="mt-1 block w-full" rows="3" required />
                <InputError class="mt-2" :message="form.errors.descripcion" />
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <InputLabel for="edit_horas" value="Horas" />
                <TextInput id="edit_horas" v-model="form.horas" type="number" min="0" required class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.horas" />
            </div>
            <div>
                <InputLabel for="edit_minutos" value="Minutos" />
                <TextInput id="edit_minutos" v-model="form.minutos" type="number" min="0" max="59" required class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.minutos" />
            </div>
        </div>
    </form>
</template>
