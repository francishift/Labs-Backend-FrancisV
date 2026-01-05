import { ref } from 'vue'

export function useCRUDModals() {
    const showCreateModal = ref(false)
    const showEditModal = ref(false)
    const showConfirmModal = ref(false)
    const editingItem = ref(null)
    const itemToDelete = ref(null)

    const openCreateModal = () => {
        showCreateModal.value = true
    }

    const closeCreateModal = () => {
        showCreateModal.value = false
    }

    const openEditModal = (item) => {
        editingItem.value = item
        showEditModal.value = true
    }

    const closeEditModal = () => {
        showEditModal.value = false
        editingItem.value = null
    }

    const confirmDelete = (item) => {
        itemToDelete.value = item
        showConfirmModal.value = true
    }

    const closeConfirmModal = () => {
        showConfirmModal.value = false
        itemToDelete.value = null
    }

    return {
        showCreateModal,
        showEditModal,
        showConfirmModal,
        editingItem,
        itemToDelete,
        openCreateModal,
        closeCreateModal,
        openEditModal,
        closeEditModal,
        confirmDelete,
        closeConfirmModal,
    }
}
