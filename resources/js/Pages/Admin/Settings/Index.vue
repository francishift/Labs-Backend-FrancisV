<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import Card from '@/Components/Card.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'
import PageHeader from '@/Components/PageHeader.vue'

const props = defineProps({
    config: Object,
})

const form = useForm({
    precio_hora: props.config.precio_hora,
    descuento_mantenimiento: props.config.descuento_mantenimiento,
})

const submit = () => {
    form.patch(route('admin.settings.update'), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Configuración" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader title="Configuración del Sistema" />
        </template>

        <div class="py-6 space-y-6">
                <Card class="p-6 max-w-2xl">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Ajustes Generales</h3>
                    
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="precio_hora" value="Precio Hora Global (€)" />
                            <p class="text-xs text-gray-500 dark:text-zinc-400 mb-2">Este valor se usará por defecto al crear nuevos servicios.</p>
                            <TextInput
                                id="precio_hora"
                                v-model="form.precio_hora"
                                type="number"
                                step="0.01"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.precio_hora" />
                        </div>

                        <div>
                            <InputLabel for="descuento_mantenimiento" value="Descuento Mantenimiento (%)" />
                            <p class="text-xs text-gray-500 dark:text-zinc-400 mb-2">Porcentaje de descuento automático para los cálculos de rentabilidad en mantenimientos.</p>
                            <TextInput
                                id="descuento_mantenimiento"
                                v-model="form.descuento_mantenimiento"
                                type="number"
                                min="0"
                                max="100"
                                step="1"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.descuento_mantenimiento" />
                        </div>

                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">
                                Guardar Cambios
                            </PrimaryButton>

                            <transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p v-if="form.recentlySuccessful" class="text-sm text-gray-600 dark:text-zinc-400">Guardado correctamente.</p>
                            </transition>
                        </div>
                    </form>
                </Card>
        </div>
    </AuthenticatedLayout>
</template>
