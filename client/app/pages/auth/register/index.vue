<script setup lang="ts">
import { useAuthStore } from '~/stores/auth.store'

definePageMeta({
  layout: 'auth'
})

const authStore = useAuthStore()
const router = useRouter()

interface OAuthData {
  sim_asn_user_id: string
  name: string | null
  email: string | null
  access_token: string
  refresh_token: string | null
  expires_at: string | null
}

const oauthData = ref<OAuthData | null>(null)

const form = ref({
  name: '',
  email: '',
  opd_id: '' as string | number | undefined,
})

const loading = ref(false)
const error = ref<string | null>(null)

// Load OPD list
const opds = ref<{ id: number; nama: string }[]>([])

onMounted(async () => {
  // Check payload exists
  const payload = authStore.oauthRegistrationPayload
  if (!payload) {
    router.replace('/login')
    return
  }

  // Decode OAuth data
  try {
    const decoded: OAuthData = JSON.parse(atob(payload))
    oauthData.value = decoded
    form.value.name = decoded.name || ''
    form.value.email = decoded.email || ''
  } catch {
    error.value = 'Data registrasi tidak valid.'
    return
  }

  // Load OPD list for dropdown
  try {
    const api = useApi()
    const res = await api.get<{ data: { id: number; nama: string }[] }>('/api/v1/ref/opd')
    opds.value = res.data
  } catch {
    // OPD loading failure is non-fatal — form still works
  }
})

async function register() {
  if (!oauthData.value) return

  loading.value = true
  error.value = null
  try {
    const api = useApi()
    const res = await api.post<{ data: { user: any; token: string } }>(
      '/api/v1/auth/register-from-sim-asn',
      {
        name: form.value.name,
        email: form.value.email,
        opd_id: form.value.opd_id || null,
        sim_asn_user_id: oauthData.value.sim_asn_user_id,
        sim_asn_token: {
          access_token: oauthData.value.access_token,
          refresh_token: oauthData.value.refresh_token,
          expires_at: oauthData.value.expires_at,
        },
      },
    )
    await authStore.loginWithToken(res.data.token)
    authStore.clearOAuthRegistrationPayload()
    router.push('/dashboard')
  } catch (e: any) {
    error.value = e.data?.message || 'Registrasi gagal. Silakan coba lagi.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">
      <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Daftar Akun SIM-ASN</h1>
        <p class="text-gray-500 mt-1">Lengkapi data untuk membuat akun</p>
      </div>

      <div v-if="error" class="p-3 bg-red-50 text-red-700 text-sm rounded-lg mb-4">
        {{ error }}
      </div>

      <div v-if="oauthData" class="p-3 bg-blue-50 text-blue-700 text-sm rounded-lg mb-4">
        Login sebagai: <strong>{{ oauthData.name || oauthData.sim_asn_user_id }}</strong>
      </div>

      <form @submit.prevent="register" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
          <input v-model="form.name" type="text" placeholder="Nama lengkap"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            required />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input v-model="form.email" type="email" placeholder="email@example.com"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            required />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">OPD</label>
          <select v-model="form.opd_id"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            <option value="">Pilih OPD (opsional)</option>
            <option v-for="opd in opds" :key="opd.id" :value="opd.id">
              {{ opd.nama }}
            </option>
          </select>
        </div>

        <button type="submit" :disabled="loading || !oauthData"
          class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 font-medium">
          {{ loading ? 'Mendaftarkan...' : 'Daftar dan Masuk' }}
        </button>
      </form>

      <p class="text-center text-sm text-gray-500 mt-4">
        <NuxtLink to="/login" class="text-indigo-600 hover:underline">Kembali ke Login</NuxtLink>
      </p>
    </div>
  </div>
</template>
