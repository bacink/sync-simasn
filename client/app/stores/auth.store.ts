import { defineStore } from 'pinia'
import type { User } from '~/types/api'
import type { ApiResponse } from '~/types/api'

interface AuthState {
  user: User | null
  token: string | null
  isAuthenticated: boolean
}

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    user: null,
    token: null,
    isAuthenticated: false
  }),

  getters: {
    userRole: (state): string | null => state.user?.role || null,
    isAdmin: (state) => state.user?.role === 'admin',
    isVerifikator: (state) => state.user?.role === 'verifikator',
    isOperator: (state) => state.user?.role === 'operator',
    isLoggedIn: (state) => state.isAuthenticated,
  },

  actions: {
    async login(credentials: { email: string; password: string }): Promise<void> {
      const api = useApi()
      const res = await api.post<ApiResponse<{ user: User; token: string }>>('/auth/login', credentials)
      this.user = res.data.user
      this.token = res.data.token
      this.isAuthenticated = true
      if (import.meta.client) {
        localStorage.setItem('auth_token', res.data.token)
      }
      if (import.meta.server) {
        useCookie('auth_token', { maxAge: 60 * 60 * 24 }).value = res.data.token
      }
    },

    async logout(): Promise<void> {
      const api = useApi()
      try {
        await api.post('/auth/logout')
      }
      catch {
        // ignore errors on logout
      }
      this.user = null
      this.token = null
      this.isAuthenticated = false
      if (import.meta.client) {
        localStorage.removeItem('auth_token')
      }
      if (import.meta.server) {
        useCookie('auth_token').value = null
      }
    },

    async fetchUser(): Promise<void> {
      if (!this.token && import.meta.client) {
        this.token = localStorage.getItem('auth_token') || null
      }
      if (!this.token) return

      const api = useApi()
      try {
        const res = await api.get<ApiResponse<User>>('/auth/me')
        this.user = res.data
        this.isAuthenticated = true
      }
      catch {
        this.token = null
        this.isAuthenticated = false
        if (import.meta.client) {
          localStorage.removeItem('auth_token')
        }
      }
    },

    setToken(token: string) {
      this.token = token
      this.isAuthenticated = true
    }
  }
})
