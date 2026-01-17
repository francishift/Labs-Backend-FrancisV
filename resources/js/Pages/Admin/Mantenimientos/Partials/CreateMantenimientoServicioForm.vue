<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import TextArea from '@/Components/TextArea.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { getTodayDate } from '@/Utils/date';

const props = defineProps({
    mantenimientoId: Number,
});

const emit = defineEmits(['close']);


const form = useForm({
    mantenimiento_id: props.mantenimientoId,
    descripcion: '',
    horas: 1,
    minutos: 0,
    fecha: getTodayDate(),
});

const submit = () => {
    // Calculamos los minutos totales para la base de datos
    const duracion_minutos = (form.horas * 60) + parseInt(form.minutos);
    
    form.transform((data) => ({
        ...data,
        duracion_minutos: duracion_minutos,
    })).post(route('admin.mantenimiento-servicios.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('close');
        },
    });
};
</script>

<template>
    <form id="create-mantenimiento-servicio-form" @submit.prevent="submit" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <InputLabel for="fecha" value="Fecha del servicio" />
                <TextInput id="fecha" v-model="form.fecha" type="date" required class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.fecha" />
            </div>
            <div>
                <InputLabel for="descripcion" value="Descripción de la tarea" />
                <TextArea id="descripcion" v-model="form.descripcion" class="mt-1 block w-full" rows="3" required placeholder="Describe el trabajo..." />
                <InputError class="mt-2" :message="form.errors.descripcion" />
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <InputLabel for="horas" value="Horas" />
                <TextInput id="horas" v-model="form.horas" type="number" min="0" required class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.horas" />
            </div>
            <div>
                <InputLabel for="minutos" value="Minutos" />
                <TextInput id="minutos" v-model="form.minutos" type="number" min="0" max="59" required class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.minutos" />
            </div>
        </div>
    </form>
</template>
