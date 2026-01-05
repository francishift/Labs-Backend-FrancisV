<script setup>
import { computed } from 'vue'
const year = computed(() => new Date().getFullYear())
</script>

<template>
  <footer class="bg-gray-100 dark:bg-zinc-800 py-4">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center text-sm text-gray-600 dark:text-zinc-400">
      <span>&copy; {{ year }} Francis Valenzuela</span>
      <div class="flex space-x-4 mt-2 md:mt-0">
        <a href="https://www.TU_DOMINIO" target="_blank" class="hover:underline">Web</a>
        <a href="https://www.TU_DOMINIO/politica-de-privacidad" target="_blank" class="hover:underline">Política de Privacidad</a>
        <a href="https://www.TU_DOMINIO/politica-de-cookies" target="_blank" class="hover:underline">Cookies</a>
      </div>
    </div>
  </footer>
</template>

<style scoped>
</style>
