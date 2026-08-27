<template>
  <MainLayout title="Dashboard" eyebrow="Overview" :sensor-status="summary.sensor_status">
    <div class="space-y-6">
      <section class="dashboard-banner p-5 sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-200/70">{{ locations.activeLocation?.location_name || 'Selected Location' }}</p>
            <h2 class="mt-2 text-2xl font-bold text-white">Operational Overview</h2>
            <p class="mt-2 text-sm text-blue-100/55">Last synced {{ lastUpdated || '-' }}</p>
          </div>
          <div class="grid grid-cols-3 gap-3 text-center">
            <div class="rounded-lg border border-white/10 bg-white/8 px-4 py-3 shadow-lg shadow-blue-950/20">
              <p class="text-xs text-blue-100/55">Today</p>
              <p class="mt-1 text-xl font-bold text-blue-100">{{ summary.vehicles_today }}</p>
            </div>
            <div class="rounded-lg border border-white/10 bg-white/8 px-4 py-3 shadow-lg shadow-blue-950/20">
              <p class="text-xs text-blue-100/55">Week</p>
              <p class="mt-1 text-xl font-bold text-white">{{ summary.vehicles_this_week }}</p>
            </div>
            <div class="rounded-lg border border-white/10 bg-white/8 px-4 py-3 shadow-lg shadow-blue-950/20">
              <p class="text-xs text-blue-100/55">Month</p>
              <p class="mt-1 text-xl font-bold text-emerald-200">{{ summary.vehicles_this_month }}</p>
            </div>
          </div>
        </div>
      </section>


      <section v-if="sensorAlert" class="rounded-lg border px-5 py-4 shadow-sm" :class="sensorAlertClass">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex items-start gap-3">
            <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/70">
              <TriangleAlert :size="19" />
            </div>
            <div>
              <h2 class="text-sm font-bold">{{ sensorAlert.title }}</h2>
              <p class="mt-1 text-sm">{{ sensorAlert.message }}</p>
            </div>
          </div>
          <button class="btn-outline shrink-0" type="button" @click="refresh">
            <RefreshCw :size="17" />
            Check Now
          </button>
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
              <h2 class="section-title">Last 7 Days Graphic Chart</h2>
              <p class="muted">Last 7 Days</p>
            </div>
            <button class="btn-outline" type="button" @click="refresh">
              <RefreshCw :size="17" />
              Refresh
            </button>
          </div>
          <div class="p-5">
            <div v-if="chart.length" class="flex h-64 items-stretch gap-2">
              <div v-for="item in chart" :key="item.label" class="flex min-w-0 flex-1 flex-col items-center gap-2">
                <div class="relative flex min-h-0 w-full flex-1 items-end">
                  <div
                    class="w-full rounded-t bg-blue-500 shadow-lg shadow-blue-500/30 transition hover:bg-blue-600"
                    :style="{ height: barHeight(item.value) }"
                    @mouseenter="hoveredChart = item"
                    @mouseleave="hoveredChart = null"
                  ></div>
                  <div
                    v-if="hoveredChart?.label === item.label"
                    class="daily-trend-tooltip pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 -translate-x-1/2 whitespace-nowrap px-2.5 py-1.5 text-xs font-semibold shadow-lg"
                  >
                    {{ shortLabel(item.label) }}: {{ Number(item.value || 0) }}
                  </div>
                </div>
                <span class="w-full truncate text-center text-[11px] text-blue-100/55">{{ shortLabel(item.label) }}</span>
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
              <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-500/15 text-blue-100 ring-1 ring-blue-300/15">
                <Car :size="18" />
              </div>
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-white">{{ item.sensor_name || 'Sensor' }}</p>
                <p class="text-xs text-blue-100/55">{{ item.entry_time }}</p>
              </div>
              <span class="rounded-md bg-blue-500/15 px-2.5 py-1 text-xs font-bold text-blue-100 ring-1 ring-blue-300/15">
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
import { BarChart3, CalendarDays, Car, Radar, RefreshCw, TriangleAlert } from '@lucide/vue'
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
const hoveredChart = ref(null)
const lastUpdated = ref('')
let timer = null

const sensorText = computed(() => {
  if (summary.value.sensor_status === 'disconnected') return 'Disconnected'
  if (summary.value.sensor_status === 'inactive') return 'Inactive'
  return 'Active'
})
const sensorAlert = computed(() => {
  if (summary.value.sensor_status === 'disconnected') {
    return {
      title: 'Sensor Alert: Device disconnected',
      message: 'The sensor is down. Please check the IoT device connection and power source.',
    }
  }

  if (summary.value.sensor_status === 'inactive') {
    return {
      title: 'Sensor Alert: Sensor inactive',
      message: 'The sensor is not reporting active data. Please inspect the sensor status.',
    }
  }

  return null
})
const sensorAlertClass = computed(() => {
  if (summary.value.sensor_status === 'disconnected') return 'border-red-200 bg-red-50 text-red-700'
  return 'border-amber-200 bg-amber-50 text-amber-700'
})
const maxChart = computed(() => Math.max(...chart.value.map((item) => Number(item.value || 0)), 1))

function barHeight(value) {
  return `${Math.max((Number(value || 0) / maxChart.value) * 100, 4)}%`
}

function shortLabel(label) {
  return String(label).slice(5)
}

async function refresh() {
  const [summaryRes, activityRes, chartRes] = await Promise.allSettled([
    api.get('/dashboard/summary', { params: { location_id: locations.activeLocationId } }),
    api.get('/dashboard/recent-activities', { params: { location_id: locations.activeLocationId, limit: 8 } }),
    api.get('/dashboard/chart', { params: { location_id: locations.activeLocationId, period: 'daily' } }),
  ])

  if (summaryRes.status === 'fulfilled') {
    summary.value = summaryRes.value.data
    lastUpdated.value = summaryRes.value.data.last_updated
  }
  if (activityRes.status === 'fulfilled') {
    activities.value = activityRes.value.data
  }
  if (chartRes.status === 'fulfilled') {
    chart.value = chartRes.value.data
  }
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

