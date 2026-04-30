<script setup lang="ts">
const props = defineProps<{
  modelValue: boolean
  title?: string
  size?: 'sm' | 'md' | 'lg'
}>()

defineEmits<{ 'update:modelValue': [value: boolean] }>()

function close() {
  // Use v-model pattern
  const input = event?.target as HTMLElement
  if (input?.closest('.modal-content')) return
  // Parent handles via v-model
}
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="modelValue"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
        @click.self="$emit('update:modelValue', false)"
      >
        <div
          :class="[
            'bg-white rounded-lg shadow-xl modal-content',
            {
              'max-w-sm': size === 'sm',
              'max-w-md': size === 'md' || !size,
              'max-w-2xl': size === 'lg',
            }
          ]"
        >
          <div v-if="title" class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold">{{ title }}</h3>
            <button
              @click="$emit('update:modelValue', false)"
              class="text-gray-400 hover:text-gray-600 text-xl leading-none"
            >
              &times;
            </button>
          </div>
          <div class="p-6">
            <slot />
          </div>
          <div v-if="$slots.footer" class="px-6 py-4 border-t border-gray-200 flex gap-3 justify-end">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>