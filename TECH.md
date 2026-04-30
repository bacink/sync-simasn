🧠 HIGH-LEVEL ARCHITECTURE
Nuxt 3 (UI)
   ↓
API Layer (Laravel 13 + Sanctum)
   ↓
Service Layer (Business Logic)
   ↓
Repository / Model (Eloquent)
   ↓
Database
   ↓
External: SIM-ASN API


🎯 FRONTEND ARCHITECTURE (NUXT 3)
🔵 Stack
Nuxt 3
TailwindCSS
Pinia
📦 Struktur Folder (WAJIB RAPI)
frontend/
├── pages/
│   ├── kgb/
│   ├── pmk/
│   └── monitoring/
│
├── components/
│   ├── kgb/
│   ├── pmk/
│   └── shared/
│
├── stores/
│   ├── kgb.store.ts
│   ├── pmk.store.ts
│   └── auth.store.ts
│
├── composables/
│   ├── useKgb.ts
│   ├── usePmk.ts
│   └── useApi.ts
│
├── services/
│   ├── kgb.service.ts
│   ├── pmk.service.ts
│   └── simasn.service.ts
│
├── types/
│   └── kgb.d.ts
🧠 Prinsip Penting
❌ Jangan:
API call langsung di component
Logic di template
✅ Harus:
Component → Store → Service → API
🔥 Contoh Flow KGB (Frontend)
const store = useKgbStore()

await store.generateDraft(pegawaiId)
await store.submitKgb(id)
🧠 Pinia Store (Pattern Bersih)
export const useKgbStore = defineStore('kgb', {
  state: () => ({
    items: [],
    loading: false,
  }),

  actions: {
    async generateDraft(pegawaiId: number) {
      this.loading = true
      const res = await kgbService.generateDraft(pegawaiId)
      this.loading = false
      return res
    },
  },
})
🎯 BACKEND ARCHITECTURE (LARAVEL 13)

Sekarang yang paling krusial.

📦 Struktur Folder (WAJIB)
app/
├── Http/
│   ├── Controllers/
│
├── Services/
│   ├── Kgb/
│   │   ├── KgbService.php
│   │   ├── KgbCalculationService.php
│   │   ├── KgbSnapshotService.php
│   │
│   ├── Pmk/
│   │   ├── PmkService.php
│   │
│   ├── SimAsn/
│   │   ├── SimAsnService.php
│
├── DTOs/
│   ├── KgbData.php
│
├── Actions/ (optional tapi bagus)
│
├── Models/
🧠 Prinsip WAJIB
❌ Controller:
Jangan ada logic bisnis
Maksimal 20–30 baris
✅ Semua logic:
di Service
🔥 SERVICE LAYER DESIGN
🔵 1. KgbService (Orchestrator)
class KgbService
{
    public function generateDraft(int $pegawaiId)
    {
        $pegawai = $this->simAsn->getPegawai($pegawaiId);

        $golongan = $this->simAsn->getLastGolongan($pegawaiId);

        $masaKerja = $this->calculateMasaKerja($golongan);

        $gaji = $this->calculation->calculate(
            $golongan,
            $masaKerja
        );

        return DB::transaction(function () use ($pegawai, $gaji) {
            $kgb = Kgb::create([...]);

            $this->snapshot->store($kgb, $pegawai);

            return $kgb;
        });
    }
}
🔵 2. KgbCalculationService (PURE LOGIC)
class KgbCalculationService
{
    public function calculate($golongan, $masaKerja)
    {
        return RefGajiAsn::where([
            'golongan' => $golongan,
            'masa_kerja' => $masaKerja
        ])->firstOrFail();
    }
}

👉 ini harus:

stateless
mudah di-test
🔵 3. SimAsnService (Integration Layer)
class SimAsnService
{
    public function getPegawai($id)
    {
        return Http::get("/pegawai/$id")->json();
    }
}

👉 jangan campur dengan business logic

🔵 4. Snapshot Service
class KgbSnapshotService
{
    public function store($kgb, $pegawai)
    {
        KgbSnapshot::create([
            'riwayat_kgb_id' => $kgb->id,
            'data_json' => json_encode($pegawai),
        ]);
    }
}
🔐 AUTHENTICATION (SANCTUM)
Flow:
Login → dapat token
Simpan di:
cookie (recommended)
Semua request pakai:
Authorization: Bearer token
🔌 API DESIGN (SINGKAT)
KGB
GET    /api/kgb
POST   /api/kgb/generate
POST   /api/kgb/{id}/submit
POST   /api/kgb/{id}/verify
POST   /api/kgb/{id}/approve
GET    /api/kgb/{id}
PMK
POST   /api/pmk
GET    /api/pmk
SIM-ASN Proxy (optional)
GET /api/sim-asn/pegawai
GET /api/sim-asn/pegawai/{id}
🧠 STRATEGI PENTING (JANGAN DILANGGAR)
🔥 1. Jangan langsung pakai data SIM-ASN di UI

👉 selalu lewat backend

🔥 2. Jangan hitung gaji di frontend

👉 semua di backend

🔥 3. Gunakan DTO untuk validasi

Supaya:

controller bersih
data konsisten
🔥 4. Gunakan Transaction

Semua proses KGB:

DB::transaction()
🔥 5. Logging & Audit

Minimal:

Log::info('KGB Generated', [...]);
🚀 NEXT STEP (REKOMENDASI PALING KRITIS)

Kalau kamu mau sistem ini nggak jadi spaghetti:

👉 lanjut ke:

✔ API Contract + Response Standard

atau

✔ Implementasi Service (real code Laravel clean)

Saran saya:

lanjut ke API Contract (response + error + pagination standard)

Karena ini yang akan bikin:

frontend kamu clean
integrasi rapi
debugging gampang

Tinggal bilang: