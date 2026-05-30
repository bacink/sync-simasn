<script setup lang="ts">
import { useAuthStore } from '~/stores/auth.store'

definePageMeta({
  layout: 'auth'
})

const authStore = useAuthStore()
const router = useRouter()

const loading = ref(false)
const error = ref<string | null>(null)

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  opd_id: null as number | null,
  sim_asn_user_id: '',
})

const opds = ref<Array<{ id: number; name: string }>>([])

onMounted(() => {
  const payload = authStore.oauthRegistrationPayload
  if (payload) {
    form.value.name = payload.name
    form.value.email = payload.email
    form.value.opd_id = payload.opd_id
    form.value.sim_asn_user_id = payload.sim_asn_user_id
    authStore.clearOAuthRegistrationPayload()
  } else {
    router.replace('/login')
    return
  }

  // Load OPD list
  const api = useApi()
  api.get<{ data: Array<{ id: number; name: string }> }>('/api/v1/ref/opd')
    .then(res => { opds.value = res.data })
    .catch(() => {})
})

async function register() {
  if (form.value.password !== form.value.password_confirmation) {
    error.value = 'Password dan konfirmasi password tidak cocok.'
    return
  }
  loading.value = true
  error.value = null
  try {
    const api = useApi()
    const res = await api.post<{ data: { user: any; token: string } }>('/api/v1/auth/register-from-sim-asn', {
      name: form.value.name,
      email: form.value.email,
      password: form.value.password,
      password_confirmation: form.value.password_confirmation,
      opd_id: form.value.opd_id,
      sim_asn_user_id: form.value.sim_asn_user_id,
      sim_asn_token: authStore.oauthRegistrationPayload?.sim_asn_token || { access_token: '' },
    })
    authStore.user = res.data.user
    authStore.loginWithToken(res.data.token)
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
      <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Daftar Akun</h1>
        <p class="text-gray-500 mt-1">Lengkapi data di bawah untuk mengaktifkan akun</p>
      </div>

      <form @submit.prevent="register" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
          <input v-model="form.name" type="text" readonly
            class="w-full px-4 py-2 border rounded-lg bg-gray-50 text-gray-500" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input v-model="form.email" type="email" readonly
            class="w-full px-4 py-2 border rounded-lg bg-gray-50 text-gray-500" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Unit Kerja (OPD)</label>
          <select v-model="form.opd_id"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            <option :value="null" disabled>Pilih OPD</option>
            <option v-for="opd in opds" :key="opd.id" :value="opd.id">{{ opd.name }}</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
          <input v-model="form.password" type="password" placeholder="Minimal 8 karakter"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            minlength="8" required autocomplete="new-password" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password</label>
          <input v-model="form.password_confirmation" type="password" placeholder="Ulangi password"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            minlength="8" required autocomplete="new-password" />
        </div>

        <div v-if="error" class="p-3 bg-red-50 text-red-700 text-sm rounded-lg">
          {{ error }}
        </div>

        <button type="submit" :disabled="loading"
          class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 font-medium">
          {{ loading ? 'Memuat...' : 'Daftar dan Masuk' }}
        </button>

        <p class="text-center text-sm text-gray-500 mt-4">
          <NuxtLink to="/login" class="text-indigo-600 hover:underline">Batal</NuxtLink>
        </p>
      </form>
    </div>
  </div>
</template>
