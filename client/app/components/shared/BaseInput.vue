<script setup lang="ts">
defineProps<{
  modelValue: string | number;
  label?: string;
  type?: string;
  placeholder?: string;
  error?: string;
  required?: boolean;
  disabled?: boolean;
  name?: string;
  id?: string;
}>();

defineEmits<{
  "update:modelValue": [value: string];
}>();
</script>

<template>
  <div class="flex flex-col gap-1">
    <label v-if="label" :for="id || name" class="text-sm font-medium text-gray-700">
      {{ label }}<span v-if="required" class="text-red-500 ml-1">*</span>
    </label>
    <input
      :id="id || name"
      :name="name"
      :type="type || 'text'"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
      :class="[
        'w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 transition-colors',
        error
          ? 'border-red-300 focus:ring-red-500 focus:border-red-500'
          : 'border-gray-300 focus:ring-indigo-500 focus:border-indigo-500',
        disabled ? 'bg-gray-100 cursor-not-allowed' : 'bg-white',
      ]"
    />
    <span v-if="error" class="text-xs text-red-600">{{ error }}</span>
  </div>
</template>
