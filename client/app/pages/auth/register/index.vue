<script setup lang="ts">
import type { ApiResponse } from "~/types/api";
import type { AuthResponse } from "~/types/api";

definePageMeta({
  layout: "auth",
  middleware: [],
});

const authStore = useAuthStore();
const api = useApi();
const router = useRouter();

const oauthPayload = computed(() => {
  if (!authStore.oauthRegistrationPayload) return null;
  try {
    return JSON.parse(atob(authStore.oauthRegistrationPayload));
  } catch {
    return null;
  }
});

// Redirect to login if no OAuth payload
onMounted(() => {
  if (!authStore.oauthRegistrationPayload) {
    router.replace("/login");
  }
});

const opds = ref<{ id: number; nama: string; kode: string | null }[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);

const form = ref({
  name: oauthPayload.value?.name || "",
  email: "",
  password: "",
  password_confirmation: "",
  opd_id: null as number | null,
});

onMounted(async () => {
  try {
    const res = await api.get<ApiResponse<typeof opds.value>>("/api/v1/ref/opd");
    opds.value = res.data;
  } catch (e: any) {
    error.value = "Gagal memuat daftar OPD.";
  }
});

async function register() {
  if (!authStore.oauthRegistrationPayload) {
    error.value = "Sesi pendaftaran tidak valid.";
    return;
  }

  loading.value = true;
  error.value = null;

  try {
    const payload = JSON.parse(atob(authStore.oauthRegistrationPayload));

    const res = await api.post<ApiResponse<AuthResponse>>(
      "/api/v1/auth/register-from-sim-asn",
      {
        name: form.value.name,
        email: form.value.email,
        password: form.value.password,
        password_confirmation: form.value.password_confirmation,
        opd_id: form.value.opd_id,
        sim_asn_user_id: payload.sim_asn_user_id,
        sim_asn_token: payload.sim_asn_token,
      },
    );

    authStore.clearOAuthRegistrationPayload();
    await authStore.loginWithToken(res.data.token);
    router.push("/dashboard");
  } catch (e: any) {
    const msg = e.data?.errors
      ? Object.values(e.data.errors).flat().join(", ")
      : e.data?.message || "Pendaftaran gagal. Silakan coba lagi.";
    error.value = msg;
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
    <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">
      <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Pendaftaran Akun</h1>
        <p class="text-gray-500 mt-1">
          Lengkapi data di bawah untuk mengaktifkan akun SIM-ASN Anda
        </p>
      </div>

      <div
        v-if="oauthPayload"
        class="mb-4 p-3 bg-indigo-50 text-indigo-700 text-sm rounded-lg"
      >
        Masuk sebagai: <strong>{{ oauthPayload.name }}</strong>
        (NIP: {{ oauthPayload.nip }})
      </div>

      <form @submit.prevent="register" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
          <input
            v-model="form.name"
            type="text"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            required
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input
            v-model="form.email"
            type="email"
            placeholder="email@example.com"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            required
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Unit Kerja (OPD)</label>
          <select
            v-model="form.opd_id"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            required
          >
            <option :value="null" disabled>Pilih Unit Kerja</option>
            <option v-for="opd in opds" :key="opd.id" :value="opd.id">
              {{ opd.nama }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
          <input
            v-model="form.password"
            type="password"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            required
            minlength="8"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Konfirmasi Password
          </label>
          <input
            v-model="form.password_confirmation"
            type="password"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            required
            minlength="8"
          />
        </div>

        <div v-if="error" class="p-3 bg-red-50 text-red-700 text-sm rounded-lg">
          {{ error }}
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 font-medium"
        >
          {{ loading ? "Mendaftarkan..." : "Daftarkan Akun" }}
        </button>

        <p class="text-center text-sm text-gray-500">
          Sudah punya akun?
          <NuxtLink to="/login" class="text-indigo-600 hover:underline">
            Masuk di sini
          </NuxtLink>
        </p>
      </form>
    </div>
  </div>
</template>
