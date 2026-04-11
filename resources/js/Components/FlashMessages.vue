<script setup>
import { watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useFlash } from '@/Composables/useFlash'

const page = usePage()
const { messages, success, error, remove } = useFlash()

watch(
  () => page.props.flash,
  (f) => {
    if (f?.success) success(f.success)
    if (f?.error) error(f.error)
  },
  { deep: true, immediate: true }
)
</script>

<template>
  <div class="fixed top-20 left-1/2 -translate-x-1/2 z-[100] flex flex-col gap-4 w-full max-w-sm pointer-events-none">
    <TransitionGroup
      enter-active-class="transform ease-out duration-300 transition"
      enter-from-class="translate-y-2 opacity-0 sm:translate-y-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition ease-in duration-100"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-for="msg in messages"
        :key="msg.id"
        class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg bg-white dark:bg-zinc-800 shadow-2xl ring-1 ring-black ring-opacity-10"
      >
        <div class="p-4">
          <div class="flex items-start">
            <div class="flex-shrink-0">
              <!-- Success Icon -->
              <svg v-if="msg.type === 'success'" class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <!-- Error Icon -->
              <svg v-else-if="msg.type === 'error'" class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
              </svg>
              <!-- Info/Default Icon -->
              <svg v-else class="h-6 w-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
              </svg>
            </div>
            <div class="ml-3 w-0 flex-1 pt-0.5">
              <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                {{ msg.type === 'success' ? 'Éxito' : (msg.type === 'error' ? 'Error' : 'Info') }}
              </p>
              <div class="mt-1 text-sm text-gray-500 dark:text-gray-400" v-html="msg.message"></div>
            </div>
            <div class="ml-4 flex flex-shrink-0">
              <button 
                type="button" 
                @click="remove(msg.id)"
                class="inline-flex rounded-md bg-white dark:bg-zinc-800 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2"
                :class="{
                    'focus:ring-green-500': msg.type === 'success',
                    'focus:ring-red-500': msg.type === 'error',
                    'focus:ring-blue-500': msg.type === 'info'
                }"
              >
                <span class="sr-only">Close</span>
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </TransitionGroup>
  </div>
</template>
