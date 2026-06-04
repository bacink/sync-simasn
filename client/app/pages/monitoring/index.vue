<script setup lang="ts">
const kgbStore = useKgbStore();
const pmkStore = usePmkStore();

onMounted(async () => {
  await Promise.all([kgbStore.fetchAll(), pmkStore.fetchAll()]);
});

const stats = computed(() => ({
  kgb: {
    draft: kgbStore.items.filter((i) => i.status === "draft").length,
    diajukan: kgbStore.items.filter((i) => i.status === "diajukan").length,
    diverifikasi: kgbStore.items.filter((i) => i.status === "diverifikasi").length,
    disetujui: kgbStore.items.filter((i) => i.status === "disetujui").length,
    ditolak: kgbStore.items.filter((i) => i.status === "ditolak").length,
  },
  pmk: {
    draft: pmkStore.items.filter((i) => i.status === "draft").length,
    diajukan: pmkStore.items.filter((i) => i.status === "diajukan").length,
    diverifikasi: pmkStore.items.filter((i) => i.status === "diverifikasi").length,
    disetujui: pmkStore.items.filter((i) => i.status === "disetujui").length,
  },
}));
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Monitoring</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- KGB Stats -->
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold mb-4">KGB</h2>
        <div class="grid grid-cols-2 gap-4">
          <div class="p-4 bg-gray-100 rounded-lg">
            <div class="text-2xl font-bold">{{ stats.kgb.draft }}</div>
            <div class="text-sm text-gray-600">Draft</div>
          </div>
          <div class="p-4 bg-blue-50 rounded-lg">
            <div class="text-2xl font-bold text-blue-600">{{ stats.kgb.diajukan }}</div>
            <div class="text-sm text-blue-600">Diajukan</div>
          </div>
          <div class="p-4 bg-yellow-50 rounded-lg">
            <div class="text-2xl font-bold text-yellow-600">{{ stats.kgb.diverifikasi }}</div>
            <div class="text-sm text-yellow-600">Diverifikasi</div>
          </div>
          <div class="p-4 bg-green-50 rounded-lg">
            <div class="text-2xl font-bold text-green-600">{{ stats.kgb.disetujui }}</div>
            <div class="text-sm text-green-600">Disetujui</div>
          </div>
        </div>
      </div>

      <!-- PMK Stats -->
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold mb-4">PMK</h2>
        <div class="grid grid-cols-2 gap-4">
          <div class="p-4 bg-gray-100 rounded-lg">
            <div class="text-2xl font-bold">{{ stats.pmk.draft }}</div>
            <div class="text-sm text-gray-600">Draft</div>
          </div>
          <div class="p-4 bg-blue-50 rounded-lg">
            <div class="text-2xl font-bold text-blue-600">{{ stats.pmk.diajukan }}</div>
            <div class="text-sm text-blue-600">Diajukan</div>
          </div>
          <div class="p-4 bg-yellow-50 rounded-lg">
            <div class="text-2xl font-bold text-yellow-600">{{ stats.pmk.diverifikasi }}</div>
            <div class="text-sm text-yellow-600">Diverifikasi</div>
          </div>
          <div class="p-4 bg-green-50 rounded-lg">
            <div class="text-2xl font-bold text-green-600">{{ stats.pmk.disetujui }}</div>
            <div class="text-sm text-green-600">Disetujui</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
