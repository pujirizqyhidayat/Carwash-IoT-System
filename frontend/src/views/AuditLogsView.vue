<template>
  <MainLayout title="Audit Log" eyebrow="Security">
    <div class="space-y-6">
      <section class="card p-5">
        <div class="grid gap-4 md:grid-cols-5">
          <select v-model="filters.module" class="input">
            <option value="">All modules</option>
            <option v-for="module in moduleOptions" :key="module" :value="module">{{ module }}</option>
          </select>
          <select v-model="filters.user_id" class="input">
            <option value="">All users</option>
            <option value="system">system</option>
            <option v-for="user in userOptions" :key="user.id" :value="user.id">
              {{ formatUser(user) }}
            </option>
          </select>
          <input v-model="filters.start_date" class="input" type="date" :max="filters.end_date || undefined" />
          <input v-model="filters.end_date" class="input" type="date" :min="filters.start_date || undefined" />
          <div class="flex gap-2">
            <button class="btn-primary" type="button" @click="fetchLogs">
              <Search :size="17" />
              Filter
            </button>
            <button class="btn-outline" type="button" @click="exportLogs">
              <FileDown :size="17" />
              Export
            </button>
          </div>
        </div>
        <p v-if="filterError" class="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700">
          {{ filterError }}
        </p>
      </section>

      <section class="card overflow-hidden">
        <div class="border-b border-slate-200 px-5 py-4">
          <h2 class="section-title">System Activity</h2>
          <p class="muted">Login, CRUD, export, and failed access events</p>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full min-w-[240]">
            <thead class="table-head">
              <tr>
                <th class="px-4 py-3">Time</th>
                <th class="px-4 py-3">User</th>
                <th class="px-4 py-3">Action</th>
                <th class="px-4 py-3">Module</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Description</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="logs.length === 0">
                <td class="table-cell text-center" colspan="6">No data available</td>
              </tr>
              <tr v-for="log in logs" :key="log.id">
                <td class="table-cell">{{ formatJakartaTime(log.created_at) }}</td>
                <td class="table-cell">{{ formatAuditUser(log) }}</td>
                <td class="table-cell">{{ log.action }}</td>
                <td class="table-cell">{{ log.module }}</td>
                <td class="table-cell">
                  <span class="status-pill" :class="statusClass(log.status)">{{ log.status }}</span>
                </td>
                <td class="table-cell">{{ log.description }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </MainLayout>
</template>

<script setup>
import { FileDown, Search } from '@lucide/vue'
import { onMounted, reactive, ref } from 'vue'
import MainLayout from '../layouts/mainlayout.vue'
import { api, downloadFile, extractError } from '../services/api'

const logs = ref([])
const filterError = ref('')
const userOptions = [
  { id: 1, username: 'admin', role: 'admin' },
  { id: 2, username: 'owner', role: 'owner' },
  { id: 3, username: 'cashier', role: 'cashier' },
]
const moduleOptions = ['auth', 'authorization', 'audit_log', 'location', 'report', 'sensor', 'user', 'vehicle_entry']
const filters = reactive({
  module: '',
  user_id: '',
  start_date: '',
  end_date: '',
})

function params() {
  return Object.fromEntries(Object.entries(filters).filter(([, value]) => value))
}

function validatePeriod() {
  filterError.value = ''
  if (filters.start_date && filters.end_date && filters.end_date < filters.start_date) {
    filterError.value = 'End date cannot be before start date'
    return false
  }
  return true
}

function statusClass(status) {
  if (status === 'failed') return 'border-red-200 bg-red-50 text-red-700'
  if (status === 'warning') return 'border-amber-200 bg-amber-50 text-amber-700'
  return 'border-emerald-200 bg-emerald-50 text-emerald-700'
}

function formatUser(user) {
  if (!user) return 'system'
  return user.role ? user.username + ' (' + user.role + ')' : user.username
}


function formatAuditUser(log) {
  if (!log?.user_id) return 'system'

  const username = log.actor_username || log.user?.username || 'unknown'
  const role = log.actor_role || log.user?.role
  return role ? username + ' (' + role + ')' : username
}
function formatJakartaTime(value) {
  if (!value) return '-'

  const parts = new Intl.DateTimeFormat('en-CA', {
    timeZone: 'Asia/Jakarta',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
  }).formatToParts(new Date(value))

  const byType = Object.fromEntries(parts.map((part) => [part.type, part.value]))
  return byType.year + '-' + byType.month + '-' + byType.day + ' | ' + byType.hour + ':' + byType.minute
}

async function fetchLogs() {
  if (!validatePeriod()) return

  try {
    const { data } = await api.get('/audit-logs', { params: params() })
    logs.value = data.data || []
  } catch (error) {
    filterError.value = extractError(error, 'Failed to load audit logs')
  }
}

async function exportLogs() {
  if (!validatePeriod()) return

  try {
    await downloadFile('/audit-logs/export', 'audit-logs.xlsx', params())
  } catch (error) {
    filterError.value = extractError(error, 'Failed to export audit logs')
  }
}

onMounted(fetchLogs)
</script>
















