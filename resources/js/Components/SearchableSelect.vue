<script>
export default {
    inheritAttrs: false
}
</script>

<script setup>
import { computed, ref, watch } from 'vue';
import {
  Combobox,
  ComboboxInput,
  ComboboxButton,
  ComboboxOptions,
  ComboboxOption,
} from '@headlessui/vue';
import { ChevronUpDownIcon, CheckIcon, XMarkIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    modelValue: [String, Number],
    options: {
        type: Array,
        required: true,
    },
    placeholder: {
        type: String,
        default: 'Seleccionar opción...',
    },
    labelKey: {
        type: String,
        default: 'name',
    },
    valueKey: {
        type: String,
        default: 'id',
    },
});

const emit = defineEmits(['update:modelValue']);

const query = ref('');

const selectedOption = computed({
  get() {
    if (props.modelValue === null || props.modelValue === '') return null;
    return props.options.find(option => option[props.valueKey] == props.modelValue) || null;
  },
  set(option) {
    if (option) {
      emit('update:modelValue', option[props.valueKey]);
    } else {
      emit('update:modelValue', '');
    }
  }
});

const filteredOptions = computed(() => {
    if (query.value === '') {
        return props.options;
    }
    
    return props.options.filter((option) => {
        return String(option[props.labelKey])
            .toLowerCase()
            .includes(query.value.toLowerCase());
    });
});
</script>

<template>
  <Combobox v-model="selectedOption" as="div" class="relative">
    <div class="relative">
      <ComboboxInput
        v-bind="$attrs"
        class="w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-left shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 sm:text-sm dark:bg-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:focus:border-zinc-500 dark:focus:ring-zinc-500 transition-colors"
        :displayValue="(option) => option ? option[labelKey] : ''"
        @change="query = $event.target.value"
        :placeholder="placeholder"
        autocomplete="off"
      />
        <div class="absolute inset-y-0 right-0 flex items-center pr-2">
            <button
                v-if="selectedOption"
                type="button"
                @click.stop="selectedOption = null"
                class="p-1 rounded-full text-gray-400 hover:text-gray-600 dark:text-zinc-500 dark:hover:text-zinc-300 transition-colors mr-1 outline-none focus:ring-2 focus:ring-emerald-500"
                title="Limpiar selección"
            >
                <XMarkIcon class="h-4 w-4" aria-hidden="true" />
            </button>
            <ComboboxButton class="p-1 outline-none">
                <ChevronUpDownIcon
                    class="h-5 w-5 text-gray-400 dark:text-zinc-500 hover:text-gray-600 dark:hover:text-zinc-300 transition-colors cursor-pointer"
                    aria-hidden="true"
                />
            </ComboboxButton>
        </div>
    </div>

    <transition
      leave-active-class="transition ease-in duration-100"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
      @after-leave="query = ''"
    >
      <ComboboxOptions
        class="absolute z-[100] mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-xl ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm dark:bg-zinc-800 dark:ring-zinc-700 custom-scrollbar border border-gray-100 dark:border-zinc-700"
      >
        <div
          v-if="filteredOptions.length === 0 && query !== ''"
          class="relative cursor-default select-none py-2 px-4 text-gray-700 dark:text-zinc-400"
        >
          No se encontraron resultados para "{{ query }}"
        </div>

        <ComboboxOption
          v-for="option in filteredOptions"
          :key="option[valueKey]"
          :value="option"
          v-slot="{ selected, active }"
          as="template"
        >
          <li
            class="relative cursor-pointer select-none py-2.5 pl-10 pr-4 transition-colors"
            :class="{
              'bg-emerald-50 text-emerald-900 dark:bg-emerald-900/40 dark:text-emerald-300': active,
              'text-gray-900 dark:text-zinc-300': !active,
            }"
          >
            <span
              class="block truncate"
              :class="{
                 'font-bold text-emerald-600 dark:text-emerald-400': selected,
                 'font-normal': !selected
              }"
            >
              {{ option[labelKey] }}
            </span>

            <span
              v-if="selected"
              class="absolute inset-y-0 left-0 flex items-center pl-3"
              :class="{
                  'text-emerald-600 dark:text-emerald-400': active,
                  'text-emerald-600 dark:text-emerald-400': !active
              }"
            >
              <CheckIcon class="h-5 w-5" aria-hidden="true" />
            </span>
          </li>
        </ComboboxOption>
      </ComboboxOptions>
    </transition>
  </Combobox>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #3f3f46;
}
</style>
