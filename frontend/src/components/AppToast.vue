<template>
  <div v-if="message" class="fixed bottom-5 right-5 z-50 max-w-sm rounded-lg border px-4 py-3 text-sm font-semibold shadow-lg" :class="classes">
    {{ message }}
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  message: { type: String, default: '' },
  type: { type: String, default: 'success' },
})

const classes = computed(() => {
  if (props.type === 'error') return 'border-red-200 bg-red-50 text-red-700'
  return 'border-emerald-200 bg-emerald-50 text-emerald-700'
})
</script>
