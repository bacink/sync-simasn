import type { ApiResponse, PaginatedResponse } from '~/types/api'
import type { PmkItem, PmkFormData } from '~/types/pmk'

const api = useApi()

export const pmkService = {
  async fetchPmkList(params?: { page?: number; status?: string; search?: string }): Promise<PaginatedResponse<PmkItem>> {
    return api.get<PaginatedResponse<PmkItem>>('/pmk', params)
  },

  async createPmk(data: PmkFormData): Promise<ApiResponse<PmkItem>> {
    return api.post<ApiResponse<PmkItem>>('/pmk', data)
  },

  async uploadFile(file: File): Promise<ApiResponse<{ id: number; url: string; filename: string }>> {
    const formData = new FormData()
    formData.append('file', file)
    return api.postFormData<ApiResponse<{ id: number; url: string; filename: string }>>('/upload', formData)
  }
}