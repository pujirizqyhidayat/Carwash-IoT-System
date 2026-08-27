<template>
  <MainLayout title="Monitoring" eyebrow="Cashier transaction desk" :sensor-status="today.sensor_status">
    <section class="space-y-6">
      <div class="grid gap-6 xl:grid-cols-[260px_minmax(0,1fr)]">
        <aside class="space-y-4">
          <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-blue-500/5">
            <p class="text-xs font-semibold text-blue-500">{{ today.date || '-' }}</p>
            <p class="mt-5 text-6xl font-bold leading-none text-blue-600">{{ today.vehicles_today }}</p>
            <p class="mt-2 text-sm text-slate-400">vehicles counted today</p>
          </section>

          <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-blue-500/5">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Today's Transactions</p>
            <p class="mt-3 text-3xl font-bold text-emerald-600">{{ formatCurrency(todayRevenue) }}</p>
            <p class="mt-2 text-sm text-slate-400">{{ today.transactions_today || 0 }} paid transactions</p>
          </section>

          <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-blue-500/5">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Pending Input</p>
            <p class="mt-3 text-3xl font-bold text-amber-500">{{ today.pending_transactions || 0 }}</p>
            <p class="mt-2 text-sm text-slate-400">vehicles need cashier transaction</p>
          </section>

          <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-blue-500/5">
            <div class="flex items-center gap-3">
              <span class="h-2 w-2 rounded-full" :class="statusDotClass"></span>
              <p class="text-sm font-bold text-slate-700">{{ sensorLabel }}</p>
            </div>
            <p class="mt-2 text-sm text-slate-400">Auto refresh every 10 seconds</p>
          </section>
        </aside>

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm shadow-blue-500/5">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <h2 class="text-lg font-bold text-slate-950">Hourly Breakdown</h2>
              <p class="mt-1 text-sm text-slate-400">Vehicle count and transaction total per hour</p>
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
                      {{ hourLabel(item.hour) }} - {{ item.total_vehicle }} vehicles - {{ formatCurrency(item.total_revenue) }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-2 grid grid-cols-8 text-center text-xs text-slate-400">
              <span v-for="tick in hourTicks" :key="tick">{{ tick }}</span>
            </div>
          </div>

          <div class="mt-6 border-t border-slate-100 pt-5">
            <div class="grid gap-4 md:grid-cols-3">
              <article v-for="period in periodSummaries" :key="period.label" class="rounded-lg bg-slate-50 p-4">
                <p class="text-xs font-bold uppercase text-slate-400">{{ period.label }} ({{ period.range }})</p>
                <p class="mt-2 text-2xl font-bold text-slate-800">{{ period.total }}</p>
                <p class="mt-1 text-sm font-semibold text-emerald-600">{{ formatCurrency(period.revenue) }}</p>
              </article>
            </div>
          </div>
        </section>
      </div>

      <section class="rounded-lg border border-slate-200 bg-white shadow-sm shadow-blue-500/5">
        <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h2 class="text-lg font-bold text-slate-950">Vehicle Transaction Queue</h2>
            <p class="mt-1 text-sm text-slate-400">Cashier records the wash service for each detected vehicle</p>
          </div>
          <span class="text-sm font-semibold text-slate-500">{{ transactionEntries.length }} entries</span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full min-w-[980px]">
            <thead class="table-head">
              <tr>
                <th class="px-4 py-3">Entry Time</th>
                <th class="px-4 py-3 text-right">Vehicle</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Service</th>
                <th class="px-4 py-3 text-right">Price</th>
                <th class="px-4 py-3 text-right">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="transactionEntries.length === 0">
                <td class="table-cell text-center" colspan="6">No vehicle entries today</td>
              </tr>
              <tr v-for="entry in transactionEntries" :key="entry.id">
                <td class="table-cell">{{ formatDateTime(entry.entry_time) }}</td>
                <td class="table-cell text-right font-semibold">{{ entry.vehicle_count }}</td>
                <td class="table-cell">
                  <span v-if="entry.transaction" class="inline-flex rounded-full bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700">Paid</span>
                  <span v-else class="inline-flex rounded-full bg-amber-50 px-2 py-1 text-xs font-bold text-amber-700">Pending</span>
                </td>
                <td class="table-cell">
                  <div v-if="entry.transaction" class="font-semibold text-slate-700">{{ entry.transaction.service_name }}</div>
                  <select v-else v-model="formFor(entry.id).wash_service_id" class="input h-10" :disabled="!canRecordTransactions" @change="applySelectedService(entry.id)">
                    <option value="">Select service</option>
                    <option v-for="service in services" :key="service.id" :value="service.id">{{ service.service_name }}</option>
                    <option value="custom">Custom</option>
                  </select>
                  <input
                    v-if="!entry.transaction && formFor(entry.id).wash_service_id === 'custom'"
                    v-model="formFor(entry.id).service_name"
                    class="input mt-2 h-10"
                    type="text"
                    placeholder="Custom service"
                    :disabled="!canRecordTransactions || formFor(entry.id).wash_service_id !== 'custom'"
                  />
                </td>
                <td class="table-cell text-right">
                  <span v-if="entry.transaction" class="font-semibold text-emerald-600">{{ formatCurrency(entry.transaction.price) }}</span>
                  <input
                    v-else
                    v-model.number="formFor(entry.id).price"
                    class="input h-10 text-right"
                    type="number"
                    min="0"
                    :disabled="!canRecordTransactions || formFor(entry.id).wash_service_id !== 'custom'"
                  />
                </td>
                <td class="table-cell text-right">
                  <button
                    v-if="!entry.transaction"
                    class="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-blue-600 px-3 text-xs font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                    type="button"
                    :disabled="!canRecordTransactions || savingId === entry.id || !canSave(entry.id)"
                    @click="saveTransaction(entry)"
                  >
                    <Save :size="14" />
                    Save
                  </button>
                  <span v-else class="text-xs text-slate-400">{{ entry.transaction.cashier_name || '-' }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </section>
    <AppToast :message="toast.message" :type="toast.type" />
  </MainLayout>
</template>

<script setup>
import { RefreshCw, Save } from '@lucide/vue'
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue'
import AppToast from '../components/AppToast.vue'
import MainLayout from '../layouts/mainlayout.vue'
import { api, extractError } from '../services/api'
import { useAuthStore } from '../stores/auth'
import { useLocationStore } from '../stores/location'

const auth = useAuthStore()
const locations = useLocationStore()
const today = ref({ date: '', vehicles_today: 0, transactions_today: 0, pending_transactions: 0, total_revenue: 0, sensor_status: 'disconnected' })
const hourly = ref([])
const services = ref([])
const transactionEntries = ref([])
const transactionForms = reactive({})
const toast = reactive({ message: '', type: 'success' })
const savingId = ref(null)
let timer = null

const canRecordTransactions = computed(() => ['cashier', 'admin'].includes(auth.role))
const currentHour = computed(() => new Date().getHours())
const todayRevenue = computed(() => Number(today.value.total_revenue || 0))
const maxHourly = computed(() => Math.max(...chartHours.value.map((item) => Number(item.total_vehicle || 0)), 1))
const chartHours = computed(() => {
  const totalsByHour = new Map()
  const revenueByHour = new Map()
  for (const item of hourly.value) {
    const hour = hourNumber(item.hour ?? item.time ?? item.label ?? item.entry_hour ?? item.created_at)
    const total = Number(item.total_vehicle ?? item.total_vehicles ?? item.total ?? item.count ?? item.value ?? 0)
    const revenue = Number(item.total_revenue ?? 0)
    totalsByHour.set(hour, (totalsByHour.get(hour) || 0) + total)
    revenueByHour.set(hour, (revenueByHour.get(hour) || 0) + revenue)
  }

  const hours = Array.from({ length: 24 }, (_, hour) => ({
    hour,
    total_vehicle: totalsByHour.get(hour) || 0,
    total_revenue: revenueByHour.get(hour) || 0,
  }))

  const hourlyTotal = hours.reduce((sum, item) => sum + Number(item.total_vehicle || 0), 0)
  const todayTotal = Number(today.value.vehicles_today || 0)
  if (hourlyTotal === 0 && todayTotal > 0) {
    hours[currentHour.value].total_vehicle = todayTotal
  }

  return hours
})
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

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(Number(value || 0))
}

function formatDateTime(value) {
  if (!value) return '-'
  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(value.replace(' ', 'T')))
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
  const revenue = entries.reduce((sum, item) => sum + Number(item.total_revenue || 0), 0)

  return {
    label,
    range,
    total,
    revenue,
  }
}

