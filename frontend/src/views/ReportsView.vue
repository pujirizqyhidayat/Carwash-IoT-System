<template>
  <MainLayout title="Reports" eyebrow="Period summary">
    <div class="space-y-6">
      <section class="card p-5">
        <div class="grid gap-4 md:grid-cols-[1fr_1fr_auto_auto]">
          <div>
            <label class="label">Start</label>
            <select v-model="filters.start_month" class="input">
              <option value="">Select start month</option>
              <option v-for="month in monthOptions" :key="month.value" :value="month.value">
                {{ month.label }}
              </option>
            </select>
          </div>
          <div>
            <label class="label">End</label>
            <select v-model="filters.end_month" class="input">
              <option value="">Select end month</option>
              <option v-for="month in monthOptions" :key="month.value" :value="month.value">
                {{ month.label }}
              </option>
            </select>
          </div>
          <div class="flex items-end">
            <button class="btn-outline whitespace-nowrap" type="button" @click="exportReport('pdf')" :disabled="!canExport">
              <FileDown :size="17" />
              PDF
            </button>
          </div>
          <div class="flex items-end">
            <button class="btn-outline whitespace-nowrap" type="button" @click="exportReport('excel')" :disabled="!canExport">
              <Sheet :size="17" />
              Excel
            </button>
          </div>
        </div>
        <p v-if="periodError" class="mt-3 text-sm font-medium text-red-600">{{ periodError }}</p>
      </section>

      <section class="card overflow-hidden">
        <div class="border-b border-slate-200 px-5 py-4">
          <h2 class="section-title">{{ reportTitle }}</h2>
          <p class="muted">{{ activeLocationName }} Reports</p>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full min-w-[920px]">
            <thead class="table-head">
              <tr>
                <th class="px-4 py-3">Period</th>
                <th class="px-4 py-3">Location</th>
                <th class="px-4 py-3 text-right">Vehicle Entry (In Days)</th>
                <th class="px-4 py-3 text-right">Total Transactions</th>
                <th class="px-4 py-3 text-right">Total Revenue</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!hasSelectedPeriod">
                <td class="table-cell text-center" colspan="5">Select start and end month to view report data</td>
              </tr>
              <tr v-else-if="!isValidPeriod">
                <td class="table-cell text-center" colspan="5">End period cannot be before start period</td>
              </tr>
              <tr v-else-if="reports.length === 0">
                <td class="table-cell text-center" colspan="5">No data available</td>
              </tr>
              <template v-else>
                <tr v-for="item in reports" :key="item.id">
                  <td class="table-cell">{{ formatReportDate(item.summary_date) }}</td>
                  <td class="table-cell">{{ item.location?.location_name || activeLocationName }}</td>
                  <td class="table-cell text-right font-semibold">{{ item.total_vehicle }}</td>
                  <td class="table-cell text-right font-semibold">{{ item.total_transactions || 0 }}</td>
                  <td class="table-cell text-right font-semibold text-emerald-600">{{ formatCurrency(revenueFor(item)) }}</td>
                </tr>
                <tr class="bg-slate-50">
                  <td class="table-cell font-bold" colspan="2">Total</td>
                  <td class="table-cell text-right font-bold">{{ totalVehicles }}</td>
                  <td class="table-cell text-right font-bold">{{ totalTransactions }}</td>
                  <td class="table-cell text-right font-bold text-emerald-700">{{ formatCurrency(totalRevenue) }}</td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </section>
    </div>
    <AppToast :message="toast.message" :type="toast.type" />
  </MainLayout>
</template>

<script setup>
import { FileDown, Sheet } from '@lucide/vue'
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import AppToast from '../components/AppToast.vue'
import MainLayout from '../layouts/mainlayout.vue'
import { api, downloadFile, extractError } from '../services/api'
import { useLocationStore } from '../stores/location'

