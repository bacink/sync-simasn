import { KgbStatus } from './kgb'

export interface PmkItem {
  id: number
  nip: string
  nama: string
  masa_kerja_lama_tahun: number
  masa_kerja_lama_bulan: number
  masa_kerja_baru_tahun: number
  masa_kerja_baru_bulan: number
  dasar_pmk: string
  nomor_sk: string
  tanggal_sk: string
  status: KgbStatus
  created_at: string
  updated_at?: string
  file_sk_id?: number
  file_sk_url?: string
  catatan?: string
}

export interface PmkFormData {
  nip: string
  masa_kerja_baru_tahun: number
  masa_kerja_baru_bulan: number
  dasar_pmk: string
  file_sk_id?: number
}