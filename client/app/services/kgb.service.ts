import type { ApiResponse, PaginatedResponse } from "~/types/api";
import type { KgbItem, KgbFormData, KgbCalculation, KgbSnapshot } from "~/types/kgb";

const api = useApi();

export const kgbService = {
  async fetchKgbList(params?: {
    page?: number;
    status?: string;
    opd?: string;
    tahun?: string;
  }): Promise<PaginatedResponse<KgbItem>> {
    return api.get<PaginatedResponse<KgbItem>>("/kgb", params);
  },

  async fetchKgbById(id: number): Promise<ApiResponse<KgbItem>> {
    return api.get<ApiResponse<KgbItem>>(`/kgb/${id}`);
  },

  async generateDraft(
    pegawaiId: number,
    masaKerjaTahun: number,
    masaKerjaBulan: number,
    jenisKgb: "reguler" | "penyesuaian",
  ): Promise<ApiResponse<KgbItem & { calculation: KgbCalculation; snapshot: KgbSnapshot }>> {
    return api.post<ApiResponse<KgbItem & { calculation: KgbCalculation; snapshot: KgbSnapshot }>>(
      "/kgb/generate",
      {
        pegawai_id: pegawaiId,
        masa_kerja_tahun: masaKerjaTahun,
        masa_kerja_bulan: masaKerjaBulan,
        jenis_kgb: jenisKgb,
      },
    );
  },

  async submitKgb(id: number): Promise<ApiResponse<KgbItem>> {
    return api.post<ApiResponse<KgbItem>>(`/kgb/${id}/submit`);
  },

  async verifyKgb(id: number, catatan?: string): Promise<ApiResponse<KgbItem>> {
    return api.post<ApiResponse<KgbItem>>(`/kgb/${id}/verify`, catatan ? { catatan } : undefined);
  },

  async approveKgb(id: number): Promise<ApiResponse<KgbItem>> {
    return api.post<ApiResponse<KgbItem>>(`/kgb/${id}/approve`);
  },

  async rejectKgb(id: number, catatan: string): Promise<ApiResponse<KgbItem>> {
    return api.post<ApiResponse<KgbItem>>(`/kgb/${id}/reject`, { catatan });
  },
};
