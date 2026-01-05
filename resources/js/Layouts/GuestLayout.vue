<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
import { Link } from '@inertiajs/vue3'
import Footer from '@/Components/Footer.vue'
</script>

<template>
  <div class="min-h-[100svh] md:min-h-screen flex flex-col bg-gray-100 dark:bg-zinc-900 transition-colors duration-300">

    <div class="flex-1 flex flex-col items-center px-4 pt-6 sm:px-0 sm:justify-center sm:pt-0">
      <div>
        <Link href="/">
          <ApplicationLogo class="h-28 w-28 fill-current text-gray-800 dark:text-zinc-200" />
        </Link>
      </div>

      <div class="mt-6 w-full overflow-hidden bg-white dark:bg-zinc-800 px-6 py-4 shadow-md rounded-lg sm:max-w-md sm:rounded-xl transition-colors duration-300">
        <slot />
      </div>
    </div>

    <Footer />
  </div>
</template>
