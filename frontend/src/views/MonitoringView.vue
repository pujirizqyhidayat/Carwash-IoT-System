<template>
  <MainLayout title="Monitoring" eyebrow="Cashier view" :sensor-status="today.sensor_status">
    <section>
      <div class="grid gap-6 xl:grid-cols-[260px_minmax(0,1fr)]">
        <aside class="space-y-4">
          <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-blue-500/5">
            <p class="text-xs font-semibold text-blue-500">{{ today.date || '-' }}</p>
            <p class="mt-5 text-6xl font-bold leading-none text-blue-600">{{ today.vehicles_today }}</p>
            <p class="mt-2 text-sm text-slate-400">vehicles counted today</p>
          </section>

          <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-blue-500/5">
            <div class="flex items-center gap-3">
              <span class="h-2 w-2 rounded-full" :class="statusDotClass"></span>
              <p class="text-sm font-bold text-slate-700">{{ sensorLabel }}</p>
            </div>
            <p class="mt-2 text-sm text-slate-400">Auto refresh every 10 seconds</p>
          </section>

          <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-blue-500/5">
            <h2 class="text-xs font-bold uppercase tracking-wide text-slate-500">Top Peak Hours</h2>
            <div class="mt-4 space-y-3">
              <div v-if="!peakHours.length" class="text-sm text-slate-400">No data available</div>
              <div v-for="item in peakHours" :key="item.hour" class="grid grid-cols-[42px_1fr_28px] items-center gap-3">
                <span class="text-xs font-semibold text-slate-500">{{ hourLabel(item.hour) }}</span>
                <div class="h-2 overflow-hidden rounded-full bg-blue-100">
                  <div class="h-full rounded-full bg-blue-500" :style="{ width: widthFor(item.total_vehicle) }"></div>
                </div>
                <span class="text-right text-xs font-bold text-blue-600">{{ item.total_vehicle }}</span>
              </div>
            </div>
          </section>
        </aside>

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm shadow-blue-500/5">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <h2 class="text-lg font-bold text-slate-950">Hourly Breakdown</h2>
              <p class="mt-1 text-sm text-slate-400">Vehicle count per hour - hover a bar for details</p>
            </div>
            <button
              class="inline-flex h-8 items-center justify-center gap-2 rounded-lg border border-blue-100 bg-blue-50 px-3 text-xs font-semibold text-blue-600 transition hover:border-blue-200 hover:bg-blue-100"
              type="button"
              @click="refresh"
            >
              <RefreshCw :size="14" />
              Refresh
            </button>
          </div>

          <div class="mt-6">
            <div class="relative h-72 border-y border-dashed border-slate-200 sm:h-80">
              <div class="pointer-events-none absolute inset-x-0 top-1/4 border-t border-dashed border-slate-200"></div>
              <div class="pointer-events-none absolute inset-x-0 top-1/2 border-t border-dashed border-slate-200"></div>
              <div class="pointer-events-none absolute inset-x-0 top-3/4 border-t border-dashed border-slate-200"></div>

              <div class="relative flex h-full items-end gap-1.5 pt-2 sm:gap-2">
                <div v-for="item in chartHours" :key="item.hour" class="group relative flex h-full min-w-0 flex-1 items-end justify-center">
                  <div
                    class="relative min-h-0 w-full rounded-t-md transition duration-200 group-hover:opacity-80"
                    :class="barClass(item)"
                    :style="{ height: barHeight(item.total_vehicle) }"
                  >
                    <div class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 hidden -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-950 px-2 py-1 text-xs font-semibold text-white shadow-lg group-hover:block">
                      {{ hourLabel(item.hour) }} - {{ item.total_vehicle }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-2 grid grid-cols-8 text-center text-xs text-slate-400">
              <span v-for="tick in hourTicks" :key="tick">{{ tick }}</span>
            </div>

            <div class="mt-5 flex flex-wrap items-center gap-5 text-xs text-slate-400">
              <span class="inline-flex items-center gap-2">
                <span class="h-3 w-3 rounded bg-blue-600"></span>
                Current hour
              </span>
              <span class="inline-flex items-center gap-2">
                <span class="h-3 w-3 rounded bg-blue-300"></span>
                Past
              </span>
              <span class="inline-flex items-center gap-2">
                <span class="h-3 w-3 rounded bg-blue-100"></span>
                Upcoming
              </span>
            </div>
          </div>

          <div class="mt-6 border-t border-slate-100 pt-5">
            <div class="grid gap-4 md:grid-cols-3">
              <article v-for="period in periodSummaries" :key="period.label" class="rounded-lg bg-slate-50 p-4">
                <p class="text-xs font-bold uppercase text-slate-400">{{ period.label }} ({{ period.range }})</p>
                <p class="mt-2 text-2xl font-bold text-slate-800">{{ period.total }}</p>
              </article>
            </div>
          </div>
        </section>
      </div>
    </section>
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

const currentHour = computed(() => new Date().getHours())
const maxHourly = computed(() => Math.max(...chartHours.value.map((item) => Number(item.total_vehicle || 0)), 1))
const chartHours = computed(() => {
  const totalsByHour = new Map()
  for (const item of hourly.value) {
    const hour = hourNumber(item.hour ?? item.time ?? item.label ?? item.entry_hour ?? item.created_at)
    const total = Number(item.total_vehicle ?? item.total_vehicles ?? item.total ?? item.count ?? item.value ?? 0)
    totalsByHour.set(hour, (totalsByHour.get(hour) || 0) + total)
  }

  const hours = Array.from({ length: 24 }, (_, hour) => ({
    hour,
    total_vehicle: totalsByHour.get(hour) || 0,
  }))

  const hourlyTotal = hours.reduce((sum, item) => sum + Number(item.total_vehicle || 0), 0)
  const todayTotal = Number(today.value.vehicles_today || 0)
  if (hourlyTotal === 0 && todayTotal > 0) {
    hours[currentHour.value].total_vehicle = todayTotal
  }

  return hours
})
const peakHours = computed(() =>
  [...chartHours.value]
    .filter((item) => item.total_vehicle > 0)
    .sort((a, b) => b.total_vehicle - a.total_vehicle)
    .slice(0, 5),
)
const hourTicks = ['00', '03', '06', '09', '12', '15', '18', '21']
const sensorLabel = computed(() => {
  if (today.value.sensor_status === 'disconnected') return 'Sensor Disconnected'
  if (today.value.sensor_status === 'inactive') return 'Sensor Inactive'
  return 'Sensor Active'
})
const statusDotClass = computed(() => {
  if (today.value.sensor_status === 'disconnected') return 'bg-red-400'
  if (today.value.sensor_status === 'inactive') return 'bg-amber-400'
  return 'bg-emerald-400'
})
const periodSummaries = computed(() => [
  summarizePeriod('Morning', '07:00-12:00', 7, 12),
  summarizePeriod('Afternoon', '12:00-18:00', 12, 18),
  summarizePeriod('Evening', '18:00-21:00', 18, 21),
])

function hourNumber(value) {
  const match = String(value ?? '').match(/\d{1,2}/)
  if (!match) return 0
  return Math.min(Math.max(Number(match[0]), 0), 23)
}

function hourLabel(hour) {
  return `${String(hour).padStart(2, '0')}:00`
}

function widthFor(value) {
  const total = Number(value || 0)
  if (total <= 0) return '0%'
  return `${Math.max((total / maxHourly.value) * 100, 6)}%`
}

function barHeight(value) {
  const total = Number(value || 0)
  if (total <= 0) return '2px'
  return `${Math.max(Math.sqrt(total / maxHourly.value) * 100, 14)}%`
}

function barClass(item) {
  const total = Number(item.total_vehicle || 0)
  if (total > 0 && item.hour === currentHour.value) return 'bg-blue-600 shadow-lg shadow-blue-500/20'
  if (total > 0) return 'bg-blue-500 shadow-sm shadow-blue-500/20'
  if (item.hour > currentHour.value) return 'bg-blue-100'
  return 'bg-blue-200'
}

function summarizePeriod(label, range, startHour, endHour) {
  const entries = chartHours.value.filter((item) => item.hour >= startHour && item.hour < endHour)
  const total = entries.reduce((sum, item) => sum + Number(item.total_vehicle || 0), 0)

  return {
    label,
    range,
    total,
  }
}

async function refresh() {
  const { data: todayData } = await api.get('/monitoring/today', {
    params: { location_id: locations.activeLocationId },
  })
  today.value = todayData

  const { data: hourlyData } = await api.get('/monitoring/hourly', {
    params: { location_id: locations.activeLocationId, date: today.value.date },
  })
  hourly.value = hourlyData
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










