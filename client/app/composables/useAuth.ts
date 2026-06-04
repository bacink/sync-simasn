import { useAuthStore } from "~/stores/auth.store";

export function useAuth() {
  const authStore = useAuthStore();

  return {
    isAuthenticated: computed(() => authStore.isAuthenticated),
    user: computed(() => authStore.user),
    userRole: computed(() => authStore.userRole),
    isAdmin: computed(() => authStore.isAdmin),
    isVerifikator: computed(() => authStore.isVerifikator),
    isOperator: computed(() => authStore.isOperator),
    login: authStore.login,
    logout: authStore.logout,
    fetchUser: authStore.fetchUser,
  };
}
