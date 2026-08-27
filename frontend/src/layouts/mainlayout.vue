<template>
  <div class="app-shell workspace-shell min-h-screen text-blue-50">
    <aside class="sidebar-panel fixed bottom-4 left-4 top-4 z-30 hidden w-72 overflow-hidden rounded-lg border border-white/10 text-white lg:flex lg:flex-col">
      <div class="px-5 pb-5 pt-6">
        <div class="flex items-center gap-3 rounded-lg border border-white/10 bg-white/8 px-4 py-3 backdrop-blur">
          <div class="grid h-11 w-11 place-items-center rounded-md border border-cyan-300/20 bg-cyan-400/10 text-cyan-100 shadow-lg shadow-cyan-500/20">
            <Car :size="22" />
          </div>
          <div>
            <p class="text-sm font-bold text-white">Charcoal</p>
            <p class="text-xs text-blue-100/70">Vehicle Analysis Monitoring Systemu</p>
          </div>
        </div>
      </div>

      <div class="mx-5 mb-2 rounded-lg border border-white/10 bg-white/6 px-4 py-3">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs text-blue-100/65">Current location</p>
            <p class="mt-1 truncate text-sm font-semibold text-white">{{ locations.activeLocation?.location_name || 'Select location' }}</p>
          </div>
          <div class="flex h-9 w-9 items-center justify-center rounded-md bg-blue-500/20 text-blue-100">
            <MapPinned :size="17" />
          </div>
        </div>
      </div>

      <nav class="scrollbar-hidden min-h-0 flex-1 space-y-1.5 overflow-y-auto px-4 py-4">
        <RouterLink
          v-for="item in visibleNav"
          :key="item.to"
          :to="item.to"
          class="nav-item"
          active-class="nav-item-active"
        >
          <component :is="item.icon" :size="18" />
          <span>{{ item.label }}</span>
        </RouterLink>
      </nav>

      <div class="shrink-0 p-4">
        <div class="mb-3 rounded-lg border border-white/10 bg-white/6 p-3 backdrop-blur">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-blue-500/25 text-sm font-bold text-white ring-1 ring-blue-300/25">
              {{ userInitials }}
            </div>
            <div class="min-w-0">
              <p class="truncate text-sm font-semibold text-white">{{ auth.user?.full_name }}</p>
              <p class="text-xs capitalize text-blue-100/70">{{ auth.user?.role }}</p>
            </div>
          </div>
        </div>
        <RouterLink
          to="/profile"
          class="mb-3 inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg border border-white/10 bg-white/8 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-white/12"
        >
          <UserCircle :size="17" />
          Profile
        </RouterLink>
        <button
          class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg border border-red-200/40 bg-red-500/15 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-red-500/25 hover:text-white"
          type="button"
          @click="logout"
        >
          <LogOut :size="17" />
          Logout
        </button>
      </div>
    </aside>

    <div class="lg:pl-80">
      <header class="sticky top-4 z-20 mx-4 mt-4 rounded-lg border border-white/10 bg-slate-950/55 shadow-2xl shadow-blue-950/50 backdrop-blur-xl sm:mx-6 lg:mx-8">
        <div class="flex min-h-24 flex-col items-start justify-center gap-4 px-5 py-4 sm:min-h-24 sm:flex-row sm:items-center sm:justify-between sm:px-6 sm:py-5">
          <div class="min-w-0">
            <p class="robot-copy text-[11px] font-semibold uppercase text-cyan-200/70">{{ eyebrow }}</p>
            <h1 class="mt-1 truncate text-xl font-semibold leading-tight text-white sm:text-2xl">{{ title }}</h1>
          </div>
          <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto sm:justify-end sm:gap-3">
            <select v-if="locations.locations.length" class="input hidden h-11 w-60 text-sm sm:block" :value="locations.activeLocationId" @change="changeLocation">
              <option v-for="location in locations.locations" :key="location.id" :value="location.id">
                {{ location.location_name }}
              </option>
            </select>
            <span class="status-pill sensor-status-pill" :class="sensorClass">
              <span class="h-2 w-2 rounded-full" :class="sensorDotClass"></span>
              {{ sensorLabel }}
            </span>
            <button class="icon-btn lg:hidden" type="button" @click="mobileOpen = !mobileOpen" aria-label="Toggle menu">
              <Menu :size="20" />
            </button>
          </div>
        </div>
        <div v-if="mobileOpen" class="border-t border-white/10 bg-slate-950/95 p-3 lg:hidden">
          <div class="space-y-1.5">
            <RouterLink
              v-for="item in visibleNav"
              :key="item.to"
              :to="item.to"
              class="nav-item"
              active-class="nav-item-active"
              @click="mobileOpen = false"
            >
              <component :is="item.icon" :size="18" />
              <span>{{ item.label }}</span>
            </RouterLink>
          </div>
          <RouterLink to="/profile" class="mt-3 inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg border border-white/10 bg-white/8 px-4 text-sm font-semibold text-white transition hover:bg-white/12" @click="mobileOpen = false">
            <UserCircle :size="17" />
            Profile
          </RouterLink>
          <button class="mt-3 inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg border border-red-300/20 bg-red-500/15 px-4 text-sm font-semibold text-red-100 transition hover:bg-red-500/25 hover:text-white" type="button" @click="logout">
            <LogOut :size="17" />
            Logout
          </button>
        </div>
      </header>

      <main class="px-4 py-7 sm:px-6 lg:px-8">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import {
  Activity,
  BarChart3,
  Car,
  ClipboardList,
  LayoutDashboard,
  LogOut,
  MapPinned,
  Menu,
  Radar,
  Users,
} from '@lucide/vue'
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { api } from '../services/api'
import { useAuthStore } from '../stores/auth'
import { useLocationStore } from '../stores/location'

