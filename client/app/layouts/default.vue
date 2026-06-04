<script setup lang="ts">
import { useAuthStore } from "~/stores/auth.store";

const authStore = useAuthStore();
const router = useRouter();

// Protect all non-auth pages
if (!authStore.isLoggedIn) {
  router.push("/login");
}

definePageMeta({
  middleware: ["auth"],
});
</script>

<template>
  <div class="flex h-screen bg-gray-50">
    <SharedAppSidebar />
    <div class="flex-1 flex flex-col overflow-hidden">
      <SharedAppHeader />
      <main class="flex-1 overflow-y-auto p-6">
        <slot />
      </main>
    </div>
  </div>
</template>
