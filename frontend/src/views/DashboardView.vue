<template>
  <MainLayout title="Dashboard" eyebrow="Overview" :sensor-status="summary.sensor_status">
    <div class="space-y-6">
      <section class="dashboard-banner p-5 sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">{{ locations.activeLocation?.location_name || 'Selected Location' }}</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-950">Operational Snapshot</h2>
            <p class="mt-2 text-sm text-slate-500">Last synced from backend at {{ lastUpdated || '-' }}</p>
          </div>
          <div class="grid grid-cols-3 gap-3 text-center">
            <div class="rounded-lg border border-blue-100 bg-white/80 px-4 py-3">
              <p class="text-xs text-slate-500">Today</p>
              <p class="mt-1 text-xl font-bold text-blue-700">{{ summary.vehicles_today }}</p>
            </div>
            <div class="rounded-lg border border-blue-100 bg-white/80 px-4 py-3">
              <p class="text-xs text-slate-500">Week</p>
              <p class="mt-1 text-xl font-bold text-slate-950">{{ summary.vehicles_this_week }}</p>
            </div>
            <div class="rounded-lg border border-blue-100 bg-white/80 px-4 py-3">
              <p class="text-xs text-slate-500">Month</p>
              <p class="mt-1 text-xl font-bold text-emerald-700">{{ summary.vehicles_this_month }}</p>
            </div>
          </div>
        </div>
      </section>

      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <MetricCard label="Today" :value="summary.vehicles_today" sublabel="vehicles counted" :icon="Car" />
        <MetricCard label="This Week" :value="summary.vehicles_this_week" sublabel="weekly total" :icon="CalendarDays" />
        <MetricCard label="This Month" :value="summary.vehicles_this_month" sublabel="monthly total" :icon="BarChart3" />
        <MetricCard label="Sensor" :value="sensorText" sublabel="current device state" :icon="Radar" />
      </div>

      <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
        <section class="card-elevated overflow-hidden">
          <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <div>
              <h2 class="section-title">Daily Trend</h2>
              <p class="muted">Last data points from backend chart API</p>
            </div>
            <button class="btn-outline" type="button" @click="refresh">
              <RefreshCw :size="17" />
              Refresh
            </button>
          </div>
          <div class="p-5">
            <div v-if="chart.length" class="flex h-64 items-end gap-2">
              <div v-for="item in chart" :key="item.label" class="flex min-w-0 flex-1 flex-col items-center gap-2">
                <div class="w-full rounded-t bg-blue-600 shadow-sm shadow-blue-200" :style="{ height: barHeight(item.value) }"></div>
                <span class="w-full truncate text-center text-[11px] text-slate-500">{{ shortLabel(item.label) }}</span>
              </div>
            </div>
            <div v-else class="flex h-64 items-center justify-center text-sm text-slate-500">No data available</div>
          </div>
        </section>

        <section class="card-elevated overflow-hidden">
          <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="section-title">Recent Activity</h2>
            <p class="muted">Latest vehicle detections</p>
          </div>
          <div class="divide-y divide-slate-100">
            <div v-if="!activities.length" class="px-5 py-10 text-center text-sm text-slate-500">No data available</div>
            <div v-for="item in activities" :key="item.entry_id" class="flex items-center gap-3 px-5 py-4">
              <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-700">
                <Car :size="18" />
              </div>
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-slate-900">{{ item.sensor_name || 'Sensor' }}</p>
                <p class="text-xs text-slate-500">{{ item.entry_time }}</p>
              </div>
              <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">
                +{{ item.vehicle_count }}
              </span>
            </div>
          </div>
        </section>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { BarChart3, CalendarDays, Car, Radar, RefreshCw } from '@lucide/vue'
import { computed, onMounted, onUnmounted, ref } from 'vue'
import MainLayout from '../layouts/mainlayout.vue'
import { api } from '../services/api'
import { useLocationStore } from '../stores/location'
import MetricCard from './partials/MetricCard.vue'

const locations = useLocationStore()
const summary = ref({
  vehicles_today: 0,
  vehicles_this_week: 0,
  vehicles_this_month: 0,
  sensor_status: 'disconnected',
})
const activities = ref([])
const chart = ref([])
const lastUpdated = ref('')
let timer = null

const sensorText = computed(() => {
  if (summary.value.sensor_status === 'disconnected') return 'Disconnected'
  if (summary.value.sensor_status === 'inactive') return 'Inactive'
  return 'Active'
})
const maxChart = computed(() => Math.max(...chart.value.map((item) => Number(item.value || 0)), 1))

function barHeight(value) {
  return `${Math.max((Number(value || 0) / maxChart.value) * 100, 4)}%`
}

function shortLabel(label) {
  return String(label).slice(5)
}

async function refresh() {
  const [summaryRes, activityRes, chartRes] = await Promise.all([
    api.get('/dashboard/summary', { params: { location_id: locations.activeLocationId } }),
    api.get('/dashboard/recent-activities', { params: { location_id: locations.activeLocationId, limit: 8 } }),
    api.get('/dashboard/chart', { params: { location_id: locations.activeLocationId, period: 'daily' } }),
  ])
  summary.value = summaryRes.data
  activities.value = activityRes.data
  chart.value = chartRes.data
  lastUpdated.value = summaryRes.data.last_updated
}

onMounted(() => {
  locations.fetchLocations().catch(() => {})
  refresh()
  window.addEventListener('active-location-changed', refresh)
  timer = window.setInterval(refresh, 15000)
})

onUnmounted(() => {
  if (timer) window.clearInterval(timer)
  window.removeEventListener('active-location-changed', refresh)
})
</script>
