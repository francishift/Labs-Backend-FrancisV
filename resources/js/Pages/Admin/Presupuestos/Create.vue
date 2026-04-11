<script setup>
import { ref, computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import Card from '@/Components/Card.vue'
import PageHeader from '@/Components/PageHeader.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import InputLabel from '@/Components/InputLabel.vue'
import InputError from '@/Components/InputError.vue'
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  clientes: Array,
  defaultIva: Number,
  defaultIrpf: Number,
  defaultVencimientoDias: Number,
})
const getDefaultDueDate = () => {
    const d = new Date()
    const days = props.defaultVencimientoDias !== undefined ? props.defaultVencimientoDias : 30
    d.setDate(d.getDate() + days)
    return d.toISOString().split('T')[0]
}

const form = useForm({
  client_id: '',
  contact_name: '',
  date: new Date().toISOString().split('T')[0],
  due_date: getDefaultDueDate(),
  notes: '',
  lineas: [
      {
          concepto: '',
          cantidad: 1,
          precio_unitario: 0,
          porcentaje_iva: props.defaultIva !== undefined ? props.defaultIva : 21,
          porcentaje_irpf: props.defaultIrpf !== undefined ? props.defaultIrpf : 0
      }
  ]
})

const selectedClient = ref(null)

const onClientChange = (event) => {
    const clientId = event.target.value
    const client = props.clientes.find(c => String(c.id) === String(clientId))
    if (client) {
        form.contact_name = client.name
    } else {
        form.contact_name = ''
    }
}

const addLinea = () => {
    form.lineas.push({
        concepto: '',
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
    form.post(route('admin.presupuestos.store'), {
        preserveScroll: true,
    })
}
</script>

<template>
  <Head title="Crear Presupuesto" />

  <AuthenticatedLayout>
    <template #header>
      <PageHeader title="Crear Nuevo Presupuesto">
        <template #actions>
          <Link :href="route('admin.presupuestos.index')">
            <SecondaryButton>Cancelar</SecondaryButton>
          </Link>
        </template>
      </PageHeader>
    </template>

    <div class="py-6 space-y-6">
      <Card class="p-4 sm:p-6 max-w-5xl mx-auto">
        <form @submit.prevent="submit" class="space-y-8">
            
            <!-- Datos Iniciales -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pb-6 border-b border-gray-200 dark:border-zinc-700">
                <div>
                    <InputLabel for="client" value="Cliente" />
                    <select 
                        id="client" 
                        v-model="form.client_id" 
                        @change="onClientChange"
                        class="mt-1 block w-full border-gray-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                        required
                    >
                        <option value="" disabled>Selecciona un cliente</option>
                        <option v-for="c in clientes" :key="c.id" :value="c.id">{{ c.name }} ({{ c.cif_nif || 'Sin NIF' }})</option>
                    </select>
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
            </div>
            <!-- Repetidor de Líneas -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Líneas de Presupuesto</h3>
                
                <div class="space-y-4">
                    <div v-for="(linea, index) in form.lineas" :key="index" class="p-4 bg-gray-50 dark:bg-zinc-800/50 rounded-lg border border-gray-200 dark:border-zinc-700 flex flex-col md:flex-row gap-4 items-start md:items-center relative">
                        <div class="flex-grow grid grid-cols-1 md:grid-cols-12 gap-4 w-full">
                            <div class="md:col-span-5">
                                <InputLabel value="Concepto" v-if="index === 0" />
                                <TextInput v-model="linea.concepto" type="text" class="w-full mt-1" required placeholder="Ej. Diseño web..." />
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
                                    <!-- Delete Button inside row for Desktop, positioned tight -->
                                    <button v-if="form.lineas.length > 1" type="button" @click="removeLinea(index)" class="text-red-500 hover:text-red-700 bg-white dark:bg-zinc-900 border border-gray-300 dark:border-zinc-700 rounded-md p-2">
                                        <TrashIcon class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
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

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Notas -->
                <div>
                    <InputLabel for="notes" value="Notas / Condiciones (Opcional)" />
                    <textarea 
                        id="notes" 
                        v-model="form.notes"
                        class="mt-1 block w-full border-gray-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm h-32"
                        placeholder="Las condiciones de pago son..."
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
                    {{ form.processing ? 'Guardando...' : 'Crear y Guardar PDF' }}
                </PrimaryButton>
            </div>

        </form>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
