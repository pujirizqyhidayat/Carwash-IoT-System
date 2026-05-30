<template>
  <MainLayout title="Locations" eyebrow="Carwash sites">
    <div class="space-y-6">
      <section v-if="auth.canAccessAdmin" class="card p-5">
        <div class="grid gap-4 md:grid-cols-5">
          <input v-model="form.owner_id" class="input" type="number" min="1" placeholder="Owner ID" />
          <input v-model="form.location_name" class="input md:col-span-2" placeholder="Location name" />
          <input v-model="form.address" class="input md:col-span-2" placeholder="Address" />
          <input v-model="form.capacity" class="input" type="number" min="0" placeholder="Capacity" />
          <button class="btn-primary md:col-span-2" type="button" @click="createLocation">
            <Plus :size="17" />
            Create Location
          </button>
        </div>
      </section>

      <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <article v-for="location in locations" :key="location.id" class="card p-5">
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-lg font-bold text-slate-950">{{ location.location_name }}</p>
              <p class="mt-2 text-sm text-slate-500">{{ location.address }}</p>
            </div>
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-700">
              <MapPinned :size="19" />
            </div>
          </div>
          <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4">
            <span class="text-sm text-slate-500">Capacity</span>
            <span class="text-sm font-bold text-slate-900">{{ location.capacity || '-' }}</span>
          </div>
          <div v-if="auth.canAccessAdmin" class="mt-4 grid grid-cols-2 gap-2">
            <button class="btn-outline justify-center" type="button" @click="openEdit(location)">
              <Pencil :size="16" />
              Edit
            </button>
            <button class="btn-outline justify-center" type="button" @click="deleteLocation(location.id)">
              <Trash2 :size="16" />
              Delete
            </button>
          </div>
        </article>
      </section>
    </div>

    <AppModal :open="editOpen" title="Edit Location" description="Update carwash location details." @close="editOpen = false">
      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label class="label">Location Name</label>
          <input v-model="editForm.location_name" class="input" />
        </div>
        <div>
          <label class="label">Capacity</label>
          <input v-model="editForm.capacity" class="input" type="number" />
        </div>
        <div class="md:col-span-2">
          <label class="label">Address</label>
          <input v-model="editForm.address" class="input" />
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
import { MapPinned, Pencil, Plus, Trash2 } from '@lucide/vue'
import { onMounted, reactive, ref } from 'vue'
import AppModal from '../components/AppModal.vue'
import AppToast from '../components/AppToast.vue'
import MainLayout from '../layouts/mainlayout.vue'
import { api, extractError } from '../services/api'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const locations = ref([])
const editOpen = ref(false)
const editId = ref(null)
const toast = reactive({ message: '', type: 'success' })
const form = reactive({
  owner_id: 1,
  location_name: '',
  address: '',
  capacity: 20,
})
const editForm = reactive({
  location_name: '',
  address: '',
  capacity: 20,
})

async function fetchLocations() {
  const { data } = await api.get('/locations')
  locations.value = data
}

async function createLocation() {
  try {
    await api.post('/locations', {
      ...form,
      owner_id: Number(form.owner_id),
      capacity: Number(form.capacity || 0),
    })
    Object.assign(form, { owner_id: 1, location_name: '', address: '', capacity: 20 })
    await fetchLocations()
    showToast('Location created')
  } catch (error) {
    showToast(extractError(error, 'Failed to create location'), 'error')
  }
}

function openEdit(location) {
  editId.value = location.id
  Object.assign(editForm, {
    location_name: location.location_name,
    address: location.address,
    capacity: location.capacity || 0,
  })
  editOpen.value = true
}

async function saveEdit() {
  try {
    await api.put(`/locations/${editId.value}`, {
      ...editForm,
      capacity: Number(editForm.capacity || 0),
    })
    editOpen.value = false
    await fetchLocations()
    showToast('Location updated')
  } catch (error) {
    showToast(extractError(error, 'Failed to update location'), 'error')
  }
}

async function deleteLocation(id) {
  await api.delete(`/locations/${id}`)
  await fetchLocations()
  showToast('Location deleted')
}

function showToast(message, type = 'success') {
  toast.message = message
  toast.type = type
  window.setTimeout(() => {
    toast.message = ''
  }, 2600)
}

onMounted(fetchLocations)
</script>
