<script setup>
import { ref } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router, Link } from '@inertiajs/vue3'
import Card from '@/Components/Card.vue'
import PageHeader from '@/Components/PageHeader.vue'
import Pagination from '@/Components/Pagination.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import debounce from 'lodash/debounce'
import { PlusIcon, EyeIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  presupuestos: Object,
  filters: Object,
})

const search = ref(props.filters.search || '')

const updateResults = debounce(() => {
  router.get(route('admin.presupuestos.index'), { search: search.value }, {
    preserveState: true,
    replace: true,
    preserveScroll: true,
  })
}, 300)

const formatDate = (timestamp) => {
    if(!timestamp) return '';
    const date = new Date(timestamp * 1000);
    if (isNaN(date.getTime()) || String(timestamp).indexOf('-') > -1) {
        return new Date(timestamp).toLocaleDateString('es-ES');
    }
    return date.toLocaleDateString('es-ES');
}

const formatCurrency = (val) => new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(val || 0)

const deletePresupuesto = (id) => {
    if(confirm('¿Estás seguro de eliminar este presupuesto? Esta acción también lo eliminará de tu base de datos (no de Google Drive).')) {
        router.delete(route('admin.presupuestos.destroy', id))
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
        
        <!-- Filters -->
        <div class="mb-6 flex flex-col sm:flex-row gap-4 items-center justify-between">
            <TextInput 
                v-model="search" 
                @input="updateResults" 
                type="text" 
                placeholder="Buscar por cliente o número..." 
                class="w-full sm:w-1/3"
            />
        </div>
        
        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                <thead class="bg-gray-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Número</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cliente</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Neto</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-zinc-800">
                    <tr v-for="item in presupuestos.data" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-zinc-800/50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                            {{ item.number }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ formatDate(item.date) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ item.contact_name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white text-right">
                            {{ formatCurrency(item.total) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center space-x-3">
                            <Link :href="route('admin.presupuestos.show', item.id)" class="text-emerald-600 hover:text-emerald-900 dark:text-emerald-500 dark:hover:text-emerald-400" title="Ver Propuesta / PDF">
                                <EyeIcon class="w-5 h-5 inline" />
                            </Link>
                            <Link :href="route('admin.presupuestos.edit', item.id)" class="text-blue-600 hover:text-blue-900 dark:text-blue-500 dark:hover:text-blue-400" title="Editar">
                                <PencilIcon class="w-5 h-5 inline" />
                            </Link>
                            <button @click="deletePresupuesto(item.id)" class="text-red-600 hover:text-red-900 dark:text-red-500 dark:hover:text-red-400" title="Eliminar">
                                <TrashIcon class="w-5 h-5 inline" />
                            </button>
                        </td>
                    </tr>
                    <tr v-if="presupuestos.data.length === 0">
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            No se encontraron presupuestos.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination :links="presupuestos.links" class="mt-6" v-if="presupuestos.links?.length > 3" />

      </Card>
    </div>
  </AuthenticatedLayout>
</template>
