<script setup>
import { ref, computed, watch } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import Card from '@/Components/Card.vue'
import PageHeader from '@/Components/PageHeader.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import InputLabel from '@/Components/InputLabel.vue'
import InputError from '@/Components/InputError.vue'
import SearchableSelect from '@/Components/SearchableSelect.vue'
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'
import { formatDateForInput } from '@/Utils/date'

const props = defineProps({
  presupuesto: Object,
  clientes: Array,
  statuses: Array,
  defaultIva: Number,
  defaultIrpf: Number,
})



const form = useForm({
  client_id: props.presupuesto.client_id || '',
  contact_name: props.presupuesto.contact_name || '',
  date: formatDateForInput(props.presupuesto.date),
  due_date: formatDateForInput(props.presupuesto.due_date),
  status: props.presupuesto.status !== undefined ? props.presupuesto.status : 0,
  notes: props.presupuesto.notes || '',
  description: props.presupuesto.description || '',
  lineas: props.presupuesto.lineas && props.presupuesto.lineas.length > 0 
    ? props.presupuesto.lineas.map(l => ({
        concepto: l.concepto,
        descripcion: l.descripcion || '',
        cantidad: Number(l.cantidad),
        precio_unitario: Number(l.precio_unitario),
        porcentaje_iva: Number(l.porcentaje_iva),
        porcentaje_irpf: Number(l.porcentaje_irpf)
    }))
    : [{ concepto: '', descripcion: '', cantidad: 1, precio_unitario: 0, porcentaje_iva: props.defaultIva !== undefined ? props.defaultIva : 21, porcentaje_irpf: props.defaultIrpf !== undefined ? props.defaultIrpf : 0 }]
})

const showHtml = ref(false)

const clientesOpciones = computed(() => {
    return props.clientes.map(c => ({
        ...c,
        display_name: `${c.name} (${c.cif_nif || 'Sin NIF'})`
    }))
})

watch(() => form.client_id, (newClientId) => {
    const client = props.clientes.find(c => String(c.id) === String(newClientId))
    if (client) {
        form.contact_name = client.name
    } else {
        form.contact_name = ''
    }
})

const addLinea = () => {
    form.lineas.push({
        concepto: '',
        descripcion: '',
        cantidad: 1,
        precio_unitario: 0,
        porcentaje_iva: props.defaultIva !== undefined ? props.defaultIva : 21,
        porcentaje_irpf: props.defaultIrpf !== undefined ? props.defaultIrpf : 0
    })
}

const removeLinea = (index) => {
    if (form.lineas.length > 1) {
        form.lineas.splice(index, 1)
    }
}

const subtotal = computed(() => {
    return form.lineas.reduce((acc, linea) => acc + (linea.cantidad * linea.precio_unitario), 0)
})

const taxAmount = computed(() => {
    return form.lineas.reduce((acc, linea) => acc + ((linea.cantidad * linea.precio_unitario) * (linea.porcentaje_iva / 100)), 0)
})

const irpfAmount = computed(() => {
    return form.lineas.reduce((acc, linea) => acc + ((linea.cantidad * linea.precio_unitario) * (linea.porcentaje_irpf / 100)), 0)
})

const total = computed(() => {
    return subtotal.value + taxAmount.value - irpfAmount.value
})

const formatCurrency = (val) => new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(val || 0)

const submit = () => {
    form.patch(route('admin.presupuestos.update', props.presupuesto.id), { replace: true })
}

const goBack = () => {
    window.history.back()
}
</script>

