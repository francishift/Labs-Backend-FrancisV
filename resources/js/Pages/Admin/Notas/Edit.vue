<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import Card from '@/Components/Card.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import TextArea from '@/Components/TextArea.vue'
import InputError from '@/Components/InputError.vue'

import DangerButton from '@/Components/DangerButton.vue'

const props = defineProps({
    nota: Object,
})

const form = useForm({
    fecha: props.nota.fecha ? props.nota.fecha.substring(0, 10) : '',
    hora: props.nota.hora ? props.nota.hora.substring(0, 5) : '',
    comentario: props.nota.comentario,
    notificacion_minutos_antes: props.nota.notificacion_minutos_antes,
})

const formOptions = [
    { value: -1, label: 'Sin notificación' },
    { value: 0, label: 'A la hora indicada' },
    { value: 1, label: '1 Minuto antes' },
    { value: 5, label: '5 Minutos antes' },
    { value: 15, label: '15 Minutos antes' },
    { value: 60, label: '1 Hora antes' },
    { value: 1440, label: '1 Día antes' },
]

const saveNota = () => {
    form.patch(route('admin.notas.update', props.nota.id), {
        onSuccess: () => {
            // El usuario será redirigido gracias al back() original del controller
        },
    });
}

const deleteNota = () => {
    if (confirm('¿Estás seguro de que deseas eliminar esta nota? Esta acción no se puede deshacer.')) {
        form.delete(route('admin.notas.destroy', props.nota.id));
    }
}
</script>

<template>
    <Head title="Editar Nota" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader title="Detalle de Nota">
                <template #actions>
                    <SecondaryButton type="button" @click="$inertia.visit(route('admin.notas.index'))" class="w-full sm:w-auto text-center justify-center">
                        Volver a Notas
                    </SecondaryButton>
                </template>
            </PageHeader>
        </template>

        <div class="py-6 space-y-6 max-w-2xl">
            <Card class="p-6">
                <form @submit.prevent="saveNota" class="space-y-6">
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="w-full md:w-1/2">
                            <InputLabel for="fecha" value="Fecha" />
                            <TextInput
                                id="fecha"
                                type="date"
                                class="mt-1 block w-full"
                                v-model="form.fecha"
                            />
                            <InputError class="mt-2" :message="form.errors.fecha" />
                        </div>
                        <div class="w-full md:w-1/2">
                            <InputLabel for="hora" value="Hora" />
                            <TextInput
                                id="hora"
                                type="time"
                                class="mt-1 block w-full"
                                v-model="form.hora"
                            />
                            <InputError class="mt-2" :message="form.errors.hora" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="comentario" value="Comentario" />
                        <TextArea 
                            id="comentario"
                            v-model="form.comentario"
                            class="mt-1 block w-full"
                            rows="6"
                        />
                        <InputError class="mt-2" :message="form.errors.comentario" />
                    </div>

                    <div>
                        <InputLabel for="notificacion_minutos_antes" value="Avisar con antelación" />
                        <select 
                            v-model="form.notificacion_minutos_antes" 
                            id="notificacion_minutos_antes"
                            class="mt-1 block w-full border-gray-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        >
                            <option v-for="option in formOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.notificacion_minutos_antes" />
                    </div>

                    <div class="pt-4 flex items-center justify-between">
                        <DangerButton type="button" @click="deleteNota" :disabled="form.processing">
                            Eliminar Nota
                        </DangerButton>
                        <PrimaryButton :disabled="form.processing">
                            Guardar Cambios
                        </PrimaryButton>
                    </div>
                </form>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
