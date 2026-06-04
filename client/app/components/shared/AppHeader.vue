<script setup lang="ts">
import { useAuthStore } from "~/stores/auth.store";

const authStore = useAuthStore();
const route = useRoute();

const pageTitle = computed(() => {
  const map: Record<string, string> = {
    "/dashboard": "Dashboard",
    "/kgb": "Kelola KGB",
    "/pmk": "Kelola PMK",
  };
  return map[route.path] || (route.meta.title as string) || "KGB System";
});
</script>

<template>
  <header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
    <div>
      <h2 class="text-lg font-semibold text-gray-900">{{ pageTitle }}</h2>
    </div>
    <div class="flex items-center gap-4">
      <span v-if="authStore.user" class="text-sm text-gray-500">
        {{ authStore.user.name }}
      </span>
      <div
        v-if="authStore.isAdmin"
        class="px-2 py-1 text-xs bg-indigo-100 text-indigo-700 rounded-full"
      >
        Admin
      </div>
      <div
        v-else-if="authStore.isVerifikator"
        class="px-2 py-1 text-xs bg-yellow-100 text-yellow-700 rounded-full"
      >
        Verifikator
      </div>
    </div>
  </header>
</template>
