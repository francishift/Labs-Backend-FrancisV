import { reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';

/**
 * Composable para manejar múltiples filtros en tablas con Inertia.js
 * @param {Object} initialFilters - Objeto con los filtros iniciales (ej: { search: '', categoria_id: 1 })
 * @param {String} routeName - Nombre de la ruta de Laravel a la que enviar la petición (ej: 'admin.users.index')
 * @param {Number} delay - Tiempo de espera para el debounce en ms (por defecto 300ms)
 * @returns {Object} - Objeto reactivo 'filters' listo para usar con v-model
 */
export function useFilters(initialFilters = {}, routeName, delay = 300) {
    const filters = reactive({ ...initialFilters });

    const debouncedSearch = debounce((newFilters) => {
        router.get(
            route(routeName),
            newFilters,
            {
                preserveState: true,
                replace: true,
                preserveScroll: true,
            }
        );
    }, delay);

    watch(filters, (newFilters) => {
        // Clonamos el objeto para evitar referencias mutables en Inertia
        debouncedSearch({ ...newFilters });
    }, { deep: true });

    return {
        filters,
    };
}
