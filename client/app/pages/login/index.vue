<script setup lang="ts">
import { useAuthStore } from "~/stores/auth.store";

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();

definePageMeta({
  layout: "auth",
});
const form = ref({
  email: "",
  password: "",
});

const loading = ref(false);
const error = ref<string | null>(null);

onMounted(() => {
  const oauthRegister = route.query.oauth_register as string | undefined;
  const accessToken = route.query.access_token as string | undefined;
  const errorParam = route.query.error as string | undefined;

  // Priority 1: SIM-ASN OAuth new user — redirect to registration page
  if (oauthRegister) {
    authStore.setOAuthRegistrationPayload(oauthRegister);
    router.replace({ query: {} });
    router.push("/auth/register");
    return;
  }

  // Priority 2: Existing user returning with Sanctum token
  if (accessToken) {
    authStore
      .loginWithToken(accessToken)
      .then(() => {
        router.replace({ query: {} });
        router.push("/dashboard");
      })
      .catch((e: any) => {
        error.value = e.data?.message || "Login gagal. Silakan coba lagi.";
      });
    return;
  }

  // Priority 3: OAuth error from callback
  if (errorParam) {
    if (errorParam === "user_not_found") {
      error.value = "Akun Anda belum terdaftar di sistem ini.";
    } else if (errorParam === "oauth_denied") {
      error.value = "Login SIM-ASN dibatalkan.";
    } else {
      error.value = "Login SIM-ASN gagal. Silakan coba lagi.";
    }
    router.replace({ query: {} });
  }
});

async function login() {
  loading.value = true;
  error.value = null;
  try {
    await authStore.login(form.value);
    router.push("/dashboard");
  } catch (e: any) {
    error.value = e.data?.message || "Login gagal. Silakan periksa kredensial Anda.";
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">
      <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Sistem KGB & PMK</h1>
        <p class="text-gray-500 mt-1">Silakan masuk untuk melanjutkan</p>
      </div>

      <form @submit.prevent="login" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input
            v-model="form.email"
            type="email"
            placeholder="email@instance.domain"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            required
            autocomplete="email"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
          <input
            v-model="form.password"
            type="password"
            placeholder="••••••••"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            required
            autocomplete="current-password"
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
          {{ loading ? "Memuat..." : "Masuk" }}
        </button>
      </form>

      <div class="flex items-center gap-3 my-6">
        <div class="flex-1 h-px bg-gray-300"></div>
        <span class="text-sm text-gray-400">atau</span>
        <div class="flex-1 h-px bg-gray-300"></div>
      </div>

      <a
        href="/auth/sim-asn"
        class="block w-full px-4 py-2 border-2 border-indigo-600 text-indigo-600 rounded-lg hover:bg-indigo-50 font-medium text-center"
      >
        Masuk dengan SIM-ASN
      </a>
    </div>
  </div>
</template>
