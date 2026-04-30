import type { ApiResponse } from '~/types/api'

export interface RefGajiItem {
  id: number
  jenis_asn: string
  golongan: string
  sub_golongan: string | null
  masa_kerja: number
  gaji: number
  peraturan: {
    id: number
    jenis: string
    nomor: string
    tahun: number
    nama: string
    effective_date: string
  }
}

export interface RefGajiGolongan {
  id: number
  jenis_asn: string
  golongan: string
  sub_golongan: string | null
  pangkat: string
  urutan: number
}

export interface RefGajiPeraturan {
  id: number
  jenis: string
  nomor: string
  tahun: number
  nama: string
  effective_date: string
}

export interface RefGajiListData {
  items: RefGajiItem[]
  golongan: RefGajiGolongan[]
  peraturan: RefGajiPeraturan[]
}

const api = useApi()

export const refGajiService = {
  async fetchList(jenisAsn: 'pns' | 'pppk'): Promise<ApiResponse<RefGajiListData>> {
    return api.get<ApiResponse<RefGajiListData>>('/ref-gaji', { jenis_asn: jenisAsn })
  }
}