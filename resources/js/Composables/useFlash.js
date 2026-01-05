import { ref } from 'vue';

const messages = ref([]);

export function useFlash() {
    const remove = (id) => {
        messages.value = messages.value.filter((m) => m.id !== id);
    };

    const add = (type, message) => {
        const id = Date.now() + Math.random();
        messages.value.push({ id, type, message });

        setTimeout(() => {
            remove(id);
        }, 5000);
    };

    const success = (message) => add('success', message);
    const error = (message) => add('error', message);
    const warning = (message) => add('warning', message);
    const info = (message) => add('info', message);

    return {
        messages,
        add,
        remove,
        success,
        error,
        warning,
        info,
    };
}
