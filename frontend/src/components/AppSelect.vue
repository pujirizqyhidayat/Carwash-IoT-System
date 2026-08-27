<template>
  <div ref="root" class="relative">
    <button
      class="input flex items-center justify-between gap-3 text-left"
      type="button"
      :aria-expanded="open"
      @click.stop="open = !open"
      @keydown.escape="open = false"
    >
      <span class="truncate" :class="selectedLabel ? 'text-slate-950' : 'text-slate-400'">{{ selectedLabel || placeholder }}</span>
      <ChevronDown :size="16" class="shrink-0 text-slate-500 transition" :class="open ? 'rotate-180' : ''" />
    </button>

    <div
      v-if="open"
      class="mt-2 max-h-64 overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-xl shadow-blue-950/10 ring-1 ring-blue-500/10"
      @click.stop
    >
      <button
        v-for="option in options"
        :key="String(option.value)"
        class="flex h-10 w-full items-center justify-between gap-3 px-3 text-left text-sm text-slate-700 transition hover:bg-blue-50 hover:text-blue-700"
        :class="option.value === modelValue ? 'bg-blue-600 text-white hover:bg-blue-600 hover:text-white' : ''"
        type="button"
        @click="selectOption(option.value)"
      >
        <span class="truncate">{{ option.label }}</span>
        <Check v-if="option.value === modelValue" :size="15" class="shrink-0" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { Check, ChevronDown } from '@lucide/vue'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  options: { type: Array, required: true },
  placeholder: { type: String, default: 'Select option' },
})
const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const root = ref(null)
const selectedLabel = computed(() => props.options.find((option) => option.value === props.modelValue)?.label || '')

function selectOption(value) {
  emit('update:modelValue', value)
  open.value = false
}

function handleDocumentClick(event) {
  if (!root.value?.contains(event.target)) {
    open.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleDocumentClick)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleDocumentClick)
})
</script>
