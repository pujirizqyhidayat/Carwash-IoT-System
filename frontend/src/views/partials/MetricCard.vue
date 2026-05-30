<template>
  <section class="card-elevated group relative overflow-hidden p-5">
    <div class="absolute inset-x-0 top-0 h-1 bg-blue-600"></div>
    <div class="flex items-start justify-between gap-3">
      <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ label }}</p>
        <p class="mt-3 text-3xl font-bold text-slate-950">{{ value }}</p>
        <p class="mt-1 text-sm text-slate-500">{{ sublabel }}</p>
      </div>
      <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-50 text-blue-700 ring-1 ring-blue-100 transition group-hover:bg-blue-600 group-hover:text-white">
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
