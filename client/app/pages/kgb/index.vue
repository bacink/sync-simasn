<script setup lang="ts">
const store = useKgbStore()

onMounted(() => {
  store.fetchAll()
})

const statusFilter = ref('')

watch(statusFilter, (val) => {
  store.fetchAll({ status: val || undefined })
})

function getStatusColor(status: string) {
  const colors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800',
    diajukan: 'bg-blue-100 text-blue-800',
    diverifikasi: 'bg-yellow-100 text-yellow-800',
    disetujui: 'bg-green-100 text-green-800',
    ditolak: 'bg-red-100 text-red-800'
  }
  return colors[status] || 'bg-gray-100'
}

function formatCurrency(val: number) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(val)
}
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Daftar KGB</h1>
      <NuxtLink to="/kgb/create" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
        + KGB Baru
      </NuxtLink>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
      <div class="flex gap-4">
        <select v-model="statusFilter" class="px-4 py-2 border rounded-lg">
          <option value="">Semua Status</option>
          <option value="draft">Draft</option>
          <option value="diajukan">Diajukan</option>
          <option value="diverifikasi">Diverifikasi</option>
          <option value="disetujui">Disetujui</option>
          <option value="ditolak">Ditolak</option>
        </select>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
      <div v-if="store.isLoading" class="p-8 text-center text-gray-500">
        Memuat data...
      </div>
      <div v-else-if="store.items.length === 0" class="p-8 text-center text-gray-500">
        Tidak ada data KGB
      </div>
      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">NIP</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Golongan</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Masa Kerja</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gaji Baru</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="item in store.items" :key="item.id">
            <td class="px-4 py-3 text-sm">{{ item.nip }}</td>
            <td class="px-4 py-3 text-sm font-medium">{{ item.nama }}</td>
            <td class="px-4 py-3 text-sm">{{ item.golongan }}</td>
            <td class="px-4 py-3 text-sm">{{ item.masa_kerja_tahun }} tahun {{ item.masa_kerja_bulan }} bulan</td>
            <td class="px-4 py-3 text-sm">{{ formatCurrency(item.gaji_baru) }}</td>
            <td class="px-4 py-3">
              <span :class="['px-2 py-1 text-xs rounded-full', getStatusColor(item.status)]">
                {{ item.status }}
              </span>
            </td>
            <td class="px-4 py-3">
              <NuxtLink :to="`/kgb/${item.id}`" class="text-indigo-600 hover:text-indigo-900">
                Detail
              </NuxtLink>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="store.meta" class="mt-4 flex justify-center gap-2">
      <button
        v-for="p in store.meta.last_page"
        :key="p"
        class="px-3 py-1 rounded"
        :class="store.meta.current_page === p ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700'"
      >
        {{ p }}
      </button>
    </div>
  </div>
</template>