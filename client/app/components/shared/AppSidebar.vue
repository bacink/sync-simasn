<script setup lang="ts">
import { useAuthStore } from "~/stores/auth.store";

const authStore = useAuthStore();
const route = useRoute();

const navItems = [
  { label: "Dashboard", to: "/dashboard", icon: "📊" },
  { label: "KGB", to: "/kgb", icon: "📋" },
  { label: "PMK", to: "/pmk", icon: "📄" },
  { label: "Referensi Gaji", to: "/ref-gaji", icon: "💰" },
];

function isActive(path: string) {
  return route.path === path || route.path.startsWith(path + "/");
}
</script>

<template>
  <aside class="w-64 min-h-screen bg-white border-r border-gray-200 flex flex-col">
    <div class="p-6 border-b border-gray-200">
      <h1 class="text-xl font-bold text-indigo-600">KGB System</h1>
    </div>
    <nav class="flex-1 p-4 space-y-1">
      <NuxtLink
        v-for="item in navItems"
        :key="item.to"
        :to="item.to"
        :class="[
          'flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors',
          isActive(item.to) ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-100',
        ]"
      >
        <span class="text-base">{{ item.icon }}</span>
        {{ item.label }}
      </NuxtLink>
    </nav>
    <div class="p-4 border-t border-gray-200">
      <div v-if="authStore.user" class="text-sm text-gray-500 mb-2">{{ authStore.user.name }}</div>
      <button
        @click="authStore.logout()"
        class="flex items-center gap-2 px-4 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg w-full"
      >
        <span>🚪</span>
        Logout
      </button>
    </div>
  </aside>
</template>
