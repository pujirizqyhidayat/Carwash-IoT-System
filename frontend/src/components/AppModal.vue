<template>
  <TransitionRoot appear :show="open" as="template">
    <Dialog as="div" class="relative z-50" @close="$emit('close')">
      <TransitionChild
        as="template"
        enter="duration-150 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-100 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-slate-950/35" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
          <TransitionChild
            as="template"
            enter="duration-150 ease-out"
            enter-from="opacity-0 translate-y-2 scale-95"
            enter-to="opacity-100 translate-y-0 scale-100"
            leave="duration-100 ease-in"
            leave-from="opacity-100 translate-y-0 scale-100"
            leave-to="opacity-0 translate-y-2 scale-95"
          >
            <DialogPanel class="w-full max-w-2xl rounded-lg bg-white shadow-xl">
              <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                <div>
                  <DialogTitle class="text-base font-semibold text-slate-950">{{ title }}</DialogTitle>
                  <p v-if="description" class="mt-1 text-sm text-slate-500">{{ description }}</p>
                </div>
                <button class="icon-btn" type="button" aria-label="Close modal" @click="$emit('close')">
                  <X :size="18" />
                </button>
              </div>
              <div class="px-5 py-5">
                <slot />
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { X } from '@lucide/vue'

defineProps({
  open: { type: Boolean, required: true },
  title: { type: String, required: true },
  description: { type: String, default: '' },
})

defineEmits(['close'])
</script>