<template>
  <Head :title="'Editar Presupuesto '+presupuesto.number" />

  <AuthenticatedLayout>
    <template #header>
      <PageHeader :title="'Editar Presupuesto: ' + (presupuesto.number || '')">
        <template #actions>
          <SecondaryButton @click="goBack">Cancelar</SecondaryButton>
        </template>
      </PageHeader>
    </template>

    <div class="py-6 space-y-6">
      <Card class="p-4 sm:p-6 max-w-5xl mx-auto">
        <form @submit.prevent="submit" class="space-y-8">
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 pb-6 border-b border-gray-200 dark:border-zinc-700">
                <div>
                    <InputLabel for="client" value="Cliente" />
                    <SearchableSelect
                        id="client"
                        v-model="form.client_id"
                        :options="clientesOpciones"
                        label-key="display_name"
                        placeholder="Buscar y seleccionar cliente..."
                        class="mt-1 block w-full"
                    />
                    <InputError class="mt-2" :message="form.errors.client_id" />
                </div>
                <div>
                    <InputLabel for="date" value="Fecha del Presupuesto" />
                    <TextInput 
                        id="date" 
                        type="date" 
                        class="mt-1 block w-full" 
                        v-model="form.date" 
                        required 
                    />
                    <InputError class="mt-2" :message="form.errors.date" />
                </div>
                <div>
                    <InputLabel for="due_date" value="Fecha de Vencimiento" />
                    <TextInput 
                        id="due_date" 
                        type="date" 
                        class="mt-1 block w-full" 
                        v-model="form.due_date" 
                    />
                    <InputError class="mt-2" :message="form.errors.due_date" />
                </div>
                <div>
                    <InputLabel for="status" value="Estado" />
                    <select 
                        id="status" 
                        v-model="form.status" 
                        class="mt-1 block w-full border-gray-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 focus:border-emerald-500 dark:focus:border-emerald-600 focus:ring-emerald-500 dark:focus:ring-emerald-600 rounded-md shadow-sm h-[42px]"
                    >
                        <option v-for="s in statuses" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.status" />
                </div>
            </div>

            <!-- Repetidor de Líneas -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Líneas de Presupuesto</h3>
                
                <div class="space-y-4">
                    <div v-for="(linea, index) in form.lineas" :key="index" class="p-4 bg-gray-50 dark:bg-zinc-800/50 rounded-lg border border-gray-200 dark:border-zinc-700 flex flex-col gap-2 relative">
                        <div class="flex-grow grid grid-cols-1 md:grid-cols-12 gap-4 w-full">
                            <div class="md:col-span-5">
                                <InputLabel value="Concepto" v-if="index === 0" />
                                <TextInput v-model="linea.concepto" type="text" class="w-full mt-1" required />
                                <InputError class="mt-2" :message="form.errors[`lineas.${index}.concepto`]" />
                            </div>
                            <div class="md:col-span-2">
                                <InputLabel value="Cant." v-if="index === 0" />
                                <TextInput v-model="linea.cantidad" type="number" step="0.01" min="0.01" class="w-full mt-1 text-right" required />
                            </div>
                            <div class="md:col-span-2">
                                <InputLabel value="Precio Ud. (€)" v-if="index === 0" />
                                <TextInput v-model="linea.precio_unitario" type="number" step="0.01" class="w-full mt-1 text-right" required />
                            </div>
                            <div class="md:col-span-1">
                                <InputLabel value="IVA %" v-if="index === 0" />
                                <TextInput v-model="linea.porcentaje_iva" type="number" min="0" max="100" class="w-full mt-1 text-center" />
                            </div>
                            <div class="md:col-span-2 flex flex-col relative">
                                <InputLabel value="IRPF %" v-if="index === 0" />
                                <div class="flex items-center gap-2 mt-1">
                                    <TextInput v-model="linea.porcentaje_irpf" type="number" min="0" max="100" class="w-full text-center" />
                                    <!-- Delete Button -->
                                    <button v-if="form.lineas.length > 1" type="button" @click="removeLinea(index)" class="text-red-500 hover:text-red-700 bg-white dark:bg-zinc-900 border border-gray-300 dark:border-zinc-700 rounded-md p-2">
                                        <TrashIcon class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Segunda fila: Descripción de la línea -->
                        <div class="w-full mt-3">
                            <textarea v-model="linea.descripcion" placeholder="Descripción extendida del concepto (opcional)..." rows="2" class="w-full text-sm border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 dark:text-zinc-300 focus:border-indigo-500 rounded-md shadow-sm"></textarea>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <SecondaryButton type="button" @click="addLinea" class="text-sm">
                        <PlusIcon class="w-4 h-4 mr-1" />
                        Añadir Concepto
                    </SecondaryButton>
                </div>
            </div>

            <!-- Observaciones -->
            <div>
                <div class="flex justify-between items-center mb-1">
                    <InputLabel value="Observaciones (Opcional)" />
                    <button type="button" @click="showHtml = !showHtml" class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-200 hover:underline">
                        {{ showHtml ? 'Ver Editor Visual' : 'Editar Código HTML' }}
                    </button>
                </div>
                <!-- Modo Visual -->
                <div v-show="!showHtml" class="bg-white dark:bg-zinc-900 border border-gray-300 dark:border-zinc-700 rounded-md shadow-sm">
                    <QuillEditor theme="snow" v-model:content="form.description" contentType="html" toolbar="minimal" />
                </div>
                <!-- Modo HTML -->
                <div v-show="showHtml">
                    <textarea 
                        v-model="form.description" 
                        rows="8" 
                        class="block w-full border-gray-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm font-mono text-sm"
                        placeholder="<p>Escribe HTML aquí...</p>"
                    ></textarea>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Notas -->
                <div>
                    <InputLabel for="notes" value="Notas / Condiciones (Opcional)" />
                    <textarea 
                        id="notes" 
                        v-model="form.notes"
                        class="mt-1 block w-full border-gray-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm h-32"
                    ></textarea>
                </div>

                <!-- Totales resúmen -->
                <div class="bg-emerald-50 dark:bg-emerald-900/10 rounded-lg p-6 border border-emerald-100 dark:border-emerald-800/20">
                    <h3 class="text-emerald-800 dark:text-emerald-500 font-bold mb-4 uppercase tracking-wider text-sm">Resumen Financiero</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>Subtotal</span>
                            <span class="font-medium">{{ formatCurrency(subtotal) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600 dark:text-gray-400" v-if="taxAmount > 0">
                            <span>Impuestos (IVA)</span>
                            <span class="font-medium">{{ formatCurrency(taxAmount) }}</span>
                        </div>
                        <div class="flex justify-between text-red-500" v-if="irpfAmount > 0">
                            <span>Retenciones (IRPF)</span>
                            <span class="font-medium">-{{ formatCurrency(irpfAmount) }}</span>
                        </div>
                        <div class="pt-3 border-t border-emerald-200 dark:border-emerald-800/30 flex justify-between items-center">
                            <span class="text-lg font-bold text-gray-900 dark:text-white">Total a Pagar</span>
                            <span class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ formatCurrency(total) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-6 border-t border-gray-200 dark:border-zinc-700">
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    {{ form.processing ? 'Guardando...' : 'Actualizar y Guardar PDF' }}
                </PrimaryButton>
            </div>

        </form>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
