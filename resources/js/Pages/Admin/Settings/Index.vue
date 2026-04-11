<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import Card from '@/Components/Card.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import CurrencyInput from '@/Components/CurrencyInput.vue'
import InputError from '@/Components/InputError.vue'
import PageHeader from '@/Components/PageHeader.vue'
import PushToggleButton from '@/Components/PushToggleButton.vue'

const props = defineProps({
    config: Object,
})

const form = useForm({
    precio_hora: props.config.precio_hora,
    descuento_mantenimiento: props.config.descuento_mantenimiento,
    porcentaje_software: props.config.porcentaje_software,
    empresa_nombre: props.config.empresa_nombre || '',
    empresa_nif: props.config.empresa_nif || '',
    empresa_direccion: props.config.empresa_direccion || '',
    empresa_email: props.config.empresa_email || '',
    empresa_telefono: props.config.empresa_telefono || '',
    empresa_banco_nombre: props.config.empresa_banco_nombre || '',
    empresa_banco_iban: props.config.empresa_banco_iban || '',
    default_iva: props.config.default_iva ?? 21,
    default_irpf: props.config.default_irpf ?? 0,
    default_vencimiento_dias: props.config.default_vencimiento_dias ?? 30,
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

        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-[1400px] mx-auto">
            <form @submit.prevent="submit" class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">
                <div class="w-full">
                    <Card class="p-4 sm:p-6 w-full">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Datos de Empresa / Facturación</h3>
                        
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="empresa_nombre" value="Nombre o Razón Social" />
                                    <TextInput id="empresa_nombre" v-model="form.empresa_nombre" type="text" class="mt-1 block w-full" />
                                    <InputError class="mt-2" :message="form.errors.empresa_nombre" />
                                </div>
                                <div>
                                    <InputLabel for="empresa_nif" value="NIF/CIF" />
                                    <TextInput id="empresa_nif" v-model="form.empresa_nif" type="text" class="mt-1 block w-full" />
                                    <InputError class="mt-2" :message="form.errors.empresa_nif" />
                                </div>
                            </div>

                            <div>
                                <InputLabel for="empresa_direccion" value="Dirección Completa" />
                                <TextInput id="empresa_direccion" v-model="form.empresa_direccion" type="text" class="mt-1 block w-full" placeholder="Ej: Pz. Pedro Santos Gómez 19 1A, Sevilla (41010)" />
                                <InputError class="mt-2" :message="form.errors.empresa_direccion" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="empresa_email" value="Email de Contacto" />
                                    <TextInput id="empresa_email" v-model="form.empresa_email" type="email" class="mt-1 block w-full" />
                                    <InputError class="mt-2" :message="form.errors.empresa_email" />
                                </div>
                                <div>
                                    <InputLabel for="empresa_telefono" value="Teléfono de Contacto" />
                                    <TextInput id="empresa_telefono" v-model="form.empresa_telefono" type="text" class="mt-1 block w-full" />
                                    <InputError class="mt-2" :message="form.errors.empresa_telefono" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="empresa_banco_nombre" value="Nombre del Banco" />
                                    <TextInput id="empresa_banco_nombre" v-model="form.empresa_banco_nombre" type="text" class="mt-1 block w-full" placeholder="Ej: CAIXABANK" />
                                    <InputError class="mt-2" :message="form.errors.empresa_banco_nombre" />
                                </div>
                                <div>
                                    <InputLabel for="empresa_banco_iban" value="IBAN (Cuenta Bancaria)" />
                                    <TextInput id="empresa_banco_iban" v-model="form.empresa_banco_iban" type="text" class="mt-1 block w-full" placeholder="ESXX XXXX XXXX XXXX XXXX" />
                                    <InputError class="mt-2" :message="form.errors.empresa_banco_iban" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <InputLabel for="default_iva" value="IVA por Defecto (%)" />
                                    <TextInput id="default_iva" v-model="form.default_iva" type="number" min="0" max="100" class="mt-1 block w-full" required />
                                    <InputError class="mt-2" :message="form.errors.default_iva" />
                                </div>
                                <div>
                                    <InputLabel for="default_irpf" value="IRPF por Defecto (%)" />
                                    <TextInput id="default_irpf" v-model="form.default_irpf" type="number" min="0" max="100" class="mt-1 block w-full" required />
                                    <InputError class="mt-2" :message="form.errors.default_irpf" />
                                </div>
                                <div>
                                    <InputLabel for="default_vencimiento_dias" value="Vencimiento por Defecto (Días)" />
                                    <TextInput id="default_vencimiento_dias" v-model="form.default_vencimiento_dias" type="number" min="0" class="mt-1 block w-full" required />
                                    <InputError class="mt-2" :message="form.errors.default_vencimiento_dias" />
                                </div>
                            </div>

                            <div class="flex items-center gap-4 mt-6 pt-6 border-t border-gray-200 dark:border-zinc-700">
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
                        </div>
                    </Card>
                </div>
                
                <div class="space-y-6 w-full">
                    <Card class="p-4 sm:p-6 w-full">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Ajustes Generales</h3>
                        
                        <div class="space-y-6">
                            <div>
                                <InputLabel for="precio_hora" value="Precio Hora Global (€)" />
                                <p class="text-xs text-gray-500 dark:text-zinc-400 mb-2">Este valor se usará por defecto al crear nuevos servicios.</p>
                                <CurrencyInput
                                    id="precio_hora"
                                    v-model="form.precio_hora"
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

                            <div>
                                <InputLabel for="porcentaje_software" value="Gasto Software / Hosting (%)" />
                                <p class="text-xs text-gray-500 dark:text-zinc-400 mb-2">Porcentaje del total anual de software/hosting aplicado como gasto global a proyectos y mantenimientos.</p>
                                <TextInput
                                    id="porcentaje_software"
                                    v-model="form.porcentaje_software"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.1"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.porcentaje_software" />
                            </div>
                        </div>
                    </Card>
                    <Card class="p-4 sm:p-6 w-full">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Notificaciones Push</h3>
                    <p class="text-sm text-gray-600 dark:text-zinc-400 mb-4">
                        Habilita o deshabilita las notificaciones push en este navegador para recibir alertas sobre las notas y recordatorios.
                    </p>
                    <PushToggleButton />
                </Card>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
