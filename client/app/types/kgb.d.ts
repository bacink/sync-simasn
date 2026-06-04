export enum KgbStatus {
  Draft = "draft",
  Diajukan = "diajukan",
  Diverifikasi = "diverifikasi",
  Disetujui = "disetujui",
  Ditolak = "ditolak",
}

export enum JenisKgb {
  Reguler = "reguler",
  Penyesuaian = "penyesuaian",
}

export interface KgbItem {
  id: number;
  nip: string;
  nama: string;
  golongan: string;
  masa_kerja_tahun: number;
  masa_kerja_bulan: number;
  gaji_lama: number;
  gaji_baru: number;
  tmt_kgb: string;
  nomor_sk: string;
  tanggal_sk: string;
  jenis_kgb: "reguler" | "penyesuaian";
  status: KgbStatus;
  created_at: string;
  updated_at?: string;
  file_sk_id?: number;
  file_sk_url?: string;
  catatan?: string;
}

export interface KgbFormData {
  pegawai_id: number;
  masa_kerja_tahun: number;
  masa_kerja_bulan: number;
  jenis_kgb: "reguler" | "penyesuaian";
}

export interface KgbCalculation {
  id: number;
  riwayat_kgb_id: number;
  golongan: string;
  masa_kerja_tahun: number;
  masa_kerja_bulan: number;
  gaji_ref_id: number;
  gaji_ref: number;
  gaji_hasil: number;
  formula: string;
  created_at: string;
}

export interface KgbSnapshot {
  id: number;
  riwayat_kgb_id: number;
  jabatan_nama: string;
  unit_kerja: string;
  nama_skpd: string;
  golongan: string;
  masa_kerja_tahun: number;
  masa_kerja_bulan: number;
  data_json: Record<string, any>;
  created_at: string;
}
