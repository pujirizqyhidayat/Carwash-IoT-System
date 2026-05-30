<template>
  <MainLayout title="Sensors" eyebrow="Device management" :sensor-status="overallStatus">
    <div class="space-y-6">
      <section v-if="auth.canAccessAdmin" class="card p-5">
        <div class="grid gap-4 md:grid-cols-5">
          <div>
            <label class="label">Name</label>
            <input v-model="form.sensor_name" class="input" />
          </div>
          <div>
            <label class="label">Code</label>
            <input v-model="form.sensor_code" class="input" />
          </div>
          <div>
            <label class="label">Threshold</label>
            <input v-model="form.threshold_distance" class="input" type="number" min="0" />
          </div>
          <div class="flex items-end">
            <button class="btn-primary w-full justify-center" type="button" @click="createSensor">
              <Plus :size="17" />
              Add Sensor
            </button>
          </div>
        </div>
      </section>

      <section class="card overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
          <div>
            <h2 class="section-title">Sensor List</h2>
            <p class="muted">Active, inactive, and disconnected devices</p>
          </div>
          <button class="btn-outline" type="button" @click="fetchSensors">
            <RefreshCw :size="17" />
            Refresh
          </button>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full min-w-[820px]">
            <thead class="table-head">
              <tr>
                <th class="px-4 py-3">Sensor</th>
                <th class="px-4 py-3">Code</th>
                <th class="px-4 py-3">Position</th>
                <th class="px-4 py-3">Threshold</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Last Seen</th>
                <th v-if="auth.canAccessAdmin" class="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="sensor in sensors" :key="sensor.id">
                <td class="table-cell font-semibold">{{ sensor.sensor_name }}</td>
                <td class="table-cell font-mono">{{ sensor.sensor_code }}</td>
                <td class="table-cell">{{ sensor.sensor_position }}</td>
                <td class="table-cell">{{ sensor.threshold_distance || '-' }}</td>
                <td class="table-cell">
                  <span class="status-pill" :class="pillClass(sensor.status)">
                    <span class="h-2 w-2 rounded-full" :class="dotClass(sensor.status)"></span>
                    {{ sensor.status }}
                  </span>
                </td>
                <td class="table-cell">{{ sensor.last_seen_at || '-' }}</td>
                <td v-if="auth.canAccessAdmin" class="table-cell text-right">
                  <button class="btn-outline mr-2" type="button" @click="openEdit(sensor)">
                    <Pencil :size="16" />
                    Edit
                  </button>
                  <button class="btn-outline" type="button" @click="deactivate(sensor.id)">
                    <PowerOff :size="16" />
                    Deactivate
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>

    <AppModal :open="editOpen" title="Edit Sensor" description="Update sensor details and device status." @close="editOpen = false">
      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label class="label">Name</label>
          <input v-model="editForm.sensor_name" class="input" />
        </div>
        <div>
          <label class="label">Position</label>
          <select v-model="editForm.sensor_position" class="input">
            <option value="entry">entry</option>
            <option value="exit">exit</option>
          </select>
        </div>
        <div>
          <label class="label">Status</label>
          <select v-model="editForm.status" class="input">
            <option value="active">active</option>
            <option value="inactive">inactive</option>
            <option value="disconnected">disconnected</option>
          </select>
        </div>
        <div>
          <label class="label">Threshold</label>
          <input v-model="editForm.threshold_distance" class="input" type="number" />
        </div>
      </div>
      <div class="mt-5 flex justify-end gap-2">
        <button class="btn-outline" type="button" @click="editOpen = false">Cancel</button>
        <button class="btn-primary" type="button" @click="saveEdit">Save Changes</button>
      </div>
    </AppModal>
    <AppToast :message="toast.message" :type="toast.type" />
  </MainLayout>
</template>

<script setup>
import { Pencil, Plus, PowerOff, RefreshCw } from '@lucide/vue'
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue'
import AppModal from '../components/AppModal.vue'
import AppToast from '../components/AppToast.vue'
import MainLayout from '../layouts/mainlayout.vue'
import { api, extractError } from '../services/api'
import { useAuthStore } from '../stores/auth'
import { useLocationStore } from '../stores/location'

const auth = useAuthStore()
const locations = useLocationStore()
const sensors = ref([])
const editOpen = ref(false)
const editId = ref(null)
const toast = reactive({ message: '', type: 'success' })
const form = reactive({
  sensor_name: '',
  sensor_code: '',
  sensor_position: 'entry',
  threshold_distance: 40,
})
const editForm = reactive({
  sensor_name: '',
  sensor_position: 'entry',
  status: 'active',
  threshold_distance: 40,
})

const overallStatus = computed(() => sensors.value.find((item) => item.status === 'disconnected')?.status || sensors.value[0]?.status || 'disconnected')

function pillClass(status) {
  if (status === 'disconnected') return 'border-red-200 bg-red-50 text-red-700'
  if (status === 'inactive') return 'border-amber-200 bg-amber-50 text-amber-700'
  return 'border-emerald-200 bg-emerald-50 text-emerald-700'
}

function dotClass(status) {
  if (status === 'disconnected') return 'bg-red-500'
  if (status === 'inactive') return 'bg-amber-500'
  return 'bg-emerald-500'
}

async function fetchSensors() {
  const { data } = await api.get('/sensors', { params: { location_id: locations.activeLocationId } })
  sensors.value = data
}

async function createSensor() {
  try {
    await api.post('/sensors', {
      ...form,
      location_id: locations.activeLocationId,
      threshold_distance: Number(form.threshold_distance),
    })
    form.sensor_name = ''
    form.sensor_code = ''
    await fetchSensors()
    showToast('Sensor created')
  } catch (error) {
    showToast(extractError(error, 'Failed to create sensor'), 'error')
  }
}

function openEdit(sensor) {
  editId.value = sensor.id
  Object.assign(editForm, {
    sensor_name: sensor.sensor_name,
    sensor_position: sensor.sensor_position,
    status: sensor.status,
    threshold_distance: sensor.threshold_distance || 0,
  })
  editOpen.value = true
}

async function saveEdit() {
  try {
    await api.put(`/sensors/${editId.value}`, {
      ...editForm,
      threshold_distance: Number(editForm.threshold_distance),
    })
    editOpen.value = false
    await fetchSensors()
    showToast('Sensor updated')
  } catch (error) {
    showToast(extractError(error, 'Failed to update sensor'), 'error')
  }
}

async function deactivate(id) {
  await api.delete(`/sensors/${id}`)
  await fetchSensors()
  showToast('Sensor deactivated')
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
  fetchSensors()
  window.addEventListener('active-location-changed', fetchSensors)
})

onUnmounted(() => {
  window.removeEventListener('active-location-changed', fetchSensors)
})
</script>
