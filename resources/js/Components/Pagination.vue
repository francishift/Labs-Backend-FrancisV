<script setup>
import { toRef } from 'vue'
import { Link } from '@inertiajs/vue3'
import { usePaginationWindow } from '@/Composables/usePaginationWindow'

const props = defineProps({
  links: {
    type: Array,
    required: true,
  },
  prefetch: {
    type: Boolean,
    default: false,
  },
})

const paginationLinks = usePaginationWindow(toRef(props, 'links'))

const scrollToContent = () => {
    const target = document.querySelector('body') || document.body;
    const offset = 30; // Offset para evitar que el header fijo tape el contenido
    const bodyRect = document.body.getBoundingClientRect().top;
    const elementRect = target.getBoundingClientRect().top;
    const elementPosition = elementRect - bodyRect;
    const offsetPosition = elementPosition - offset;

    window.scrollTo({
        top: offsetPosition,
        behavior: 'smooth'
    });
}
</script>

<template>
  <div v-if="links.length > 3" class="mt-6 flex flex-wrap gap-2">
    <template v-for="(link, i) in paginationLinks" :key="i">
      <div
        v-if="link.url === null"
        class="mr-1 mb-1 px-4 py-2 text-sm leading-4 text-gray-400 dark:text-zinc-600 border dark:border-zinc-700 rounded-md"
        v-html="link.label"
      />
      <Link
        v-else
        class="mr-1 mb-1 px-4 py-2 text-sm leading-4 border rounded-md transition-colors duration-200"
        :class="
          link.active
            ? 'bg-zinc-800 dark:bg-zinc-200 border-zinc-800 dark:border-zinc-100 text-white dark:text-zinc-900'
            : 'border-gray-200 dark:border-zinc-700 text-gray-700 dark:text-zinc-400 hover:bg-gray-50 dark:hover:bg-zinc-700'
        "
        :href="link.url"
        v-html="link.label"
        preserve-scroll
        :prefetch="prefetch"
        @click="scrollToContent"
      />
    </template>
  </div>
</template>
