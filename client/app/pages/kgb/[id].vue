<script setup lang="ts">
import { useKgbStore } from "~/stores/kgb.store";
import { useAuthStore } from "~/stores/auth.store";

const route = useRoute();
const kgbStore = useKgbStore();
const authStore = useAuthStore();
const router = useRouter();

const kgbId = computed(() => Number(route.params.id));

onMounted(async () => {
  await kgbStore.fetchById(kgbId.value);
});

const loadingAction = ref(false);
const actionError = ref<string | null>(null);
const actionSuccess = ref<string | null>(null);
const showRejectModal = ref(false);
const rejectCatatan = ref("");

async function handleSubmit() {
  loadingAction.value = true;
  actionError.value = null;
  actionSuccess.value = null;
  try {
    await kgbStore.submit(kgbId.value);
    actionSuccess.value = "KGB berhasil diajukan.";
  } catch (e: any) {
    actionError.value = e.data?.message || "Gagal mengajukan KGB";
  } finally {
    loadingAction.value = false;
  }
}

async function handleVerify() {
  loadingAction.value = true;
  actionError.value = null;
  actionSuccess.value = null;
  try {
    await kgbStore.verify(kgbId.value);
    actionSuccess.value = "KGB berhasil diverifikasi.";
  } catch (e: any) {
    actionError.value = e.data?.message || "Gagal memverifikasi KGB";
  } finally {
    loadingAction.value = false;
  }
}

async function handleApprove() {
  loadingAction.value = true;
  actionError.value = null;
  actionSuccess.value = null;
  try {
    await kgbStore.approve(kgbId.value);
    actionSuccess.value = "KGB berhasil disetujui.";
  } catch (e: any) {
    actionError.value = e.data?.message || "Gagal menyetujui KGB";
  } finally {
    loadingAction.value = false;
  }
}

async function handleReject() {
  if (!rejectCatatan.value.trim()) return;
  loadingAction.value = true;
  actionError.value = null;
  try {
    await kgbStore.reject(kgbId.value, rejectCatatan.value);
    showRejectModal.value = false;
    actionSuccess.value = "KGB berhasil ditolak.";
  } catch (e: any) {
    actionError.value = e.data?.message || "Gagal menolak KGB";
  } finally {
    loadingAction.value = false;
  }
}

function getStatusColor(status: string) {
  const colors: Record<string, string> = {
    draft: "bg-gray-100 text-gray-800",
    diajukan: "bg-blue-100 text-blue-800",
    diverifikasi: "bg-yellow-100 text-yellow-800",
    disetujui: "bg-green-100 text-green-800",
    ditolak: "bg-red-100 text-red-800",
  };
  return colors[status] || "bg-gray-100";
}

function formatCurrency(val: number) {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
  }).format(val);
}

function formatDate(dateStr: string) {
  if (!dateStr) return "-";
  return new Date(dateStr).toLocaleDateString("id-ID", {
    day: "2-digit",
    month: "long",
    year: "numeric",
  });
}

const currentStatus = computed(() => kgbStore.currentItem?.status);
</script>

