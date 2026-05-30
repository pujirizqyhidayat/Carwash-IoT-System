<template>
  <main class="min-h-screen bg-white">
    <div class="grid min-h-screen lg:grid-cols-[1.05fr_0.95fr]">
      <section class="app-shell flex items-center justify-center px-6 py-10">
        <div class="card-elevated w-full max-w-md p-7">
          <div class="mb-8 flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-600 text-white shadow-lg shadow-blue-200">
              <Car :size="24" />
            </div>
            <div>
              <p class="text-lg font-bold text-slate-950">Carwash IoT</p>
              <p class="text-sm text-slate-500">Secure Operations Dashboard</p>
            </div>
          </div>

          <div class="mb-7">
            <h1 class="text-2xl font-bold text-slate-950">Login</h1>
            <p class="mt-2 text-sm text-slate-500">Secure dashboard access</p>
          </div>

          <form class="space-y-4" @submit.prevent="submit">
            <div>
              <label class="label" for="email">Email</label>
              <input id="email" v-model="form.email" class="input" type="email" autocomplete="email" required />
            </div>
            <div>
              <label class="label" for="password">Password</label>
              <input
                id="password"
                v-model="form.password"
                class="input"
                type="password"
                autocomplete="current-password"
                required
              />
            </div>

            <p v-if="error" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700">
              {{ error }}
            </p>

            <button class="btn-primary w-full justify-center" type="submit" :disabled="auth.loading">
              <LogIn :size="18" />
              {{ auth.loading ? 'Signing in...' : 'Sign in' }}
            </button>
          </form>

        </div>
      </section>

      <section class="mini-grid hidden bg-blue-700 p-8 text-white lg:block">
        <div class="flex h-full flex-col justify-between rounded-lg border border-white/20 bg-white/10 p-8 shadow-2xl shadow-blue-950/20">
          <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-100">Rizki Car Wash</p>
            <h2 class="mt-5 max-w-xl text-4xl font-bold leading-tight">Operations Control</h2>
          </div>
          <div class="rounded-lg border border-white/20 bg-white p-5 text-blue-950 shadow-xl">
            <div class="mb-5 flex items-center justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-500">Dashboard</p>
                <p class="mt-1 text-lg font-bold">Vehicle Flow</p>
              </div>
              <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Active</span>
            </div>
            <div class="grid grid-cols-3 gap-3">
              <div class="rounded-lg bg-blue-50 p-4">
                <p class="text-xs text-blue-500">Today</p>
                <p class="mt-2 text-2xl font-bold">Live</p>
              </div>
              <div class="rounded-lg bg-slate-50 p-4">
                <p class="text-xs text-slate-500">Sensor</p>
                <p class="mt-2 text-2xl font-bold">Online</p>
              </div>
              <div class="rounded-lg bg-amber-50 p-4">
                <p class="text-xs text-amber-600">Export</p>
                <p class="mt-2 text-2xl font-bold">PDF</p>
              </div>
            </div>
            <div class="mt-5 flex h-28 items-end gap-2">
              <div class="h-[35%] flex-1 rounded-t bg-blue-200"></div>
              <div class="h-[55%] flex-1 rounded-t bg-blue-300"></div>
              <div class="h-[72%] flex-1 rounded-t bg-blue-500"></div>
              <div class="h-[45%] flex-1 rounded-t bg-cyan-300"></div>
              <div class="h-[88%] flex-1 rounded-t bg-blue-700"></div>
              <div class="h-[64%] flex-1 rounded-t bg-emerald-400"></div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </main>
</template>

<script setup>
import { Car, LogIn } from '@lucide/vue'
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { extractError } from '../services/api'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()
const error = ref('')
const form = reactive({
  email: '',
  password: '',
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
</script>