function formFor(entryId) {
  if (!transactionForms[entryId]) {
    transactionForms[entryId] = {
      wash_service_id: '',
      service_name: '',
      price: 0,
    }
  }

  return transactionForms[entryId]
}

function applySelectedService(entryId) {
  const form = formFor(entryId)
  if (form.wash_service_id === 'custom') {
    form.service_name = ''
    form.price = 0
    return
  }

  const service = services.value.find((item) => Number(item.id) === Number(form.wash_service_id))
  if (service) {
    form.service_name = service.service_name
    form.price = Number(service.price || 0)
  }
}

function canSave(entryId) {
  const form = formFor(entryId)
  const hasService = form.wash_service_id && (form.wash_service_id !== 'custom' || form.service_name.trim())
  return hasService && Number(form.price) >= 0
}

async function saveTransaction(entry) {
  const form = formFor(entry.id)
  savingId.value = entry.id

  try {
    await api.post(`/monitoring/entries/${entry.id}/transaction`, {
      wash_service_id: form.wash_service_id === 'custom' ? null : form.wash_service_id,
      service_name: form.wash_service_id === 'custom' ? form.service_name : null,
      price: form.wash_service_id === 'custom' ? form.price : null,
      payment_status: 'paid',
    })
    showToast('Transaction saved')
    await refresh()
  } catch (error) {
    showToast(extractError(error, 'Failed to save transaction'), 'error')
  } finally {
    savingId.value = null
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

  const { data: entriesData } = await api.get('/monitoring/entries', {
    params: { location_id: locations.activeLocationId, date: today.value.date },
  })
  transactionEntries.value = entriesData
}

async function fetchServices() {
  const { data } = await api.get('/monitoring/services')
  services.value = data
}

function showToast(message, type = 'success') {
  toast.message = message
  toast.type = type
  window.setTimeout(() => {
    toast.message = ''
  }, 2600)
}

onMounted(() => {
  locations.fetchLocations().catch(() => {})
  fetchServices().catch(() => {})
  refresh()
  window.addEventListener('active-location-changed', refresh)
  timer = window.setInterval(refresh, 10000)
})

onUnmounted(() => {
  if (timer) window.clearInterval(timer)
  window.removeEventListener('active-location-changed', refresh)
})
</script>
