<script setup lang="ts">
defineProps<{
  title?: string;
  padding?: boolean;
}>();
</script>

<template>
  <div :class="['bg-white rounded-lg shadow-sm', padding !== false ? 'p-6' : '']">
    <h3 v-if="title" class="text-lg font-semibold mb-4 text-gray-900">{{ title }}</h3>
    <slot />
  </div>
</template>
