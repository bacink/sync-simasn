<script setup lang="ts">
import { useAuthStore } from "~/stores/auth.store";
import { useKgbStore } from "~/stores/kgb.store";
import { usePmkStore } from "~/stores/pmk.store";

const authStore = useAuthStore();
const kgbStore = useKgbStore();
const pmkStore = usePmkStore();

onMounted(async () => {
  await Promise.all([kgbStore.fetchList(), pmkStore.fetchList()]);
});

const stats = computed(() => ({
  totalKgb: kgbStore.items.length,
  pendingVerification: kgbStore.items.filter((i) => i.status === "diajukan").length,
  approvedThisMonth: kgbStore.items.filter((i) => i.status === "disetujui").length,
  totalPmk: pmkStore.items.length,
}));

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
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-gray-500 text-sm mt-1">
          Selamat datang{{ authStore.user?.name ? `, ${authStore.user.name}` : "" }}
        </p>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="text-3xl font-bold text-gray-900">{{ stats.totalKgb }}</div>
        <div class="text-sm text-gray-500 mt-1">Total KGB</div>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="text-3xl font-bold text-yellow-600">{{ stats.pendingVerification }}</div>
        <div class="text-sm text-gray-500 mt-1">Menunggu Verifikasi</div>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="text-3xl font-bold text-green-600">{{ stats.approvedThisMonth }}</div>
        <div class="text-sm text-gray-500 mt-1">Disetujui Bulan Ini</div>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="text-3xl font-bold text-gray-900">{{ stats.totalPmk }}</div>
        <div class="text-sm text-gray-500 mt-1">Total PMK</div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- KGB Activity -->
      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-gray-900">Aktivitas KGB Terbaru</h2>
          <NuxtLink to="/kgb" class="text-sm text-indigo-600 hover:text-indigo-800"
            >Lihat semua</NuxtLink
          >
        </div>

        <div v-if="kgbStore.isLoading" class="p-8 text-center text-gray-500">Memuat...</div>
        <div v-else-if="kgbStore.items.length === 0" class="p-4 text-center text-gray-500">
          Belum ada data KGB
        </div>
        <div v-else class="space-y-3">
          <div
            v-for="item in kgbStore.items.slice(0, 5)"
            :key="item.id"
            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
          >
            <div>
              <div class="font-medium text-gray-900">{{ item.nama }}</div>
              <div class="text-sm text-gray-500">{{ item.nip }} · Gol {{ item.golongan }}</div>
            </div>
            <div class="text-right">
              <span :class="['px-2 py-1 text-xs rounded-full', getStatusColor(item.status)]">
                {{ item.status }}
              </span>
              <div v-if="item.gaji_baru" class="text-sm font-medium text-gray-700 mt-1">
                {{ formatCurrency(item.gaji_baru) }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Aksi Cepat</h2>
        <div class="space-y-3">
          <NuxtLink
            to="/kgb/create"
            class="flex items-center gap-3 p-4 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-colors"
          >
            <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center">
              <span class="text-white text-lg">+</span>
            </div>
            <div>
              <div class="font-medium text-gray-900">Ajukan KGB Baru</div>
              <div class="text-sm text-gray-500">Buat kenaikan gaji berkala baru</div>
            </div>
          </NuxtLink>

          <NuxtLink
            to="/pmk/create"
            class="flex items-center gap-3 p-4 bg-amber-50 hover:bg-amber-100 rounded-lg transition-colors"
          >
            <div class="w-10 h-10 bg-amber-500 rounded-lg flex items-center justify-center">
              <span class="text-white text-lg">P</span>
            </div>
            <div>
              <div class="font-medium text-gray-900">Input PMK</div>
              <div class="text-sm text-gray-500">Catat perubahan masa kerja</div>
            </div>
          </NuxtLink>

          <NuxtLink
            to="/monitoring"
            class="flex items-center gap-3 p-4 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors"
          >
            <div class="w-10 h-10 bg-gray-500 rounded-lg flex items-center justify-center">
              <span class="text-white text-lg">📊</span>
            </div>
            <div>
              <div class="font-medium text-gray-900">Monitoring</div>
              <div class="text-sm text-gray-500">Lihat statistik KGB & PMK</div>
            </div>
          </NuxtLink>
        </div>
      </div>
    </div>
  </div>
</template>