const props = defineProps({
  title: { type: String, required: true },
  eyebrow: { type: String, default: 'Operations' },
  sensorStatus: { type: String, default: null },
})

defineOptions({
  name: 'MainLayout',
})

const router = useRouter()
const auth = useAuthStore()
const locations = useLocationStore()
const mobileOpen = ref(false)
const headerSensorStatus = ref('disconnected')
let sensorTimer = null

const navItems = [
  { to: '/dashboard', label: 'Dashboard', icon: LayoutDashboard, roles: ['owner', 'cashier', 'admin'] },
  { to: '/monitoring', label: 'Monitoring', icon: Activity, roles: ['owner', 'cashier', 'admin'] },
  { to: '/reports', label: 'Reports', icon: BarChart3, roles: ['owner', 'admin'] },
  { to: '/sensors', label: 'Sensors', icon: Radar, roles: ['owner', 'admin'] },
  { to: '/locations', label: 'Locations', icon: MapPinned, roles: ['owner', 'admin'] },
  { to: '/users', label: 'Users', icon: Users, roles: ['admin'] },
  { to: '/audit-logs', label: 'Audit Log', icon: ClipboardList, roles: ['admin'] },
]

const visibleNav = computed(() => navItems.filter((item) => item.roles.includes(auth.role)))
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
const currentSensorStatus = computed(() => props.sensorStatus || headerSensorStatus.value || 'disconnected')
const sensorLabel = computed(() => {
  if (currentSensorStatus.value === 'disconnected') return 'Sensor Disconnected'
  if (currentSensorStatus.value === 'inactive') return 'Sensor Inactive'
  return 'Sensor Active'
})
const sensorClass = computed(() => {
  if (currentSensorStatus.value === 'disconnected') return 'border-red-200 bg-red-50 text-red-700'
  if (currentSensorStatus.value === 'inactive') return 'border-amber-200 bg-amber-50 text-amber-700'
  return 'border-emerald-200 bg-emerald-50 text-emerald-700'
})
const sensorDotClass = computed(() => {
  if (currentSensorStatus.value === 'disconnected') return 'bg-red-500'
  if (currentSensorStatus.value === 'inactive') return 'bg-amber-500'
  return 'bg-emerald-500'
})

async function logout() {
  await auth.logout()
  router.push('/login')
}

function changeLocation(event) {
  locations.setActiveLocation(event.target.value)
  window.dispatchEvent(new CustomEvent('active-location-changed'))
}

async function refreshHeaderSensorStatus() {
  if (props.sensorStatus) return

  try {
    const { data } = await api.get('/dashboard/summary', {
      params: { location_id: locations.activeLocationId },
    })
    headerSensorStatus.value = data.sensor_status || 'disconnected'
  } catch {
    headerSensorStatus.value = 'disconnected'
  }
}

function handleActiveLocationChanged() {
  refreshHeaderSensorStatus()
}

onMounted(() => {
  locations.fetchLocations().catch(() => {})
  refreshHeaderSensorStatus()
  window.addEventListener('active-location-changed', handleActiveLocationChanged)
  sensorTimer = window.setInterval(refreshHeaderSensorStatus, 15000)
})

onUnmounted(() => {
  window.removeEventListener('active-location-changed', handleActiveLocationChanged)
  if (sensorTimer) window.clearInterval(sensorTimer)
})
</script>











