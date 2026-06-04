<script setup lang="ts">
import { useRefGajiStore } from "~/stores/ref-gaji.store";

const store = useRefGajiStore();

onMounted(() => {
  store.fetchList("pns");
});

const activeTab = computed(() => store.activeTab);

function switchTab(tab: "pns" | "pppk") {
  store.setTab(tab);
}

function formatCurrency(val: number) {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
  }).format(val);
}

const searchGolongan = ref("");
const searchMk = ref("");

const filteredGrouped = computed(() => {
  const groups: Record<string, (typeof store.groupedByGolongan)[string]> = {};
  for (const [key, items] of Object.entries(store.groupedByGolongan)) {
    if (searchGolongan.value && !key.toLowerCase().includes(searchGolongan.value.toLowerCase()))
      continue;
    const filtered = items.filter(
      (i) => !searchMk.value || String(i.masa_kerja).includes(searchMk.value),
    );
    if (filtered.length) groups[key] = filtered;
  }
  return groups;
});

const sortedKeys = computed(() => Object.keys(filteredGrouped.value).sort());
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Referensi Gaji</h1>
    </div>

    <!-- Tabs -->
    <div class="flex gap-2 mb-6">
      <button
        @click="switchTab('pns')"
        :class="[
          'px-4 py-2 rounded-lg font-medium text-sm transition-colors',
          activeTab === 'pns'
            ? 'bg-indigo-600 text-white'
            : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200',
        ]"
      >
        PNS
      </button>
      <button
        @click="switchTab('pppk')"
        :class="[
          'px-4 py-2 rounded-lg font-medium text-sm transition-colors',
          activeTab === 'pppk'
            ? 'bg-indigo-600 text-white'
            : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200',
        ]"
      >
        PPPK
      </button>
    </div>

    <!-- Info -->
    <div v-if="store.peraturan.length" class="mb-4 text-sm text-gray-500">
      Berlaku suruh:
      <span class="font-medium text-gray-700">{{ store.peraturan[0].nama }}</span> ({{
        store.peraturan[0].effective_date
      }})
    </div>

    <!-- Search -->
    <div class="flex gap-3 mb-6">
      <div class="relative flex-1">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
        <input
          v-model="searchGolongan"
          placeholder="Cari golongan (mis. III, IV.b)..."
          class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
        />
      </div>
      <div class="relative">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">📅</span>
        <input
          v-model="searchMk"
          type="number"
          min="0"
          max="33"
          placeholder="Masa Kerja (th)"
          class="w-40 pl-9 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
        />
      </div>
    </div>

    <!-- Loading -->
    <div v-if="store.loading" class="p-12 text-center text-gray-500">Memuat data...</div>

    <!-- Error -->
    <div v-else-if="store.error" class="p-6 bg-red-50 text-red-700 rounded-lg">
      {{ store.error }}
    </div>

    <!-- Empty -->
    <div v-else-if="sortedKeys.length === 0" class="p-12 text-center text-gray-500">
      Tidak ada data untuk ditampilkan
    </div>

    <!-- Grouped Tables -->
    <div v-else class="space-y-8">
      <div
        v-for="golonganKey in sortedKeys"
        :key="golonganKey"
        class="bg-white rounded-lg shadow-sm overflow-hidden"
      >
        <!-- Golongan Header -->
        <div
          class="px-5 py-3 bg-indigo-50 border-b border-indigo-100 flex items-center justify-between"
        >
          <div>
            <span class="text-lg font-bold text-indigo-800">Golongan {{ golonganKey }}</span>
            <span
              v-if="
                store.uniqueGolongan.find(
                  (g) =>
                    (g.sub_golongan ? `${g.golongan}${g.sub_golongan}` : g.golongan) ===
                    golonganKey,
                )
              "
              class="ml-3 text-sm text-indigo-600"
            >
              {{
                store.uniqueGolongan.find(
                  (g) =>
                    (g.sub_golongan ? `${g.golongan}${g.sub_golongan}` : g.golongan) ===
                    golonganKey,
                )?.pangkat
              }}
            </span>
          </div>
          <span class="text-xs text-indigo-400"
            >{{ filteredGrouped[golonganKey]?.length }} data</span
          >
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-24">
                  MK (tahun)
                </th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">
                  Gaji
                </th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                  Peraturan
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="item in filteredGrouped[golonganKey]"
                :key="item.id"
                class="hover:bg-gray-50"
              >
                <td class="px-4 py-2 text-gray-700">{{ item.masa_kerja }}</td>
                <td class="px-4 py-2 text-right font-medium text-gray-900">
                  {{ formatCurrency(item.gaji) }}
                </td>
                <td class="px-4 py-2 text-gray-500 text-xs">
                  {{ item.peraturan.jenis }} {{ item.peraturan.nomor }}/{{ item.peraturan.tahun }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
