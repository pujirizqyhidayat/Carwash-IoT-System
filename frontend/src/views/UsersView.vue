<template>
  <MainLayout title="Users" eyebrow="Admin">
    <div class="space-y-6">
      <section class="card overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
          <div>
            <h2 class="section-title">User Management</h2>
            <p class="muted">Create, edit, and deactivate dashboard accounts</p>
          </div>
          <div class="flex items-center gap-2">
            <button class="btn-primary" type="button" @click="openAdd">
              <UserPlus :size="17" />
              Add User
            </button>
            <button class="btn-outline" type="button" @click="fetchUsers">
              <RefreshCw :size="17" />
              Refresh
            </button>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full min-w-[920px]">
            <thead class="table-head">
              <tr>
                <th class="px-4 py-3">Name</th>
                <th class="px-4 py-3">Username</th>
                <th class="px-4 py-3">Email</th>
                <th class="px-4 py-3">Role</th>
                <th class="px-4 py-3">Location</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in users" :key="user.id">
                <td class="table-cell font-semibold">{{ user.full_name }}</td>
                <td class="table-cell">{{ user.username }}</td>
                <td class="table-cell">{{ user.email }}</td>
                <td class="table-cell capitalize">{{ user.role }}</td>
                <td class="table-cell">{{ user.assigned_location_name || 'All location' }}</td>
                <td class="table-cell capitalize">{{ user.status }}</td>
                <td class="table-cell text-right">
                  <button class="btn-outline mr-2" type="button" @click="openEdit(user)">
                    <Pencil :size="16" />
                    Edit
                  </button>
                  <button class="btn-outline" type="button" @click="deactivate(user.id)">
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

    <AppModal :open="addOpen" title="Add User" description="Create a new dashboard account." @close="addOpen = false">
      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label class="label">Full Name</label>
          <input v-model="form.full_name" class="input" />
        </div>
        <div>
          <label class="label">Username</label>
          <input v-model="form.username" class="input" />
        </div>
        <div>
          <label class="label">Email</label>
          <input v-model="form.email" class="input" type="email" />
        </div>
        <div>
          <label class="label">Role</label>
          <select v-model="form.role" class="input" @change="syncAddLocation">
            <option value="owner">Owner</option>
            <option value="cashier">Cashier</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <div>
          <label class="label">Location</label>
          <select v-model="form.location_id" class="input" :disabled="form.role !== 'cashier'">
            <option value="">All location</option>
            <option v-for="location in locations" :key="location.id" :value="location.id">
              {{ location.location_name }}
            </option>
          </select>
        </div>
        <div>
          <label class="label">Password</label>
          <input v-model="form.password" class="input" type="password" autocomplete="new-password" />
        </div>
      </div>
      <div class="mt-5 flex justify-end gap-2">
        <button class="btn-outline" type="button" @click="addOpen = false">Cancel</button>
        <button class="btn-primary" type="button" @click="createUser">
          <UserPlus :size="17" />
          Create User
        </button>
      </div>
    </AppModal>

    <AppModal :open="editOpen" title="Edit User" description="Update account role, location, and status." @close="editOpen = false">
      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label class="label">Full Name</label>
          <input v-model="editForm.full_name" class="input" />
        </div>
        <div>
          <label class="label">Role</label>
          <select v-model="editForm.role" class="input" @change="syncEditLocation">
            <option value="owner">Owner</option>
            <option value="cashier">Cashier</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <div>
          <label class="label">Location</label>
          <select v-model="editForm.location_id" class="input" :disabled="editForm.role !== 'cashier'">
            <option value="">All location</option>
            <option v-for="location in locations" :key="location.id" :value="location.id">
              {{ location.location_name }}
            </option>
          </select>
        </div>
        <div>
          <label class="label">Status</label>
          <select v-model="editForm.status" class="input">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div class="md:col-span-2">
          <label class="label">New Password</label>
          <input v-model="newPassword" class="input" type="password" autocomplete="new-password" placeholder="Leave blank to keep current" />
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
import { Pencil, PowerOff, RefreshCw, UserPlus } from '@lucide/vue'
import { onMounted, reactive, ref } from 'vue'
import AppModal from '../components/AppModal.vue'
import AppToast from '../components/AppToast.vue'
import MainLayout from '../layouts/mainlayout.vue'
import { api, extractError } from '../services/api'

const users = ref([])
const locations = ref([])
const addOpen = ref(false)
const editOpen = ref(false)
const editId = ref(null)
const newPassword = ref('')
const toast = reactive({ message: '', type: 'success' })
const form = reactive({
  full_name: '',
  username: '',
  email: '',
  password: '',
  role: 'cashier',
  location_id: '',
})
const editForm = reactive({
  full_name: '',
  role: 'cashier',
  location_id: '',
  status: 'active',
})

async function fetchUsers() {
  const { data } = await api.get('/users')
  users.value = data
}

async function fetchLocations() {
  const { data } = await api.get('/locations')
  locations.value = data
  syncAddLocation()
}

function firstLocationId() {
  return locations.value[0]?.id ? String(locations.value[0].id) : ''
}

function normalizeUserPayload(payload) {
  return {
    ...payload,
    location_id: payload.role === 'cashier' ? payload.location_id || null : null,
  }
}

function syncAddLocation() {
  if (form.role !== 'cashier') {
    form.location_id = ''
    return
  }

  if (!form.location_id) {
    form.location_id = firstLocationId()
  }
}

function syncEditLocation() {
  if (editForm.role !== 'cashier') {
    editForm.location_id = ''
    return
  }

  if (!editForm.location_id) {
    editForm.location_id = firstLocationId()
  }
}

function openAdd() {
  syncAddLocation()
  addOpen.value = true
}

async function createUser() {
  try {
    await api.post('/users', normalizeUserPayload(form))
    Object.assign(form, { full_name: '', username: '', email: '', password: '', role: 'cashier', location_id: firstLocationId() })
    addOpen.value = false
    await fetchUsers()
    showToast('User created')
  } catch (error) {
    showToast(extractError(error, 'Failed to create user'), 'error')
  }
}

function openEdit(user) {
  editId.value = user.id
  newPassword.value = ''
  Object.assign(editForm, {
    full_name: user.full_name,
    role: user.role,
    location_id: user.location_id ? String(user.location_id) : '',
    status: user.status,
  })
  syncEditLocation()
  editOpen.value = true
}

async function saveEdit() {
  try {
    await api.put(`/users/${editId.value}`, normalizeUserPayload(editForm))
    if (newPassword.value) {
      await api.post(`/users/${editId.value}/reset-password`, { new_password: newPassword.value })
    }
    editOpen.value = false
    await fetchUsers()
    showToast('User updated')
  } catch (error) {
    showToast(extractError(error, 'Failed to update user'), 'error')
  }
}

async function deactivate(id) {
  await api.delete(`/users/${id}`)
  await fetchUsers()
  showToast('User deactivated')
}

function showToast(message, type = 'success') {
  toast.message = message
  toast.type = type
  window.setTimeout(() => {
    toast.message = ''
  }, 2600)
}

onMounted(async () => {
  await fetchLocations()
  await fetchUsers()
})
</script>
