<script setup lang="ts">
interface Toast {
  id: string
  message: string
  type: 'success' | 'error' | 'info' | 'warning'
}

const toasts = ref<Toast[]>([])

function add(message: string, type: Toast['type'] = 'info') {
  const id = Date.now().toString()
  toasts.value.push({ id, message, type })
  setTimeout(() => remove(id), 4000)
}

function remove(id: string) {
  toasts.value = toasts.value.filter(t => t.id !== id)
}

defineExpose({ add })
</script>

<template>
  <Teleport to="body">
    <div class="fixed top-4 right-4 z-50 flex flex-col gap-2">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        :class="[
          'flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg text-sm font-medium animate-slide-in',
          {
            'bg-green-50 text-green-800 border border-green-200': toast.type === 'success',
            'bg-red-50 text-red-800 border border-red-200': toast.type === 'error',
            'bg-blue-50 text-blue-800 border border-blue-200': toast.type === 'info',
            'bg-yellow-50 text-yellow-800 border border-yellow-200': toast.type === 'warning',
          }
        ]"
      >
        <span>{{ toast.message }}</span>
        <button @click="remove(toast.id)" class="ml-2 opacity-60 hover:opacity-100">&times;</button>
      </div>
    </div>
  </Teleport>
</template>