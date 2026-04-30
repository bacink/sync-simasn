---
name: nuxt-development
description: "Nuxt 3/4 frontend development - Vue components, Pinia stores, composables, pages, layouts, SSR, and API integration. Use when building or modifying client/ directory."
license: MIT
metadata:
  author: current-user
---

# Nuxt Development

## Stack

- **Nuxt**: v4.4.2
- **TailwindCSS**: v6.14.0
- **Pinia**: @pinia/nuxt v0.11.3
- **Testing**: vitest v4.1.4

## Project Structure

```
client/
├── app/
│   └── app.vue           # Root component
├── pages/               # File-based routing
├── components/         # Auto-imported components
├── composables/         # Auto-imported composables
├── stores/              # Pinia stores
├── services/           # API service layer
├── types/               # TypeScript definitions
└── public/             # Static assets
```

## Usage

### Run Dev Server
```bash
cd /home/bacink/DevApps/2026/kgb/client && npm run dev
```

### Build
```bash
npm run build
```

### Test
```bash
npm test              # Run all tests
npm run test:watch    # Watch mode
npm run test:unit    # Unit tests only
npm run test:nuxt    # Nuxt tests only
```

## Vue Component Patterns

Use `<script setup lang="ts">` for all components:

```vue
<script setup lang="ts">
interface Props {
  title: string
  count?: number
}

const props = withDefaults(defineProps<Props>(), {
  count: 0
})

const emit = defineEmits<{
  update: [value: number]
}>()
</script>

<template>
  <div>{{ title }} - {{ count }}</div>
</template>
```

## Pinia Store Pattern

```ts
// stores/user.store.ts
import { defineStore } from 'pinia'

export const useUserStore = defineStore('user', {
  state: () => ({
    user: null as User | null,
    loading: false,
  }),

  getters: {
    isLoggedIn: (state) => !!state.user,
  },

  actions: {
    async fetchUser() {
      this.loading = true
      // call API
      this.loading = false
    },
  },
})
```

## API Service Pattern

```ts
// services/api.ts
const config = useRuntimeConfig()

export const kgbService = {
  async getAll() {
    return await $fetch(`${config.public.apiBase}/kgb`)
  },

  async create(data: KgbFormData) {
    return await $fetch(`${config.public.apiBase}/kgb`, {
      method: 'POST',
      body: data,
    })
  },
}
```

## Tailwind CSS

Use Tailwind v4 syntax. Classes go directly in template:

```vue
<div class="flex items-center gap-4 p-4 bg-white dark:bg-gray-900">
  <span class="text-sm text-gray-500">Label</span>
</div>
```

## Auto-Imports

Nuxt auto-imports from:
- `~/components/` → component name
- `~/composables/` → function name
- `~/server/api/` → API routes
- Built-ins: `useNuxtApp`, `useRoute`, `useRouter`, `useFetch`, `$fetch`, etc.

## Testing

Use `@nuxt/test-utils` for Nuxt-specific testing:

```ts
import { setup } from '@nuxt/test-utils'

describe('My Test', () => {
  setup({

  })
})
```