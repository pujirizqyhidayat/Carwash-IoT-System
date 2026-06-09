<template>
  <main class="min-h-screen bg-[#071129] text-blue-50">
    <div class="grid min-h-screen lg:grid-cols-[1.05fr_0.95fr]">
      <section class="app-shell relative flex items-center justify-center overflow-hidden px-6 py-10">
        <div class="pointer-events-none absolute left-10 top-10 hidden h-24 w-24 border-l border-t border-cyan-300/25 lg:block"></div>
        <div class="pointer-events-none absolute bottom-10 right-10 hidden h-24 w-24 border-b border-r border-blue-300/25 lg:block"></div>
        <div class="robot-panel w-full max-w-md p-7">
          <div class="mb-8 flex items-center gap-3">
            <div class="grid h-12 w-12 place-items-center border border-cyan-300/25 bg-cyan-400/10 text-cyan-100 shadow-lg shadow-cyan-500/20">
              <Cpu :size="24" />
            </div>
            <div>
              <p class="text-lg font-bold text-white">Carwash IoT</p>
              <p class="text-sm text-blue-100/55">Secure Operations Dashboard</p>
            </div>
          </div>

          <div class="mb-7">
            <h1 class="text-2xl font-bold text-white">Login</h1>
            <p class="mt-2 text-sm text-blue-100/55">Secure dashboard access</p>
          </div>

          <form class="space-y-4" @submit.prevent="submit">
            <div>
              <label class="label" for="email">Email</label>
              <input id="email" v-model="form.email" class="robot-input" type="email" autocomplete="email" required />
            </div>
            <div>
              <label class="label" for="password">Password</label>
              <input
                id="password"
                v-model="form.password"
                class="robot-input"
                type="password"
                autocomplete="current-password"
                required
              />
            </div>

            <p v-if="error" class="rounded-lg border border-red-300/25 bg-red-500/15 px-3 py-2 text-sm font-medium text-red-100">
              {{ error }}
            </p>

            <button class="robot-button w-full" type="submit" :disabled="auth.loading">
              <LogIn :size="18" />
              {{ auth.loading ? 'Signing in...' : 'Sign in' }}
            </button>
          </form>

        </div>
      </section>

      <section class="command-surface hidden p-8 text-white lg:block">
        <div class="robot-panel flex h-full flex-col justify-between p-8">
          <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-100/70">Rizki Car Wash</p>
            <h2 class="mt-5 max-w-xl text-5xl font-black leading-tight">Operations Control</h2>
          </div>
          <div class="rounded-lg border border-white/25 bg-white/18 p-5 text-blue-50 shadow-xl shadow-blue-950/30 backdrop-blur">
            <div class="mb-5 flex items-center justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-200/70">Dashboard</p>
                <p class="mt-1 text-lg font-bold">Vehicle Flow</p>
              </div>
              <span class="bg-emerald-500/15 px-3 py-1 text-xs font-bold text-emerald-100 ring-1 ring-emerald-300/20">Active</span>
            </div>
            <div class="grid grid-cols-3 gap-3">
              <div class="robot-tile">
                <p class="text-xs text-blue-100/55">Today</p>
                <p class="mt-2 text-2xl font-black">Live</p>
              </div>
              <div class="robot-tile">
                <p class="text-xs text-blue-100/55">Sensor</p>
                <p class="mt-2 text-2xl font-black">Online</p>
              </div>
              <div class="robot-tile">
                <p class="text-xs text-amber-100/70">Export</p>
                <p class="mt-2 text-2xl font-black">PDF</p>
              </div>
            </div>
            <div class="mt-5 flex h-28 items-end gap-2">
              <div class="h-[35%] flex-1 bg-blue-200"></div>
              <div class="h-[55%] flex-1 bg-blue-300"></div>
              <div class="h-[72%] flex-1 bg-blue-500"></div>
              <div class="h-[45%] flex-1 bg-cyan-300"></div>
              <div class="h-[88%] flex-1 bg-blue-700"></div>
              <div class="h-[64%] flex-1 bg-emerald-400"></div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </main>
</template>

<script setup>
import { Cpu, LogIn } from '@lucide/vue'
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
