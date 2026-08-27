<template>
  <MainLayout title="Profile" eyebrow="Account">
    <div class="grid gap-6 xl:grid-cols-[0.85fr_1.15fr]">
      <section class="card p-5">
        <div class="flex items-start gap-4">
          <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-lg font-bold text-white shadow-lg shadow-blue-500/25">
            {{ userInitials }}
          </div>
          <div class="min-w-0">
            <h2 class="section-title truncate">{{ auth.user?.full_name || '-' }}</h2>
            <p class="muted mt-1 capitalize">{{ auth.user?.role || '-' }}</p>
          </div>
        </div>

        <dl class="mt-6 space-y-4">
          <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
            <dt class="text-xs font-bold uppercase text-slate-400">Name</dt>
            <dd class="mt-1 text-sm font-semibold text-slate-800">{{ auth.user?.full_name || '-' }}</dd>
          </div>
          <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
            <dt class="text-xs font-bold uppercase text-slate-400">Email</dt>
            <dd class="mt-1 break-all text-sm font-semibold text-slate-800">{{ auth.user?.email || '-' }}</dd>
          </div>
          <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
            <dt class="text-xs font-bold uppercase text-slate-400">Location</dt>
            <dd class="mt-1 text-sm font-semibold text-slate-800">{{ auth.user?.assigned_location_name || 'All location' }}</dd>
          </div>
        </dl>
      </section>

      <section class="card p-5">
        <div class="border-b border-slate-200 pb-4">
          <h2 class="section-title">Change Password</h2>
          <p class="muted mt-1">Update your account password</p>
        </div>

        <form class="mt-5 space-y-4" autocomplete="off" @submit.prevent="submit">
          <div>
            <label class="label" for="current-password">Current Password</label>
            <input
              id="current-password"
              v-model="form.current_password"
              class="input"
              type="password"
              autocomplete="current-password"
              required
            />
          </div>
          <div>
            <label class="label" for="new-password">New Password</label>
            <input
              id="new-password"
              v-model="form.new_password"
              class="input"
              type="password"
              autocomplete="new-password"
              minlength="6"
              required
            />
          </div>
          <div>
            <label class="label" for="confirm-password">Confirm New Password</label>
            <input
              id="confirm-password"
              v-model="form.new_password_confirmation"
              class="input"
              type="password"
              autocomplete="new-password"
              minlength="6"
              required
            />
          </div>

          <p v-if="error" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700">
            {{ error }}
          </p>
          <p v-if="success" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700">
            {{ success }}
          </p>

          <button class="btn-primary" type="submit" :disabled="auth.loading">
            <KeyRound :size="17" />
            {{ auth.loading ? 'Saving...' : 'Change Password' }}
          </button>
        </form>
      </section>
    </div>
  </MainLayout>
</template>

<script setup>
import { KeyRound } from '@lucide/vue'
import { computed, onMounted, reactive, ref } from 'vue'
import MainLayout from '../layouts/mainlayout.vue'
import { extractError } from '../services/api'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const error = ref('')
const success = ref('')
const form = reactive({
  current_password: '',
  new_password: '',
  new_password_confirmation: '',
})

const userInitials = computed(() => {
  const name = auth.user?.full_name || auth.user?.username || 'User'
  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase()
})

async function submit() {
  error.value = ''
  success.value = ''

  if (form.new_password !== form.new_password_confirmation) {
    error.value = 'New password confirmation does not match'
    return
  }

  try {
    await auth.changePassword({ ...form })
    Object.assign(form, {
      current_password: '',
      new_password: '',
      new_password_confirmation: '',
    })
    success.value = 'Password changed successfully'
  } catch (err) {
    error.value = extractError(err, 'Failed to change password')
  }
}

onMounted(() => {
  auth.fetchMe().catch(() => {})
})
</script>
