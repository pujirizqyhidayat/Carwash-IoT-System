<template>
  <main class="min-h-screen overflow-hidden bg-[#f8fafc] text-slate-950">
    <section class="relative flex min-h-screen items-center justify-center px-4 py-8 sm:px-6 lg:px-10">
      <div class="absolute inset-0">
        <img src="/logincar.avif" alt="" class="h-full w-full object-cover opacity-35" />
        <div class="absolute inset-0 bg-[#e0f2fe]/82"></div>
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_18%,rgba(59,130,246,0.28),transparent_34%),radial-gradient(circle_at_82%_72%,rgba(148,163,184,0.22),transparent_28%)]"></div>
      </div>

      <div class="relative grid w-full max-w-6xl overflow-hidden rounded-lg border border-slate-300/50 bg-white/90 shadow-2xl shadow-blue-500/15 backdrop-blur-xl lg:min-h-170 lg:grid-cols-[1.04fr_0.96fr]">
        <aside class="relative hidden overflow-hidden border-r border-slate-300/40 p-8 lg:block">
          <img src="/logincar.avif" alt="Car wash login visual" class="absolute inset-0 h-full w-full object-cover" />
          <div class="absolute inset-0 bg-linear-to-b from-[#3b82f6]/8 via-[#3b82f6]/28 to-[#0f172a]/84"></div>
          <div class="absolute inset-x-0 bottom-0 h-1/2 bg-linear-to-t from-[#0f172a] to-transparent"></div>

          <div class="relative flex h-full flex-col justify-between">
            <div class="flex items-center gap-4">
              <p class="robot-copy text-xs font-semibold uppercase text-blue-50/80">RMODA</p>
              <div class="h-px flex-1 bg-sky-100/70"></div>
            </div>

            <div class="max-w-md">
              <h1 class="text-5xl font-black leading-none text-white drop-shadow-2xl">Clean Flow, Smart Control</h1>
              <p class="mt-5 max-w-sm text-sm leading-6 text-blue-50/78">
                Secure Dashboard, Secure Operations
              </p>
            </div>
          </div>
        </aside>

        <div class="flex items-center justify-center bg-[#f8fafc] px-6 py-10 text-slate-950 sm:px-10 lg:px-14">
          <div class="w-full max-w-md">
            <div class="mb-16 flex items-center justify-center gap-3 text-slate-950 lg:mb-24">
              <img src="/car-logo.svg" alt="Charcoal logo" class="h-14 w-28 object-contain" />
              <div>
                <p class="text-base font-bold">Charcoal</p>
                <p class="text-xs font-medium text-slate-500">Operations Dashboard</p>
              </div>
            </div>

            <div class="mb-8 text-center">
              <h2 class="text-4xl font-black tracking-normal text-slate-950">
                {{ isForgotPassword ? 'Reset Password' : 'Welcome Back' }}
              </h2>
              <p class="mt-3 text-sm text-slate-500">
                {{ isForgotPassword ? 'Enter your email and new password' : 'Enter your email and password to access your account' }}
              </p>
            </div>

            <form v-if="!isForgotPassword" class="space-y-5" autocomplete="off" @submit.prevent="submit">
              <div>
                <label class="mb-2 block text-sm font-semibold text-slate-800" for="email">Email</label>
                <input
                  id="email"
                  v-model="form.email"
                  class="h-12 w-full rounded-lg border border-slate-300 bg-white px-4 text-sm text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/15"
                  type="email"
                  autocomplete="off"
                  placeholder="Enter your email"
                  required
                />
              </div>
              <div>
                <label class="mb-2 block text-sm font-semibold text-slate-800" for="password">Password</label>
                <input
                  id="password"
                  v-model="form.password"
                  class="h-12 w-full rounded-lg border border-slate-300 bg-white px-4 text-sm text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/15"
                  type="password"
                  autocomplete="new-password"
                  placeholder="Enter your password"
                  required
                />
              </div>

              <div class="flex justify-end">
                <button class="text-sm font-semibold text-blue-600 transition hover:text-blue-700" type="button" @click="showForgotPassword">
                  Forgot password?
                </button>
              </div>

              <p v-if="error" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700">
                {{ error }}
              </p>

              <button class="robot-button mt-2 w-full" type="submit" :disabled="auth.loading">
                <LogIn :size="18" />
                {{ auth.loading ? 'Signing in...' : 'Sign in' }}
              </button>
            </form>

            <form v-else class="space-y-5" autocomplete="off" @submit.prevent="resetPassword">
              <div>
                <label class="mb-2 block text-sm font-semibold text-slate-800" for="reset-email">Email</label>
                <input
                  id="reset-email"
                  v-model="resetForm.email"
                  class="h-12 w-full rounded-lg border border-slate-300 bg-white px-4 text-sm text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/15"
                  type="email"
                  autocomplete="off"
                  placeholder="Enter your email"
                  required
                />
              </div>
              <div>
                <label class="mb-2 block text-sm font-semibold text-slate-800" for="new-password">New Password</label>
                <input
                  id="new-password"
                  v-model="resetForm.newPassword"
                  class="h-12 w-full rounded-lg border border-slate-300 bg-white px-4 text-sm text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/15"
                  type="password"
                  autocomplete="new-password"
                  placeholder="Enter new password"
                  required
                  minlength="6"
                />
              </div>

              <p v-if="error" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700">
                {{ error }}
              </p>
              <p v-if="success" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700">
                {{ success }}
              </p>

              <button class="robot-button mt-2 w-full" type="submit" :disabled="auth.loading">
                <KeyRound :size="18" />
                {{ auth.loading ? 'Changing password...' : 'Change password' }}
              </button>
              <button
                class="flex w-full items-center justify-center gap-2 text-sm font-semibold text-slate-600 transition hover:text-slate-950"
                type="button"
                @click="showLogin"
              >
                <ArrowLeft :size="16" />
                Back to sign in
              </button>
            </form>
          </div>
        </div>
      </div>
    </section>
  </main>
</template>

<script setup>
import { ArrowLeft, KeyRound, LogIn } from '@lucide/vue'
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { extractError } from '../services/api'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()
const error = ref('')
const success = ref('')
const isForgotPassword = ref(false)
const form = reactive({
  email: '',
  password: '',
})
const resetForm = reactive({
  email: '',
  newPassword: '',
})

async function submit() {
  error.value = ''
  try {
    await auth.login(form)
    router.push('/dashboard')
  } catch (err) {
    error.value = extractError(err, 'Invalid credentials')
  }
}

function showForgotPassword() {
  error.value = ''
  success.value = ''
  resetForm.email = form.email
  resetForm.newPassword = ''
  isForgotPassword.value = true
}

function showLogin() {
  error.value = ''
  success.value = ''
  form.email = resetForm.email
  isForgotPassword.value = false
}

async function resetPassword() {
  error.value = ''
  success.value = ''
  try {
    await auth.forgotPassword({
      email: resetForm.email,
      new_password: resetForm.newPassword,
    })
    success.value = 'Password changed successfully. You can sign in now.'
    form.email = resetForm.email
    form.password = ''
    resetForm.newPassword = ''
  } catch (err) {
    error.value = extractError(err, 'Failed to change password')
  }
}
</script>