<script setup lang="ts">
import { useAuthStore } from '~/stores/auth.store'

const authStore = useAuthStore()
const router = useRouter()

// If already logged in, redirect to dashboard
if (authStore.isAuthenticated) {
  router.push('/dashboard')
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-50 to-blue-50">
    <div class="w-full max-w-md">
      <slot />
    </div>
  </div>
</template>