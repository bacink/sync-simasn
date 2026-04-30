ERD – KGB & PMK SYSTEM (FINAL DESIGN)

1. DOMAIN: MASTER / REFERENSI
a. ref_peraturan
id (PK)
jenis (PP, Perpres, dll)
nomor
tahun
nama

b. ref_gaji_asn
id (PK)
peraturan_id (FK → ref_peraturan.id)

golongan (string)      -- contoh: III/a
masa_kerja (integer)   -- 0 - 32 tahun
gaji (decimal)

constraint (golongan, masa_kerja, peraturan_id)

2. DOMAIN: KGB (CORE)
a. riwayat_kgb (MAIN TABLE)

id (PK)

pegawai_id             -- dari SIM-ASN (external ID)
nip
nama

golongan
masa_kerja_tahun
masa_kerja_bulan

gaji_lama
gaji_baru

tmt_kgb
nomor_sk
tanggal_sk

jenis_kgb (enum: reguler, penyesuaian)

status (draft, diajukan, diverifikasi, disetujui, ditolak)

file_sk_id (FK → files.id)

deleted_at (soft delete)

b. kgb_snapshots
id (PK)
riwayat_kgb_id (FK)

jabatan_nama
unit_kerja
nama_skpd

golongan
masa_kerja_tahun
masa_kerja_bulan

data_json (json) -- full backup dari SIM-ASN (optional tapi recommended)
relasi riwayat_kgb 1 --- 1 kgb_snapshots

c. kgb_calculations
id (PK)
riwayat_kgb_id (FK)

golongan
masa_kerja

gaji_ref_id (FK → ref_gaji_asn.id)
gaji_hasil

formula (string/null)
created_at

3. DOMAIN: PMK (Peninjauan Masa Kerja)
a. riwayat_pmk
id (PK)

pegawai_id
nip
nama

masa_kerja_lama_tahun
masa_kerja_lama_bulan

masa_kerja_baru_tahun
masa_kerja_baru_bulan

dasar_pmk
nomor_sk
tanggal_sk

file_id (FK → files.id)

relasi pmk->kgb
riwayat_kgb
- pmk_id (nullable FK → riwayat_pmk.id)

4. DOMAIN: FILE & DOKUMEN
a. files

id (PK)
path
name
original_name
mime
size
created_at

5. RELASI UTAMA (GAMBAR LOGIKA)
ref_peraturan
    ↓
ref_gaji_asn
    ↓
kgb_calculations
    ↓
riwayat_kgb ---- kgb_snapshots
     ↓
     → files (SK)

riwayat_pmk → riwayat_kgb

Tambahkan index:
riwayat_kgb:
- pegawai_id
- nip
- tmt_kgb
- status

ref_gaji_asn:
- golongan
- masa_kerja

feature :
kgb_approvals
- riwayat_kgb_id
- user_id
- role (verifikator, kepala, dll)
- status
- catatan

audit_logs
- table_name
- record_id
- action
- old_data
- new_data
- user_id
- created_at

