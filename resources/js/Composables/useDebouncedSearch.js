import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';

export function useDebouncedSearch(initialValue = '', routeName, delay = 300) {
    const search = ref(initialValue);

    const debouncedSearch = debounce((value) => {
        router.get(
            route(routeName),
            { search: value },
            {
                preserveState: true,
                replace: true,
                preserveScroll: true,
            }
        );
    }, delay);

    watch(search, (value) => {
        debouncedSearch(value);
    });

    return {
        search,
    };
}
