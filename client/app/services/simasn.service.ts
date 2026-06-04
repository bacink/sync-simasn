import type { ApiResponse, PaginatedResponse } from "~/types/api";
import type { Pegawai, Golongan } from "~/types/simasn";

const api = useApi();

export const simasnService = {
  async fetchPegawaiList(params?: {
    page?: number;
    search?: string;
  }): Promise<PaginatedResponse<Pegawai>> {
    return api.get<PaginatedResponse<Pegawai>>("/sim-asn/pegawai", params);
  },

  async fetchPegawaiById(id: number): Promise<ApiResponse<Pegawai>> {
    return api.get<ApiResponse<Pegawai>>(`/sim-asn/pegawai/${id}`);
  },

  async fetchGolonganList(): Promise<ApiResponse<Golongan[]>> {
    return api.get<ApiResponse<Golongan[]>>("/sim-asn/golongan");
  },
};
