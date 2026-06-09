<template>
  <section class="card-elevated group relative overflow-hidden p-5">
    <div class="absolute inset-x-0 top-0 h-1 bg-blue-500"></div>
    <div class="flex items-start justify-between gap-3">
      <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-blue-100/55">{{ label }}</p>
        <p class="mt-3 text-3xl font-bold text-white">{{ value }}</p>
        <p class="mt-1 text-sm text-blue-100/50">{{ sublabel }}</p>
      </div>
      <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-500/15 text-blue-100 ring-1 ring-blue-300/20 transition group-hover:bg-blue-500/25 group-hover:text-white">
        <component :is="icon" :size="21" />
      </div>
    </div>
  </section>
</template>

<script setup>
defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], required: true },
  sublabel: { type: String, required: true },
  icon: { type: [Object, Function], required: true },
})
</script>
