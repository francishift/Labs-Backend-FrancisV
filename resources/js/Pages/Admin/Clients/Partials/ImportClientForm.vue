<script setup>
import { useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    closeImportModal: Function,
});

const importForm = useForm({
    file: null,
});

const handleFileUpload = (e) => {
    importForm.file = e.target.files[0];
};

const submitImport = () => {
    importForm.post(route('admin.clientes.import'), {
        onSuccess: () => {
            importForm.reset();
            props.closeImportModal();
        },
    });
};
</script>

<template>
    <form id="import-client-form" @submit.prevent="submitImport" class="mt-4 space-y-6">
        <div>
            <InputLabel for="excel_file" value="Seleccionar archivo" />
            <input
                id="excel_file"
                type="file"
                accept=".xlsx,.xls,.csv"
                @change="handleFileUpload"
                class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-zinc-400 focus:outline-none dark:bg-zinc-900 dark:border-zinc-700 dark:placeholder-zinc-400"
                required
            />
            <InputError class="mt-2" :message="importForm.errors.file" />
        </div>
    </form>
</template>
