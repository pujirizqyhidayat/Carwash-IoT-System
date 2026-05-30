<template>
  <MainLayout title="Monitoring" eyebrow="Cashier view" :sensor-status="today.sensor_status">
    <div class="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
      <section class="card p-6">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ today.date }}</p>
        <p class="mt-4 text-6xl font-bold text-blue-700">{{ today.vehicles_today }}</p>
        <p class="mt-2 text-sm text-slate-500">vehicles counted today</p>
        <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-4">
          <p class="text-sm font-semibold text-slate-900">{{ sensorLabel }}</p>
          <p class="mt-1 text-sm text-slate-500">Auto refresh every 10 seconds</p>
        </div>
      </section>

      <section class="card">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
          <div>
            <h2 class="section-title">Hourly Breakdown</h2>
            <p class="muted">Vehicle count per hour</p>
          </div>
          <button class="btn-outline" type="button" @click="refresh">
            <RefreshCw :size="17" />
            Refresh
          </button>
        </div>
        <div class="p-5">
          <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
            <div v-for="item in hourly" :key="item.hour" class="rounded-lg border border-slate-200 bg-white p-3">
              <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-slate-800">{{ item.hour }}</span>
                <span class="rounded-full bg-blue-50 px-2 py-1 text-xs font-bold text-blue-700">{{ item.total_vehicle }}</span>
              </div>
              <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-blue-600" :style="{ width: widthFor(item.total_vehicle) }"></div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </MainLayout>
</template>

<script setup>
import { RefreshCw } from '@lucide/vue'
import { computed, onMounted, onUnmounted, ref } from 'vue'
import MainLayout from '../layouts/mainlayout.vue'
import { api } from '../services/api'
import { useLocationStore } from '../stores/location'

const locations = useLocationStore()
const today = ref({ date: '', vehicles_today: 0, sensor_status: 'disconnected' })
const hourly = ref([])
let timer = null

const maxHourly = computed(() => Math.max(...hourly.value.map((item) => Number(item.total_vehicle || 0)), 1))
const sensorLabel = computed(() => {
  if (today.value.sensor_status === 'disconnected') return 'Sensor Disconnected'
  if (today.value.sensor_status === 'inactive') return 'Sensor Inactive'
  return 'Sensor Active'
})

function widthFor(value) {
  return `${Math.max((Number(value || 0) / maxHourly.value) * 100, 3)}%`
}

async function refresh() {
  const [todayRes, hourlyRes] = await Promise.all([
    api.get('/monitoring/today', { params: { location_id: locations.activeLocationId } }),
    api.get('/monitoring/hourly', { params: { location_id: locations.activeLocationId } }),
  ])
  today.value = todayRes.data
  hourly.value = hourlyRes.data
}

onMounted(() => {
  locations.fetchLocations().catch(() => {})
  refresh()
  window.addEventListener('active-location-changed', refresh)
  timer = window.setInterval(refresh, 10000)
})

onUnmounted(() => {
  if (timer) window.clearInterval(timer)
  window.removeEventListener('active-location-changed', refresh)
})
</script>
