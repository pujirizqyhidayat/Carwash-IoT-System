import { defineStore } from 'pinia'
import { api } from '../services/api'

const storedLocationId = Number(localStorage.getItem('active_location_id') || 1)

export const useLocationStore = defineStore('location', {
  state: () => ({
    locations: [],
    activeLocationId: storedLocationId,
    loading: false,
  }),
  getters: {
    activeLocation: (state) => state.locations.find((location) => location.id === state.activeLocationId),
  },
  actions: {
    async fetchLocations() {
      this.loading = true
      try {
        const { data } = await api.get('/locations')
        this.locations = data
        if (!this.locations.some((location) => location.id === this.activeLocationId)) {
          this.setActiveLocation(this.locations[0]?.id || 1)
        }
      } finally {
        this.loading = false
      }
    },
    setActiveLocation(id) {
      this.activeLocationId = Number(id)
      localStorage.setItem('active_location_id', String(this.activeLocationId))
    },
  },
})
