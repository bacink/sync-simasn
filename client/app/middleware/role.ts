export default defineNuxtRouteMiddleware((to) => {
  const authStore = useAuthStore();

  const requiredRoles = to.meta.roles as string[] | undefined;
  if (!requiredRoles || requiredRoles.length === 0) return;

  if (!authStore.isAuthenticated) {
    return navigateTo("/login", { redirectCode: 302 });
  }

  if (!requiredRoles.includes(authStore.userRole)) {
    return navigateTo("/dashboard", { redirectCode: 302 });
  }
});
