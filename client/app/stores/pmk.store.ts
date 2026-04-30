import { defineStore } from 'pinia'
import type { PmkItem, PmkFormData } from '~/types/pmk'
import type { ApiResponse, PaginatedResponse } from '~/types/api'
import { pmkService } from '~/services/pmk.service'

interface PmkState {
  items: PmkItem[]
  currentItem: PmkItem | null
  loading: boolean
  loadingSubmit: boolean
  error: string | null
  pagination: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  } | null
}

export const usePmkStore = defineStore('pmk', {
  state: (): PmkState => ({
    items: [],
    currentItem: null,
    loading: false,
    loadingSubmit: false,
    error: null,
    pagination: null
  }),

  getters: {
    isLoading: (state) => state.loading,
    isSubmitting: (state) => state.loadingSubmit,
    hasError: (state) => !!state.error
  },

  actions: {
    async fetchList(params?: { page?: number; status?: string; search?: string }): Promise<void> {
      this.loading = true
      this.error = null
      try {
        const res = await pmkService.fetchPmkList(params) as PaginatedResponse<PmkItem>
        this.items = res.data
        this.pagination = res.meta
      } catch (e: any) {
        this.error = e.data?.message || 'Gagal mengambil data PMK'
      } finally {
        this.loading = false
      }
    },

    async fetchById(id: number): Promise<PmkItem | null> {
      this.loading = true
      this.error = null
      try {
        // This would call pmkService.getById(id) when the backend supports it
        // For now use the list endpoint and find the item
        const res = await pmkService.fetchPmkList() as PaginatedResponse<PmkItem>
        this.currentItem = res.data.find(p => p.id === id) || null
        return this.currentItem
      } catch (e: any) {
        this.error = e.data?.message || 'Gagal mengambil detail PMK'
        return null
      } finally {
        this.loading = false
      }
    },

    async create(data: PmkFormData): Promise<PmkItem> {
      this.loading = true
      this.error = null
      try {
        const res = await pmkService.createPmk(data) as ApiResponse<PmkItem>
        this.items.unshift(res.data)
        return res.data
      } catch (e: any) {
        this.error = e.data?.message || 'Gagal membuat PMK'
        throw e
      } finally {
        this.loading = false
      }
    },

    async uploadFile(file: File): Promise<{ id: number; url: string; filename: string }> {
      this.loading = true
      this.error = null
      try {
        const res = await pmkService.uploadFile(file) as ApiResponse<{ id: number; url: string; filename: string }>
        return res.data
      } catch (e: any) {
        this.error = e.data?.message || 'Gagal mengunggah file'
        throw e
      } finally {
        this.loading = false
      }
    },

    clearError(): void {
      this.error = null
    },

    clearCurrent(): void {
      this.currentItem = null
    }
  }
})