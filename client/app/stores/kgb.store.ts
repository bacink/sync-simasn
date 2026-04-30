import { defineStore } from 'pinia'
import type { KgbItem, KgbFormData, KgbCalculation, KgbSnapshot } from '~/types/kgb'
import type { ApiResponse, PaginatedResponse } from '~/types/api'
import { kgbService } from '~/services/kgb.service'

interface KgbState {
  items: KgbItem[]
  currentItem: KgbItem | null
  currentCalculation: KgbCalculation | null
  currentSnapshot: KgbSnapshot | null
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

export const useKgbStore = defineStore('kgb', {
  state: (): KgbState => ({
    items: [],
    currentItem: null,
    currentCalculation: null,
    currentSnapshot: null,
    loading: false,
    loadingSubmit: false,
    error: null,
    pagination: null
  }),

  getters: {
    isLoading: (state) => state.loading,
    isSubmitting: (state) => state.loadingSubmit,
    hasError: (state) => !!state.error,
    pendingItems: (state) => state.items.filter(i => i.status === 'draft'),
    submittedItems: (state) => state.items.filter(i => i.status === 'diajukan'),
    verifiedItems: (state) => state.items.filter(i => i.status === 'diverifikasi'),
    approvedItems: (state) => state.items.filter(i => i.status === 'disetujui'),
    rejectedItems: (state) => state.items.filter(i => i.status === 'ditolak')
  },

  actions: {
    async fetchList(params?: { page?: number; status?: string; opd?: string; tahun?: string }): Promise<void> {
      this.loading = true
      this.error = null
      try {
        const res = await kgbService.fetchKgbList(params) as PaginatedResponse<KgbItem>
        this.items = res.data
        this.pagination = res.meta
      } catch (e: any) {
        this.error = e.data?.message || 'Gagal mengambil data KGB'
      } finally {
        this.loading = false
      }
    },

    async fetchById(id: number): Promise<KgbItem | null> {
      this.loading = true
      this.error = null
      try {
        const res = await kgbService.fetchKgbById(id) as ApiResponse<KgbItem>
        this.currentItem = res.data
        return res.data
      } catch (e: any) {
        this.error = e.data?.message || 'Gagal mengambil detail KGB'
        return null
      } finally {
        this.loading = false
      }
    },

    async generateDraft(pegawaiId: number, masaKerjaTahun: number, masaKerjaBulan: number, jenisKgb: 'reguler' | 'penyesuaian'): Promise<void> {
      this.loading = true
      this.error = null
      try {
        const res = await kgbService.generateDraft(pegawaiId, masaKerjaTahun, masaKerjaBulan, jenisKgb) as ApiResponse<KgbItem & { calculation: KgbCalculation; snapshot: KgbSnapshot }>
        this.currentItem = res.data
        this.currentCalculation = res.data.calculation
        this.currentSnapshot = res.data.snapshot
      } catch (e: any) {
        this.error = e.data?.message || 'Gagal menghasilkan draft KGB'
        throw e
      } finally {
        this.loading = false
      }
    },

    async submit(id: number): Promise<void> {
      this.loadingSubmit = true
      this.error = null
      try {
        await kgbService.submitKgb(id)
        this._updateItemStatus(id, 'diajukan')
      } catch (e: any) {
        this.error = e.data?.message || 'Gagal mengajukan KGB'
        throw e
      } finally {
        this.loadingSubmit = false
      }
    },

    async verify(id: number, catatan?: string): Promise<void> {
      this.loadingSubmit = true
      this.error = null
      try {
        await kgbService.verifyKgb(id, catatan)
        this._updateItemStatus(id, 'diverifikasi')
      } catch (e: any) {
        this.error = e.data?.message || 'Gagal memverifikasi KGB'
        throw e
      } finally {
        this.loadingSubmit = false
      }
    },

    async approve(id: number): Promise<void> {
      this.loadingSubmit = true
      this.error = null
      try {
        await kgbService.approveKgb(id)
        this._updateItemStatus(id, 'disetujui')
      } catch (e: any) {
        this.error = e.data?.message || 'Gagal menyetujui KGB'
        throw e
      } finally {
        this.loadingSubmit = false
      }
    },

    async reject(id: number, catatan: string): Promise<void> {
      this.loadingSubmit = true
      this.error = null
      try {
        await kgbService.rejectKgb(id, catatan)
        this._updateItemStatus(id, 'ditolak')
      } catch (e: any) {
        this.error = e.data?.message || 'Gagal menolak KGB'
        throw e
      } finally {
        this.loadingSubmit = false
      }
    },

    _updateItemStatus(id: number, status: string) {
      if (this.currentItem?.id === id) {
        this.currentItem.status = status as any
      }
      const idx = this.items.findIndex(i => i.id === id)
      if (idx !== -1) {
        this.items[idx].status = status as any
      }
    },

    clearError(): void {
      this.error = null
    },

    clearCurrent(): void {
      this.currentItem = null
      this.currentCalculation = null
      this.currentSnapshot = null
    }
  }
})