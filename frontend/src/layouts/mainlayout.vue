<template>
  <div class="app-shell min-h-screen text-slate-950">
    <aside class="sidebar-panel fixed bottom-4 left-4 top-4 z-30 hidden w-72 overflow-hidden rounded-[2rem] border border-white/15 text-white lg:flex lg:flex-col">
      <div class="px-5 pb-5 pt-6">
        <div class="flex items-center gap-3 rounded-[1.6rem] border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
          <div class="flex h-11 w-11 items-center justify-center rounded-full bg-white text-blue-700 shadow-sm">
            <Car :size="22" />
          </div>
          <div>
            <p class="text-sm font-bold text-white">Carwash IoT</p>
            <p class="text-xs text-blue-100/70">Vehicle Counter</p>
          </div>
        </div>
      </div>

      <div class="mx-5 mb-2 rounded-[1.4rem] border border-white/10 bg-blue-950/10 px-4 py-3">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs text-blue-100/65">Current location</p>
            <p class="mt-1 truncate text-sm font-semibold text-white">{{ locations.activeLocation?.location_name || 'Select location' }}</p>
          </div>
          <div class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-blue-100">
            <MapPinned :size="17" />
          </div>
        </div>
      </div>

      <nav class="flex-1 space-y-1.5 px-4 py-4">
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

      <div class="p-4">
        <div class="mb-3 rounded-[1.4rem] border border-white/10 bg-white/10 p-3 backdrop-blur">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white text-sm font-bold text-blue-700">
              {{ userInitials }}
            </div>
            <div class="min-w-0">
              <p class="truncate text-sm font-semibold text-white">{{ auth.user?.full_name }}</p>
              <p class="text-xs capitalize text-blue-100/70">{{ auth.user?.role }}</p>
            </div>
          </div>
        </div>
        <button
          class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-full border border-red-200/40 bg-red-500/15 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-white hover:text-red-600"
          type="button"
          @click="logout"
        >
          <LogOut :size="17" />
          Logout
        </button>
      </div>
    </aside>

    <div class="lg:pl-80">
      <header class="sticky top-4 z-20 mx-4 mt-4 rounded-[1.75rem] border border-white/70 bg-white/90 shadow-xl shadow-slate-200/60 backdrop-blur sm:mx-6 lg:mx-8">
        <div class="flex h-16 items-center justify-between gap-4 px-5 sm:px-6">
          <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-blue-600">{{ eyebrow }}</p>
            <h1 class="truncate text-lg font-semibold text-slate-950">{{ title }}</h1>
          </div>
          <div class="flex items-center gap-3">
            <select v-if="locations.locations.length" class="input hidden w-56 sm:block" :value="locations.activeLocationId" @change="changeLocation">
              <option v-for="location in locations.locations" :key="location.id" :value="location.id">
                {{ location.location_name }}
              </option>
            </select>
            <span class="status-pill" :class="sensorClass">
              <span class="h-2 w-2 rounded-full" :class="sensorDotClass"></span>
              {{ sensorLabel }}
            </span>
            <button class="icon-btn lg:hidden" type="button" @click="mobileOpen = !mobileOpen" aria-label="Toggle menu">
              <Menu :size="20" />
            </button>
          </div>
        </div>
        <div v-if="mobileOpen" class="border-t border-slate-200 bg-white/95 p-3 lg:hidden">
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
      </header>

      <main class="px-4 py-6 sm:px-6 lg:px-8">
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
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useLocationStore } from '../stores/location'

const props = defineProps({
  title: { type: String, required: true },
  eyebrow: { type: String, default: 'Operations' },
  sensorStatus: { type: String, default: 'active' },
})

defineOptions({
  name: 'MainLayout',
})

const router = useRouter()
const auth = useAuthStore()
const locations = useLocationStore()
const mobileOpen = ref(false)

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
const sensorLabel = computed(() => {
  const status = props.sensorStatus || 'disconnected'
  if (status === 'disconnected') return 'Sensor Disconnected'
  if (status === 'inactive') return 'Sensor Inactive'
  return 'Sensor Active'
})
const sensorClass = computed(() => {
  if (props.sensorStatus === 'disconnected') return 'border-red-200 bg-red-50 text-red-700'
  if (props.sensorStatus === 'inactive') return 'border-amber-200 bg-amber-50 text-amber-700'
  return 'border-emerald-200 bg-emerald-50 text-emerald-700'
})
const sensorDotClass = computed(() => {
  if (props.sensorStatus === 'disconnected') return 'bg-red-500'
  if (props.sensorStatus === 'inactive') return 'bg-amber-500'
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

onMounted(() => {
  locations.fetchLocations().catch(() => {})
})
</script>
