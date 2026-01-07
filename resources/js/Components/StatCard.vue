<script setup>
import Card from '@/Components/Card.vue'
import { useFormatters } from '@/Composables/useFormatters'

const props = defineProps({
  title: String,
  value: [Number, String],
  secondaryValue: [Number, String],
  secondaryIsCurrency: {
    type: Boolean,
    default: true
  },
  isCurrency: {
    type: Boolean,
    default: true
  },
  icon: Object,
  variant: {
    type: String,
    default: 'emerald'
  }
})

const { formatCurrency } = useFormatters()

const variantClasses = {
  emerald: 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400',
  indigo: 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400',
  zinc: 'bg-zinc-50 dark:bg-zinc-900/20 text-zinc-600 dark:text-zinc-400',
  amber: 'bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400',
  rose: 'bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400',
}
</script>

<template>
  <Card class="p-4 relative overflow-hidden group">
    <div class="flex items-center">
      <div :class="['p-3 rounded-xl', variantClasses[variant] || variantClasses.emerald]">
        <component :is="icon" class="h-8 w-8" />
      </div>
      <div class="ml-5">
        <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">{{ title }}</p>
        <div class="flex items-baseline gap-2">
          <p class="text-2xl font-black text-gray-900 dark:text-white">
            {{ isCurrency ? formatCurrency(value) : value }}
          </p>
          <p v-if="secondaryValue !== undefined" class="text-xs font-bold text-gray-500 dark:text-zinc-400">
            {{ secondaryIsCurrency ? formatCurrency(secondaryValue) : secondaryValue }}
          </p>
        </div>
      </div>
    </div>
    <div class="absolute -right-4 -bottom-4 h-24 w-24 opacity-5 transform rotate-12 transition-transform group-hover:scale-110">
      <component :is="icon" />
    </div>
  </Card>
</template>
