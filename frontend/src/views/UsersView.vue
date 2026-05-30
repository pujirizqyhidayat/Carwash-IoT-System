<template>
  <MainLayout title="Users" eyebrow="Admin">
    <div class="space-y-6">
      <section class="card p-5">
        <div class="grid gap-4 md:grid-cols-6">
          <input v-model="form.full_name" class="input md:col-span-2" placeholder="Full name" />
          <input v-model="form.username" class="input" placeholder="Username" />
          <input v-model="form.email" class="input md:col-span-2" type="email" placeholder="Email" />
          <select v-model="form.role" class="input">
            <option value="owner">Owner</option>
            <option value="cashier">Cashier</option>
            <option value="admin">Admin</option>
          </select>
          <input v-model="form.password" class="input md:col-span-2" type="password" placeholder="Password" />
          <button class="btn-primary md:col-span-2" type="button" @click="createUser">
            <UserPlus :size="17" />
            Create User
          </button>
        </div>
      </section>

      <section class="card overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
          <h2 class="section-title">User Management</h2>
          <button class="btn-outline" type="button" @click="fetchUsers">
            <RefreshCw :size="17" />
            Refresh
          </button>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full min-w-[820px]">
            <thead class="table-head">
              <tr>
                <th class="px-4 py-3">Name</th>
                <th class="px-4 py-3">Username</th>
                <th class="px-4 py-3">Email</th>
                <th class="px-4 py-3">Role</th>
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

    <AppModal :open="editOpen" title="Edit User" description="Update account role and status." @close="editOpen = false">
      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label class="label">Full Name</label>
          <input v-model="editForm.full_name" class="input" />
        </div>
        <div>
          <label class="label">Role</label>
          <select v-model="editForm.role" class="input">
            <option value="owner">Owner</option>
            <option value="cashier">Cashier</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <div>
          <label class="label">Status</label>
          <select v-model="editForm.status" class="input">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div>
          <label class="label">New Password</label>
          <input v-model="newPassword" class="input" type="password" placeholder="Leave blank to keep current" />
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
const editOpen = ref(false)
const editId = ref(null)
const newPassword = ref('')
const toast = reactive({ message: '', type: 'success' })
const form = reactive({
  full_name: '',
  username: '',
  email: '',
  password: 'password123',
  role: 'cashier',
})
const editForm = reactive({
  full_name: '',
  role: 'cashier',
  status: 'active',
})

async function fetchUsers() {
  const { data } = await api.get('/users')
  users.value = data
}

async function createUser() {
  try {
    await api.post('/users', form)
    Object.assign(form, { full_name: '', username: '', email: '', password: 'password123', role: 'cashier' })
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
    status: user.status,
  })
  editOpen.value = true
}

async function saveEdit() {
  try {
    await api.put(`/users/${editId.value}`, editForm)
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

onMounted(fetchUsers)
</script>
