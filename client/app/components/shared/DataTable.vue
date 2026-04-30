<script setup lang="ts">
defineProps<{
  columns: { key: string; label: string; class?: string }[]
  data: Record<string, any>[]
  loading?: boolean
  emptyMessage?: string
}>()
</script>

<template>
  <div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div v-if="loading" class="p-8 text-center text-gray-500">
      Memuat data...
    </div>
    <div v-else-if="!data.length" class="p-8 text-center text-gray-500">
      {{ emptyMessage || 'Tidak ada data' }}
    </div>
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th
              v-for="col in columns"
              :key="col.key"
              :class="['px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase', col.class || '']"
            >
              {{ col.label }}
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <slot v-for="(row, idx) in data" :key="idx" name="row" :row="row" :idx="idx" />
        </tbody>
      </table>
    </div>
  </div>
</template>