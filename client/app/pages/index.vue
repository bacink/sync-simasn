<script setup lang="ts">
import { useAuthStore } from '~/stores/auth.store'

definePageMeta({
  layout: 'auth'
})

const authStore = useAuthStore()
const router = useRouter()

onMounted(async () => {
  await authStore.fetchUser()
  if (authStore.isLoggedIn) {
    router.push('/dashboard')
  }
})
</script>

<template>
  <div class="flex-1 flex flex-col items-center justify-center text-center px-6">
    <div class="mb-8">
      <div class="w-20 h-20 bg-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg">
        <span class="text-white text-3xl font-bold">KGB</span>
      </div>
      <h1 class="text-4xl font-bold text-gray-900">Sistem KGB &amp; PMK</h1>
      <p class="text-gray-500 mt-2 text-lg max-w-sm">
        Kelola Kenaikan Gaji Berkala dan Perubahan Masa Kerja ASN dengan mudah dan terstruktur.
      </p>
    </div>

    <div class="flex flex-col sm:flex-row gap-3 w-full max-w-sm">
      <NuxtLink
        to="/login"
        class="flex-1 px-6 py-3 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 transition-colors text-center"
      >
        Masuk
      </NuxtLink>
      <NuxtLink
        to="/dashboard"
        class="flex-1 px-6 py-3 bg-white border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors text-center"
      >
        Lihat Dashboard
      </NuxtLink>
    </div>

    <p class="text-gray-400 text-sm mt-12">
      Gunakan kredensial instansi Anda untuk mengakses sistem.
    </p>
  </div>
</template>