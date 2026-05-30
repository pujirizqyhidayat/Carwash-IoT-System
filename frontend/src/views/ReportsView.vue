<template>
  <MainLayout title="Reports" eyebrow="Daily summary">
    <div class="space-y-6">
      <section class="card p-5">
        <div class="grid gap-4 md:grid-cols-3">
          <div>
            <label class="label">Start Date</label>
            <input v-model="filters.start_date" class="input" type="date" />
          </div>
          <div>
            <label class="label">End Date</label>
            <input v-model="filters.end_date" class="input" type="date" />
          </div>
          <div class="flex items-end gap-2">
            <button class="btn-primary" type="button" @click="fetchReports">
              <Search :size="17" />
              Filter
            </button>
            <button class="btn-outline" type="button" @click="generateDaily">
              <CalendarPlus :size="17" />
              Generate
            </button>
          </div>
        </div>
      </section>

      <section class="card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
          <div>
            <h2 class="section-title">Vehicle Count Summaries</h2>
            <p class="muted">Daily report data from backend</p>
          </div>
          <div class="flex gap-2">
            <button class="btn-outline" type="button" @click="exportReport('pdf')">
              <FileDown :size="17" />
              PDF
            </button>
            <button class="btn-outline" type="button" @click="exportReport('excel')">
              <Sheet :size="17" />
              Excel
            </button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full min-w-[720px]">
            <thead class="table-head">
              <tr>
                <th class="px-4 py-3">Date</th>
                <th class="px-4 py-3">Location</th>
                <th class="px-4 py-3 text-right">Total Vehicle</th>
                <th class="px-4 py-3">Generated At</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="reports.length === 0">
                <td class="table-cell text-center" colspan="4">No data available</td>
              </tr>
              <tr v-for="item in reports" :key="item.id">
                <td class="table-cell">{{ item.summary_date }}</td>
                <td class="table-cell">{{ item.location?.location_name || item.location_id }}</td>
                <td class="table-cell text-right font-semibold">{{ item.total_vehicle }}</td>
                <td class="table-cell">{{ item.generated_at }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>
    <AppToast :message="toast.message" :type="toast.type" />
  </MainLayout>
</template>

<script setup>
import { CalendarPlus, FileDown, Search, Sheet } from '@lucide/vue'
import { onMounted, onUnmounted, reactive, ref } from 'vue'
import AppToast from '../components/AppToast.vue'
import MainLayout from '../layouts/mainlayout.vue'
import { api, downloadFile, extractError } from '../services/api'
import { useLocationStore } from '../stores/location'

const locations = useLocationStore()
const today = new Date().toISOString().slice(0, 10)
const reports = ref([])
const toast = reactive({ message: '', type: 'success' })
const filters = reactive({
  start_date: today,
  end_date: today,
})

function params() {
  return {
    location_id: locations.activeLocationId,
    ...Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== '' && value !== null)),
  }
}

async function fetchReports() {
  const { data } = await api.get('/reports', { params: params() })
  reports.value = data
}

async function generateDaily() {
  try {
    await api.post('/reports/generate-daily', {
      location_id: locations.activeLocationId,
      summary_date: filters.end_date || today,
    })
    await fetchReports()
    showToast('Daily summary generated')
  } catch (error) {
    showToast(extractError(error, 'Failed to generate report'), 'error')
  }
}

async function exportReport(type) {
  const extension = type === 'pdf' ? 'pdf' : 'xlsx'
  await downloadFile(`/reports/export/${type}`, `vehicle-report.${extension}`, params())
  showToast(`Report ${type === 'pdf' ? 'PDF' : 'Excel'} downloaded`)
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
  fetchReports()
  window.addEventListener('active-location-changed', fetchReports)
})

onUnmounted(() => {
  window.removeEventListener('active-location-changed', fetchReports)
})
</script>
