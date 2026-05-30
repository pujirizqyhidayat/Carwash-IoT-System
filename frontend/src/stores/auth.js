import { defineStore } from 'pinia'
import { api, setAuthToken } from '../services/api'

const storedToken = localStorage.getItem('auth_token')
const storedUser = localStorage.getItem('auth_user')

if (storedToken) {
  setAuthToken(storedToken)
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: storedToken,
    user: storedUser ? JSON.parse(storedUser) : null,
    loading: false,
  }),
  getters: {
    isAuthenticated: (state) => Boolean(state.token),
    role: (state) => state.user?.role,
    canAccessAdmin: (state) => state.user?.role === 'admin',
    canExport: (state) => ['owner', 'admin'].includes(state.user?.role),
  },
  actions: {
    async login(credentials) {
      this.loading = true
      try {
        const { data } = await api.post('/auth/login', credentials)
        this.token = data.token
        this.user = data.user
        localStorage.setItem('auth_token', data.token)
        localStorage.setItem('auth_user', JSON.stringify(data.user))
        setAuthToken(data.token)
        return data
      } finally {
        this.loading = false
      }
    },
    async fetchMe() {
      if (!this.token) return null
      const { data } = await api.get('/auth/me')
      this.user = data
      localStorage.setItem('auth_user', JSON.stringify(data))
      return data
    },
    async logout() {
      try {
        if (this.token) {
          await api.post('/auth/logout')
        }
      } finally {
        this.token = null
        this.user = null
        localStorage.removeItem('auth_token')
        localStorage.removeItem('auth_user')
        setAuthToken(null)
      }
    },
  },
})