<template>
  <div>
    <div class="flex items-center gap-4 mb-6">
      <button @click="router.back()" class="text-gray-500 hover:text-gray-700">← Kembali</button>
      <h1 class="text-2xl font-bold text-gray-900">Detail KGB</h1>
    </div>

    <div v-if="kgbStore.isLoading" class="p-12 text-center text-gray-500">Memuat...</div>

    <div v-else-if="!kgbStore.currentItem" class="p-12 text-center text-gray-500">
      Data KGB tidak ditemukan.
    </div>

    <div v-else class="space-y-6">
      <!-- Status & Actions -->
      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-3">
            <h2 class="text-lg font-semibold">Status</h2>
            <span :class="['px-3 py-1 text-sm rounded-full', getStatusColor(currentStatus!)]">
              {{ currentStatus }}
            </span>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap gap-3">
          <button
            v-if="currentStatus === 'draft'"
            @click="handleSubmit"
            :disabled="loadingAction"
            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50"
          >
            Ajukan
          </button>
          <button
            v-if="currentStatus === 'diajukan' && (authStore.isVerifikator || authStore.isAdmin)"
            @click="handleVerify"
            :disabled="loadingAction"
            class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 disabled:opacity-50"
          >
            Verifikasi
          </button>
          <button
            v-if="currentStatus === 'diverifikasi' && authStore.isAdmin"
            @click="handleApprove"
            :disabled="loadingAction"
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50"
          >
            Setujui
          </button>
          <button
            v-if="
              (currentStatus === 'diajukan' || currentStatus === 'diverifikasi') &&
              (authStore.isVerifikator || authStore.isAdmin)
            "
            @click="showRejectModal = true"
            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
          >
            Tolak
          </button>
        </div>

        <!-- Feedback -->
        <div v-if="actionError" class="mt-4 p-3 bg-red-50 text-red-700 rounded-lg">
          {{ actionError }}
        </div>
        <div v-if="actionSuccess" class="mt-4 p-3 bg-green-50 text-green-700 rounded-lg">
          {{ actionSuccess }}
        </div>
      </div>

      <!-- Data & Calculation -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Data Pegawai -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <h2 class="text-lg font-semibold mb-4">Data Pegawai</h2>
          <dl class="space-y-3">
            <div class="flex justify-between">
              <dt class="text-gray-500">NIP</dt>
              <dd class="font-medium">{{ kgbStore.currentItem.nip }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500">Nama</dt>
              <dd class="font-medium">{{ kgbStore.currentItem.nama }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500">Golongan</dt>
              <dd class="font-medium">{{ kgbStore.currentItem.golongan }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500">Masa Kerja</dt>
              <dd class="font-medium">
                {{ kgbStore.currentItem.masa_kerja_tahun }} tahun
                {{ kgbStore.currentItem.masa_kerja_bulan }} bulan
              </dd>
            </div>
          </dl>
        </div>

        <!-- Perhitungan KGB -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <h2 class="text-lg font-semibold mb-4">Perhitungan Gaji</h2>
          <dl class="space-y-3">
            <div class="flex justify-between">
              <dt class="text-gray-500">Gaji Lama</dt>
              <dd class="font-medium">{{ formatCurrency(kgbStore.currentItem.gaji_lama) }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500">Gaji Baru</dt>
              <dd class="font-medium text-green-600">
                {{ formatCurrency(kgbStore.currentItem.gaji_baru) }}
              </dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500">TMT KGB</dt>
              <dd class="font-medium">{{ formatDate(kgbStore.currentItem.tmt_kgb) }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500">Jenis</dt>
              <dd class="font-medium capitalize">{{ kgbStore.currentItem.jenis_kgb }}</dd>
            </div>
          </dl>
        </div>
      </div>

      <!-- SK Document -->
      <div v-if="kgbStore.currentItem.nomor_sk" class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold mb-4">Surat Keterangan</h2>
        <dl class="space-y-3">
          <div class="flex justify-between">
            <dt class="text-gray-500">Nomor SK</dt>
            <dd class="font-medium">{{ kgbStore.currentItem.nomor_sk }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">Tanggal SK</dt>
            <dd class="font-medium">{{ formatDate(kgbStore.currentItem.tanggal_sk) }}</dd>
          </div>
        </dl>
      </div>
    </div>

    <!-- Reject Modal -->
    <Teleport to="body">
      <div
        v-if="showRejectModal"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
        @click.self="showRejectModal = false"
      >
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
          <h3 class="text-lg font-semibold mb-4">Tolak KGB</h3>
          <textarea
            v-model="rejectCatatan"
            placeholder="Masukkan alasan penolakan..."
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-red-500 mb-4"
            rows="4"
          />
          <div class="flex gap-3">
            <button
              @click="handleReject"
              :disabled="loadingAction || !rejectCatatan.trim()"
              class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50"
            >
              Tolak
            </button>
            <button
              @click="showRejectModal = false"
              class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
            >
              Batal
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
