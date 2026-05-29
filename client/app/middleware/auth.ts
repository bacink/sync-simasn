export default defineNuxtRouteMiddleware((to) => {
  const authStore = useAuthStore()

  if (!authStore.isAuthenticated && to.path !== '/auth/register') {
    return navigateTo('/login', { redirectCode: 302 })
  }
})
