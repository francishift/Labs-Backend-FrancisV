<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router, Link } from '@inertiajs/vue3'
import pickBy from 'lodash/pickBy'
import Card from '@/Components/Card.vue'
import PageHeader from '@/Components/PageHeader.vue'
import Pagination from '@/Components/Pagination.vue'
import DataTable from '@/Components/DataTable.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import SearchInput from '@/Components/SearchInput.vue'
import SearchableSelect from '@/Components/SearchableSelect.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import InputLabel from '@/Components/InputLabel.vue'
import debounce from 'lodash/debounce'
import { PlusIcon, EyeIcon, PencilIcon, PencilSquareIcon, NoSymbolIcon, CheckCircleIcon, ArrowPathIcon, DocumentDuplicateIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  facturas: Object,
  clients: Array,
  filters: Object,
  totals: Object,
  statuses: Array,
})


const columns = [
    { key: 'number', label: 'Número', sortable: true },
    { key: 'date', label: 'Fecha', sortable: true },
    { key: 'contact_name', label: 'Cliente', sortable: true },
    { key: 'status', label: 'Estado', sortable: true },
    { key: 'total', label: 'Total Neto', sortable: true, align: 'right' },
    { key: 'acciones', label: 'Acciones', sortable: false, center: true },
]

const sort = (key) => {
    if (filterForm.value.sort === key) {
        filterForm.value.direction = filterForm.value.direction === 'asc' ? 'desc' : 'asc'
    } else {
        filterForm.value.sort = key
        filterForm.value.direction = 'asc'
    }
}

const getRowClass = (item) => {
    return item.status == 2 
        ? 'bg-red-50/60 dark:bg-red-900/10 hover:bg-red-100/60 dark:hover:bg-red-900/20' 
        : ''
}

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
            route('admin.facturas.index'),
            pickBy(filterForm.value, (value) => value !== '' && value !== null && value !== undefined),
            { preserveState: true, preserveScroll: true, replace: true }
        )
    }, 500),
    { deep: true }
)

const resetFilters = () => {
    router.get(route('admin.facturas.index'))
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
const showDuplicateModal = ref(false)
const currentBudget = ref(null)

const confirmDuplicate = (factura) => {
    currentBudget.value = factura
    showDuplicateModal.value = true
}

const executeDuplicate = () => {
    if (currentBudget.value) {
        router.post(route('admin.facturas.duplicate', currentBudget.value.id), {}, {
            preserveScroll: true,
            onSuccess: () => { showDuplicateModal.value = false; currentBudget.value = null; }
        })
    }
}

const confirmCancel = (budget) => {
    currentBudget.value = budget
    showCancelModal.value = true
}

const executeCancel = () => {
    if (currentBudget.value) {
        router.delete(route('admin.facturas.destroy', currentBudget.value.id), {
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
        router.patch(route('admin.facturas.reactivate', currentBudget.value.id), {}, {
            preserveScroll: true,
            onSuccess: () => { showReactivateModal.value = false; currentBudget.value = null; }
        })
    }
}

const updateStatusInline = (id, e) => {
    router.patch(route('admin.facturas.update-status', id), { status: e.target.value }, {
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

onMounted(() => {
    // Forzar recarga silenciosa de datos por si venimos de 'volver' (caché)
    router.reload({ only: ['facturas', 'totals'] })
})
</script>

<template>
  <Head title="Facturas" />

  <AuthenticatedLayout>
    <template #header>
      <PageHeader title="Facturas">
        <template #actions>
          <Link :href="route('admin.facturas.create')">
            <PrimaryButton>
              <PlusIcon class="w-4 h-4 mr-2 float-left" />
              Crear Factura
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
        <DataTable
            :columns="columns"
            :items="facturas.data"
            :sort-key="filterForm.sort"
            :sort-dir="filterForm.direction"
            @sort="sort"
            :hoverable="true"
            :row-class="getRowClass"
        >
            <template #cell-date="{ item }">
                {{ formatDate(item.date) }}
            </template>
            
            <template #cell-status="{ item }">
                <select 
                    :value="item.status"
                    @change="updateStatusInline(item.id, $event)"
                    @click.stop
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
            </template>
            
            <template #cell-total="{ item }">
                <span :class="[item.status == 2 ? 'text-gray-500 dark:text-gray-500 line-through font-normal' : 'font-bold text-gray-900 dark:text-white']">
                    {{ formatCurrency(item.total) }}
                </span>
            </template>
            
            <template #cell-acciones="{ item }">
                <div class="space-x-4 w-full flex justify-center items-center">
                  <Link :href="route('admin.facturas.show', item.id)" class="text-emerald-500/70 hover:text-emerald-600 dark:text-emerald-400/70 dark:hover:text-emerald-400 transition-colors" title="Ver Propuesta / PDF" @click.stop>
                      <EyeIcon class="w-5 h-5 inline" />
                  </Link>
                  <Link v-if="item.status != 2" :href="route('admin.facturas.edit', item.id)" class="text-blue-500/70 hover:text-blue-600 dark:text-blue-400/70 dark:hover:text-blue-400 transition-colors" title="Editar Factura" @click.stop>
                      <PencilSquareIcon class="w-5 h-5 inline" />
                  </Link>
                  <button v-if="item.status != 2" @click.stop="confirmDuplicate(item)" class="text-orange-500/70 hover:text-orange-600 dark:text-orange-400/70 dark:hover:text-orange-400 transition-colors" title="Duplicar Factura">
                      <DocumentDuplicateIcon class="w-5 h-5 inline" />
                  </button>
                  <button v-if="item.status != 2" @click.stop="confirmCancel(item)" class="text-red-500/70 hover:text-red-600 dark:text-red-400/70 dark:hover:text-red-400 transition-colors" title="Anular Factura">
                      <NoSymbolIcon class="w-5 h-5 inline" />
                  </button>
                  <button v-if="item.status == 2" @click.stop="confirmReactivate(item)" class="text-teal-500/70 hover:text-teal-600 dark:text-teal-400/70 dark:hover:text-teal-400 transition-colors" title="Aprobar / Reactivar Factura">
                      <CheckCircleIcon class="w-5 h-5 inline" />
                  </button>
                </div>
            </template>
        </DataTable>

        <Pagination :links="facturas.links" class="mt-6" v-if="facturas.links?.length > 3" />

      </Card>
    </div>

    <!-- Modals -->
    <ConfirmModal 
        v-model="showDuplicateModal" 
        title="Duplicar Factura" 
        message="¿Estás seguro de que deseas duplicar esta factura? Se creará una copia en estado Pendiente y se te redigirá a su vista de edición para que puedas ajustar cualquier dato antes de enviarla."
        confirm-text="Sí, duplicar"
        cancel-text="Cancelar"
        type="warning"
        @confirm="executeDuplicate"
    />
    <ConfirmModal
      :show="showCancelModal"
      title="Anular Factura"
      :content="`¿Estás seguro de anular el factura '${currentBudget?.number}'? El registro se mantendrá, pero será marcado como anulado.`"
      confirm-text="Sí, anular"
      cancel-text="Cancelar"
      @close="showCancelModal = false"
      @confirm="executeCancel"
    />

    <ConfirmModal
      :show="showReactivateModal"
      title="Reactivar Factura"
      :content="`¿Estás seguro de reactivar el factura '${currentBudget?.number}'? Volverá al estado Activo normal.`"
      confirm-text="Sí, reactivar"
      cancel-text="Cancelar"
      @close="showReactivateModal = false"
      @confirm="executeReactivate"
    />
  </AuthenticatedLayout>
</template>
