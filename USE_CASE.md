🧠 AKTOR SISTEM
👤 1. Admin BKPSDM
Full control
Validasi & approval
Kelola referensi
👤 2. Operator OPD
Mengajukan KGB / PMK
Melihat data pegawai
👤 3. Verifikator
Verifikasi data sebelum approval
👤 4. Sistem (Automation)
Hitung KGB
Sinkronisasi SIM-ASN
🎯 USE CASE UTAMA (CORE FLOW)
🔵 UC-01: Sinkronisasi Data Pegawai dari SIM-ASN

Aktor: Sistem
Trigger: manual / scheduler

Flow:
Hit endpoint SIM-ASN:
pegawai
riwayat golongan
riwayat jabatan
Cache / simpan sementara (optional)
Siapkan untuk digunakan KGB
Output:
Data pegawai siap dipakai

👉 Catatan penting:
JANGAN simpan sebagai master, hanya cache

🔵 UC-02: Generate Draft KGB (AUTO ENGINE)

Aktor: Operator / Sistem

Flow:
Pilih pegawai
Sistem ambil:
golongan terakhir
masa kerja terakhir
Sistem hitung:
masa kerja + 2 tahun
lookup ke ref_gaji_asn
Generate:
gaji lama
gaji baru
Simpan sebagai draft
Output:
riwayat_kgb (status = draft)
🔵 UC-03: Simpan Snapshot Data Pegawai

Aktor: Sistem

Flow:
Ambil data dari SIM-ASN
Simpan ke:
kgb_snapshots

👉 dilakukan saat:

draft dibuat / sebelum submit
🔵 UC-04: Ajukan KGB

Aktor: Operator

Flow:
Buka draft
Review
Klik “Ajukan”
Output:
status = diajukan
🔵 UC-05: Verifikasi KGB

Aktor: Verifikator

Flow:
Lihat data KGB
Cek:
masa kerja
golongan
gaji hasil
Aksi:
approve → lanjut
reject → kembali ke draft
🔵 UC-06: Approval KGB

Aktor: Admin

Flow:
Final check
Approve
Output:
status = disetujui

👉 Setelah ini:

data tidak boleh diubah
🔵 UC-07: Generate Dokumen SK KGB

Aktor: Sistem

Flow:
Ambil data dari:
riwayat_kgb
snapshot
Inject ke template Word/PDF
Simpan ke files
Update:
file_sk_id
🔵 UC-08: Download / Arsip SK

Aktor: Semua role sesuai akses

🔵 USE CASE PMK (KHUSUS)
🔶 UC-09: Input PMK

Aktor: Operator

Flow:
Input:
masa kerja lama
masa kerja baru
Upload SK PMK
Output:
riwayat_pmk
🔶 UC-10: Gunakan PMK untuk Perhitungan KGB

Aktor: Sistem

Logic:

Jika ada PMK:

masa kerja = hasil PMK
🔵 USE CASE REFERENSI
🧾 UC-11: Kelola Referensi Gaji

Aktor: Admin

Flow:
CRUD ref_gaji_asn
berdasarkan peraturan terbaru
🧾 UC-12: Kelola Peraturan

Aktor: Admin

🔵 USE CASE MONITORING
📊 UC-13: Monitoring KGB

Aktor: Admin / Operator

Filter:
OPD
Status
Tahun
📊 UC-14: Notifikasi KGB Jatuh Tempo

Aktor: Sistem

Flow:
cari pegawai:
tmt_kgb + 2 tahun
kirim notifikasi
🔵 USE CASE AUDIT
🔍 UC-15: Audit Trail

Aktor: Auditor / Admin

Bisa lihat:
siapa create
siapa approve
perubahan data
🔗 RELASI USE CASE (FLOW BESAR)
SIM-ASN
   ↓
[UC-01 Sync]
   ↓
[UC-02 Generate KGB]
   ↓
[UC-03 Snapshot]
   ↓
[UC-04 Ajukan]
   ↓
[UC-05 Verifikasi]
   ↓
[UC-06 Approval]
   ↓
[UC-07 Generate SK]
   ↓
[UC-08 Arsip]

Nanti mappingnya:

Use Case	        Service
Generate KGB	    KgbService
Snapshot	        SnapshotService
Hitung gaji	        KgbCalculationService
PMK	                PmkService
Generate SK	        DocumentService