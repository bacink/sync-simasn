<script setup lang="ts">
interface Props {
  status: 'draft' | 'diajukan' | 'diverifikasi' | 'disetujui' | 'ditolak'
}

const props = defineProps<Props>()

const statusConfig = {
  draft: {
    label: 'Draft',
    bg: 'bg-gray-100',
    text: 'text-gray-700',
    dot: 'bg-gray-400',
  },
  diajukan: {
    label: 'Diajukan',
    bg: 'bg-blue-50',
    text: 'text-blue-700',
    dot: 'bg-blue-500',
  },
  diverifikasi: {
    label: 'Diverifikasi',
    bg: 'bg-amber-50',
    text: 'text-amber-700',
    dot: 'bg-amber-400',
  },
  disetujui: {
    label: 'Disetujui',
    bg: 'bg-green-50',
    text: 'text-green-700',
    dot: 'bg-green-500',
  },
  ditolak: {
    label: 'Ditolak',
    bg: 'bg-red-50',
    text: 'text-red-700',
    dot: 'bg-red-400',
  },
}

const config = computed(() => statusConfig[props.status] ?? statusConfig.draft)
</script>

<template>
  <span
    :class="[
      'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium',
      config.bg,
      config.text,
    ]"
  >
    <span :class="['w-1.5 h-1.5 rounded-full', config.dot]" />
    {{ config.label }}
  </span>
</template>