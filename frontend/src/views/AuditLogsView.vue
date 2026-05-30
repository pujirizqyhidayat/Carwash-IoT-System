<template>
  <MainLayout title="Audit Log" eyebrow="Security">
    <div class="space-y-6">
      <section class="card p-5">
        <div class="grid gap-4 md:grid-cols-5">
          <input v-model="filters.module" class="input" placeholder="Module" />
          <input v-model="filters.action" class="input" placeholder="Action" />
          <input v-model="filters.start_date" class="input" type="date" />
          <input v-model="filters.end_date" class="input" type="date" />
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
      </section>

      <section class="card overflow-hidden">
        <div class="border-b border-slate-200 px-5 py-4">
          <h2 class="section-title">System Activity</h2>
          <p class="muted">Login, CRUD, export, and failed access events</p>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full min-w-[960px]">
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
                <td class="table-cell">{{ log.created_at }}</td>
                <td class="table-cell">{{ log.user?.username || 'system' }}</td>
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
import { api, downloadFile } from '../services/api'

const logs = ref([])
const filters = reactive({
  module: '',
  action: '',
  start_date: '',
  end_date: '',
})

function params() {
  return Object.fromEntries(Object.entries(filters).filter(([, value]) => value))
}

function statusClass(status) {
  if (status === 'failed') return 'border-red-200 bg-red-50 text-red-700'
  if (status === 'warning') return 'border-amber-200 bg-amber-50 text-amber-700'
  return 'border-emerald-200 bg-emerald-50 text-emerald-700'
}

async function fetchLogs() {
  const { data } = await api.get('/audit-logs', { params: params() })
  logs.value = data.data || []
}

async function exportLogs() {
  await downloadFile('/audit-logs/export', 'audit-logs.xlsx', params())
}

onMounted(fetchLogs)
</script>
