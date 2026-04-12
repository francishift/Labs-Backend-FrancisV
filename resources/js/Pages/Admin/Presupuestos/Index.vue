<script setup>
import { ref, watch, computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router, Link } from '@inertiajs/vue3'
import pickBy from 'lodash/pickBy'
import Card from '@/Components/Card.vue'
import PageHeader from '@/Components/PageHeader.vue'
import Pagination from '@/Components/Pagination.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import SearchInput from '@/Components/SearchInput.vue'
import SearchableSelect from '@/Components/SearchableSelect.vue'
import InputLabel from '@/Components/InputLabel.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import debounce from 'lodash/debounce'
import { PlusIcon, EyeIcon, PencilIcon, NoSymbolIcon, CheckCircleIcon, ArrowPathIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  presupuestos: Object,
  clients: Array,
  filters: Object,
  totals: Object,
  statuses: Array,
})

const filterForm = ref({
    search: props.filters.search || '',
    client: props.filters.client || '',
    status: props.filters.status !== undefined ? props.filters.status : '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    sort: props.filters.sort || 'date',
    direction: props.filters.direction || 'desc'
})

const statusesOptions = computed(() => [
    { id: '', name: 'Cualquier estado' },
    ...(props.statuses || [])
])

const clientsOptions = computed(() => [
    { id: '', name: 'Todos los clientes' },
    ...props.clients.map(client => ({ id: client, name: client }))
])

watch(
    filterForm,
    debounce(function () {
        router.get(
            route('admin.presupuestos.index'),
            pickBy(filterForm.value),
            { preserveState: true, preserveScroll: true, replace: true }
        )
    }, 500),
    { deep: true }
)

const resetFilters = () => {
    router.get(route('admin.presupuestos.index'))
}

const formatDate = (timestamp) => {
    if(!timestamp) return '';
    const date = new Date(timestamp * 1000);
    if (isNaN(date.getTime()) || String(timestamp).indexOf('-') > -1) {
        return new Date(timestamp).toLocaleDateString('es-ES');
    }
    return date.toLocaleDateString('es-ES');
}

const formatCurrency = (val) => new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(val || 0)

const showCancelModal = ref(false)
const showReactivateModal = ref(false)
const currentBudget = ref(null)

const confirmCancel = (budget) => {
    currentBudget.value = budget
    showCancelModal.value = true
}

const executeCancel = () => {
    if (currentBudget.value) {
        router.delete(route('admin.presupuestos.destroy', currentBudget.value.id), {
            preserveScroll: true,
            onSuccess: () => { showCancelModal.value = false; currentBudget.value = null; }
        })
    }
}

const confirmReactivate = (budget) => {
    currentBudget.value = budget
    showReactivateModal.value = true
}

const executeReactivate = () => {
    if (currentBudget.value) {
        router.patch(route('admin.presupuestos.reactivate', currentBudget.value.id), {}, {
            preserveScroll: true,
            onSuccess: () => { showReactivateModal.value = false; currentBudget.value = null; }
        })
    }
}

const updateStatusInline = (id, e) => {
    router.patch(route('admin.presupuestos.update-status', id), { status: e.target.value }, {
        preserveScroll: true
    })
}

const setDateRange = (rangeType) => {
    const today = new Date();
    if (rangeType === 'thisYear') {
        filterForm.value.date_from = new Date(today.getFullYear(), 0, 2).toISOString().split('T')[0]; // Offset for timezone safely
        filterForm.value.date_to = new Date(today.getFullYear(), 11, 31).toISOString().split('T')[0];
    } else if (rangeType === 'lastYear') {
        filterForm.value.date_from = new Date(today.getFullYear() - 1, 0, 2).toISOString().split('T')[0];
        filterForm.value.date_to = new Date(today.getFullYear() - 1, 11, 31).toISOString().split('T')[0];
    } else if (rangeType === 'last12Months') {
        const lastYear = new Date();
        lastYear.setFullYear(today.getFullYear() - 1);
        filterForm.value.date_from = lastYear.toISOString().split('T')[0];
        filterForm.value.date_to = today.toISOString().split('T')[0];
    }
}
</script>

