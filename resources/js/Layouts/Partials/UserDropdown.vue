<script setup>
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
import { UserIcon, ArrowLeftOnRectangleIcon } from '@heroicons/vue/24/outline'

defineProps({
    user: Object
})
</script>

<template>
    <div class="relative ms-3">
        <Dropdown align="right" width="48">
            <template #trigger>
                <span class="inline-flex rounded-md">
                    <button
                        type="button"
                        class="inline-flex items-center rounded-md border border-transparent bg-white dark:bg-zinc-800 px-2 py-2 text-sm font-medium leading-4 text-gray-500 dark:text-zinc-400 transition duration-150 ease-in-out hover:text-gray-700 dark:hover:text-zinc-300 focus:outline-none"
                    >
                        <UserIcon class="h-5 w-5" />

                        <svg
                            class="-me-0.5 ms-1 h-4 w-4"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 0 1 1.414 0L10 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414l-4 4a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 0-1.414z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </button>
                </span>
            </template>

            <template #content>
                <div class="block px-4 py-2 text-xs text-gray-400 border-b border-gray-100 dark:border-zinc-700">
                    {{ user.name }}
                </div>
                <DropdownLink :href="route('profile.edit')" class="flex items-center mt-1">
                    <UserIcon class="me-2 h-4 w-4" />
                    Perfil
                </DropdownLink>
                <DropdownLink
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="flex items-center"
                >
                    <ArrowLeftOnRectangleIcon class="me-2 h-4 w-4" />
                    Cerrar sesión
                </DropdownLink>
            </template>
        </Dropdown>
    </div>
</template>
