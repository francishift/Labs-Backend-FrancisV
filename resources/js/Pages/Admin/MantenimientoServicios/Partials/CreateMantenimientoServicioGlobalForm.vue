<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import TextArea from '@/Components/TextArea.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useForm } from '@inertiajs/vue3';
import { getTodayDate } from '@/Utils/date';

const props = defineProps({
    mantenimientos: Array,
});

const emit = defineEmits(['close']);

const form = useForm({
    mantenimiento_id: '',
    descripcion: '',
    horas: 0,
    minutos: 0,
    fecha: getTodayDate(),
});

const submit = () => {
    const duracion_minutos = (parseInt(form.horas) * 60) + parseInt(form.minutos);
    
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
    <form id="create-mantenimiento-servicio-global-form" @submit.prevent="submit" class="space-y-4">
        <div>
            <InputLabel for="mantenimiento_id" value="Mantenimiento (Aplicación)" />
            <SearchableSelect
                id="mantenimiento_id"
                v-model="form.mantenimiento_id"
                :options="mantenimientos"
                label-key="aplicacion"
                placeholder="Seleccionar mantenimiento..."
                class="mt-1"
                required
            />
            <InputError class="mt-2" :message="form.errors.mantenimiento_id" />
        </div>

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