<template>
  <Head title="Presupuestos" />

  <AuthenticatedLayout>
    <template #header>
      <PageHeader title="Presupuestos">
        <template #actions>
          <Link :href="route('admin.presupuestos.create')">
            <PrimaryButton>
              <PlusIcon class="w-4 h-4 mr-2 float-left" />
              Crear Presupuesto
            </PrimaryButton>
          </Link>
        </template>
      </PageHeader>
    </template>

    <div class="py-6 space-y-6">
      <Card class="p-4 sm:p-6">
        
                <!-- Unified Filters & Totals -->
        <div class="mb-6 flex flex-col lg:flex-row gap-6 lg:items-start justify-between">
          
          <!-- Left Side: General Filters -->
          <div class="w-full lg:flex-1 flex flex-col sm:flex-row flex-wrap gap-4 items-end">
            <!-- Search -->
            <div class="space-y-1 w-full sm:min-w-[200px] sm:flex-[2]">
              <InputLabel for="search" value="Buscar (Nº o Cliente)" />
              <SearchInput
                id="search"
                v-model="filterForm.search"
                placeholder="Ej: PR-1234..."
              />
            </div>

            <!-- Client Filter -->
            <div class="space-y-1 w-full sm:min-w-[250px] sm:flex-[3]">
              <InputLabel for="filter_client" value="Cliente" />
              <SearchableSelect 
                id="filter_client"
                v-model="filterForm.client"
                :options="clientsOptions"
                placeholder="Todos los clientes"
                class="w-full z-50"
              />
            </div>

            <!-- Status Filter -->
            <div class="space-y-1 w-full sm:w-40">
              <InputLabel for="filter_status" value="Estado" />
              <select 
                id="filter_status"
                v-model="filterForm.status"
                class="w-full border-gray-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 focus:border-emerald-500 dark:focus:border-emerald-600 focus:ring-emerald-500 dark:focus:ring-emerald-600 rounded-md shadow-sm sm:text-sm h-[42px]"
              >
                <option v-for="s in statusesOptions" :key="s.id" :value="s.id">{{ s.name }}</option>
              </select>
            </div>
            
            <!-- Clear Filters Button -->
            <div class="w-full sm:w-auto flex justify-start sm:justify-end mt-2 sm:mt-0">
                <button 
                    @click="resetFilters" 
                    title="Limpiar filtros"
                    class="h-[42px] px-3 flex items-center justify-center bg-gray-100 hover:bg-gray-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-gray-500 hover:text-emerald-600 transition-colors rounded-lg border border-gray-200 dark:border-zinc-700"
                >
                    <ArrowPathIcon class="w-5 h-5" />
                </button>
            </div>
          </div>

          <!-- Right Side: Dates & Totals Column -->
          <div class="w-full lg:w-max flex flex-col gap-4">
            
            <!-- Dates -->
            <div class="flex flex-col gap-2 w-full">
              <div class="flex gap-3">
                <!-- Date From -->
                <div class="space-y-1 flex-1">
                  <InputLabel for="date_from" value="Desde" />
                  <TextInput 
                    id="date_from"
                    v-model="filterForm.date_from"
                    type="date"
                    class="w-full text-sm"
                  />
                </div>

                <!-- Date To -->
                <div class="space-y-1 flex-1">
                  <InputLabel for="date_to" value="Hasta" />
                  <TextInput 
                    id="date_to"
                    v-model="filterForm.date_to"
                    type="date"
                    class="w-full text-sm"
                  />
                </div>
              </div>
              
              <!-- Quick Date Filters -->
              <div class="flex items-center justify-between gap-2 mt-1">
                 <button @click="setDateRange('thisYear')" class="text-[11px] text-gray-500 hover:text-emerald-600 dark:text-zinc-400 dark:hover:text-emerald-500 transition-colors">Este año</button>
                 <button @click="setDateRange('lastYear')" class="text-[11px] text-gray-500 hover:text-emerald-600 dark:text-zinc-400 dark:hover:text-emerald-500 transition-colors">Año pasado</button>
                 <button @click="setDateRange('last12Months')" class="text-[11px] text-gray-500 hover:text-emerald-600 dark:text-zinc-400 dark:hover:text-emerald-500 transition-colors">Últimos 12 meses</button>
              </div>
            </div>

            <!-- Totals Card -->
            <Card class="p-3 bg-gray-50 dark:bg-zinc-800/50 border border-gray-200 dark:border-zinc-700 w-full">
              <div class="flex items-center gap-x-6 justify-between lg:justify-end">
                <div>
                  <div class="text-[10px] text-gray-500 dark:text-zinc-400 font-bold uppercase tracking-wider mb-0.5">Base</div>
                  <div class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">{{ formatCurrency(totals?.base || totals?.net_amount || totals?.subtotal || 0) }}</div>
                </div>
                <div>
                  <div class="text-[10px] text-gray-500 dark:text-zinc-400 font-bold uppercase tracking-wider mb-0.5">IVA</div>
                  <div class="text-sm font-semibold text-blue-600 dark:text-blue-400">{{ formatCurrency(totals?.iva || totals?.tax_amount || 0) }}</div>
                </div>
                <!-- IRPF sólo mostrar si > 0 -->
                <div v-if="(totals?.irpf || totals?.irpf_amount || 0) > 0">
                  <div class="text-[10px] text-gray-500 dark:text-zinc-400 font-bold uppercase tracking-wider mb-0.5">IRPF</div>
                  <div class="text-sm font-semibold text-rose-600 dark:text-rose-400">-{{ formatCurrency(totals?.irpf || totals?.irpf_amount || 0) }}</div>
                </div>
                <div class="pl-4 border-l border-gray-200 dark:border-zinc-700">
                  <div class="text-[10px] text-gray-500 dark:text-zinc-400 font-bold uppercase tracking-wider mb-0.5">Total</div>
                  <div class="text-base font-bold text-gray-900 dark:text-zinc-100">{{ formatCurrency(totals?.total || 0) }}</div>
                </div>
              </div>
            </Card>
          </div>
        </div>
        
        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                <thead class="bg-gray-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Número</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cliente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Neto</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-zinc-800">
                    <tr v-for="item in presupuestos.data" :key="item.id" 
                        :class="[
                            item.status == 2 
                                ? 'bg-red-50/60 dark:bg-red-900/10 hover:bg-red-100/60 dark:hover:bg-red-900/20' 
                                : 'hover:bg-gray-50 dark:hover:bg-zinc-800/50'
                        ]"
                    >
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                            {{ item.number }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ formatDate(item.date) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ item.contact_name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <select 
                                :value="item.status"
                                @change="updateStatusInline(item.id, $event)"
                                class="text-xs font-semibold rounded-full border border-transparent shadow-sm focus:ring-2 focus:ring-offset-1 focus:ring-emerald-500 cursor-pointer py-1 pl-3 pr-8"
                                :class="{
                                    'bg-gray-100 text-gray-800 dark:bg-zinc-800 dark:text-zinc-300': item.status == 0,
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300': item.status == 1,
                                    'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300': item.status == 2,
                                    'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300': item.status == 3
                                }"
                            >
                                <option v-for="s in statuses" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-right"
                            :class="[item.status == 2 ? 'text-gray-500 dark:text-gray-500 line-through' : 'text-gray-900 dark:text-white']">
                            {{ formatCurrency(item.total) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center space-x-3">
                            <Link :href="route('admin.presupuestos.show', item.id)" class="text-emerald-600 hover:text-emerald-900 dark:text-emerald-500 dark:hover:text-emerald-400" title="Ver Propuesta / PDF">
                                <EyeIcon class="w-5 h-5 inline" />
                            </Link>
                            <Link v-if="item.status != 2" :href="route('admin.presupuestos.edit', item.id)" class="text-blue-600 hover:text-blue-900 dark:text-blue-500 dark:hover:text-blue-400" title="Editar Presupuesto">
                                <PencilIcon class="w-5 h-5 inline" />
                            </Link>
                            <button v-if="item.status != 2" @click="confirmCancel(item)" class="text-red-600 hover:text-red-900 dark:text-red-500 dark:hover:text-red-400" title="Anular Presupuesto">
                                <NoSymbolIcon class="w-5 h-5 inline" />
                            </button>
                            <button v-if="item.status == 2" @click="confirmReactivate(item)" class="text-teal-500 hover:text-teal-700 dark:text-teal-400 dark:hover:text-teal-300" title="Aprobar / Reactivar Presupuesto">
                                <CheckCircleIcon class="w-5 h-5 inline" />
                            </button>
                        </td>
                    </tr>
                    <tr v-if="presupuestos.data.length === 0">
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            No se encontraron presupuestos.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination :links="presupuestos.links" class="mt-6" v-if="presupuestos.links?.length > 3" />

      </Card>
    </div>

    <!-- Modales de Confirmación -->
    <ConfirmModal
      :show="showCancelModal"
      title="Anular Presupuesto"
      :content="`¿Estás seguro de anular el presupuesto '${currentBudget?.number}'? El registro se mantendrá, pero será marcado como anulado.`"
      confirm-text="Sí, anular"
      cancel-text="Cancelar"
      @close="showCancelModal = false"
      @confirm="executeCancel"
    />

    <ConfirmModal
      :show="showReactivateModal"
      title="Reactivar Presupuesto"
      :content="`¿Estás seguro de reactivar el presupuesto '${currentBudget?.number}'? Volverá al estado Activo normal.`"
      confirm-text="Sí, reactivar"
      cancel-text="Cancelar"
      @close="showReactivateModal = false"
      @confirm="executeReactivate"
    />
  </AuthenticatedLayout>
</template>