const locations = useLocationStore()
const currentYear = new Date().getFullYear()
const reports = ref([])
const toast = reactive({ message: '', type: 'success' })
const filters = reactive({
  start_month: '',
  end_month: '',
})
const monthOptions = [
  { value: 1, label: 'January' },
  { value: 2, label: 'February' },
  { value: 3, label: 'March' },
  { value: 4, label: 'April' },
  { value: 5, label: 'May' },
  { value: 6, label: 'June' },
  { value: 7, label: 'July' },
  { value: 8, label: 'August' },
  { value: 9, label: 'September' },
  { value: 10, label: 'October' },
  { value: 11, label: 'November' },
  { value: 12, label: 'December' },
]

const hasSelectedPeriod = computed(() => filters.start_month !== '' && filters.end_month !== '')
const isValidPeriod = computed(() => hasSelectedPeriod.value && Number(filters.end_month) >= Number(filters.start_month))
const canExport = computed(() => isValidPeriod.value && reports.value.length > 0)
const periodError = computed(() => (hasSelectedPeriod.value && !isValidPeriod.value ? 'End period cannot be before start period.' : ''))
const startMonthName = computed(() => monthName(filters.start_month))
const endMonthName = computed(() => monthName(filters.end_month))
const reportTitle = computed(() => (hasSelectedPeriod.value ? `${startMonthName.value} - ${endMonthName.value} Report` : 'Report'))
const activeLocationName = computed(() => locations.activeLocation?.location_name || 'Selected Location')
const exportBaseName = computed(() => `${startMonthName.value} - ${endMonthName.value} ${activeLocationName.value} Reports`)
const totalVehicles = computed(() => reports.value.reduce((sum, item) => sum + Number(item.total_vehicle || 0), 0))
const totalTransactions = computed(() => reports.value.reduce((sum, item) => sum + Number(item.total_transactions || 0), 0))
const totalRevenue = computed(() => reports.value.reduce((sum, item) => sum + revenueFor(item), 0))

function monthName(value) {
  return monthOptions.find((month) => month.value === Number(value))?.label || '-'
}

function monthStart(value) {
  return currentYear + '-' + String(value).padStart(2, '0') + '-01'
}

function monthEnd(value) {
  const day = new Date(currentYear, Number(value), 0).getDate()
  return currentYear + '-' + String(value).padStart(2, '0') + '-' + String(day).padStart(2, '0')
}

function formatReportDate(value) {
  if (!value) return '-'

  const [year, month, day] = String(value).slice(0, 10).split('-')
  return Number(day) + '-' + Number(month) + '-' + year
}

function revenueFor(item) {
  return Number(item.total_revenue || 0)
}

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(Number(value || 0))
}

function params() {
  return {
    location_id: locations.activeLocationId,
    start_date: monthStart(filters.start_month),
    end_date: monthEnd(filters.end_month),
  }
}

async function fetchReports() {
  if (!isValidPeriod.value) {
    reports.value = []
    return
  }

  const { data } = await api.get('/reports', { params: params() })
  reports.value = data
}

async function exportReport(type) {
  if (!canExport.value) return

  try {
    const extension = type === 'pdf' ? 'pdf' : 'xlsx'
    await downloadFile(`/reports/export/${type}`, `${exportBaseName.value}.${extension}`, params())
    showToast(`${type === 'pdf' ? 'PDF' : 'Excel'} downloaded`)
  } catch (error) {
    showToast(extractError(error, 'Failed to export report'), 'error')
  }
}

function showToast(message, type = 'success') {
  toast.message = message
  toast.type = type
  window.setTimeout(() => {
    toast.message = ''
  }, 2600)
}

function handleActiveLocationChanged() {
  fetchReports()
}

watch(() => [filters.start_month, filters.end_month], fetchReports)

onMounted(async () => {
  await locations.fetchLocations().catch(() => {})
  window.addEventListener('active-location-changed', handleActiveLocationChanged)
})

onUnmounted(() => {
  window.removeEventListener('active-location-changed', handleActiveLocationChanged)
})
</script>
