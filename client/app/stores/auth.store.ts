import { defineStore } from "pinia";
import type { User, AuthResponse } from "~/types/api";
import type { ApiResponse } from "~/types/api";

interface OAuthRegistrationPayload {
  sim_asn_user_id: string
  name: string
  email: string
  opd_id: number | null
  sim_asn_token: {
    access_token: string
    refresh_token?: string
    expires_at?: string | null
  }
}

interface AuthState {
  user: User | null
  token: string | null
  isAuthenticated: boolean
  oauthRegistrationPayload: OAuthRegistrationPayload | null
}

export const useAuthStore = defineStore("auth", {
  state: (): AuthState => ({
    user: null,
    token: null,
    isAuthenticated: false,
    oauthRegistrationPayload: null,
  }),

  getters: {
    userRole: (state): string | null => {
      if (state.user?.role) return state.user.role
      if (state.user?.roles?.length) return state.user.roles[0]
      return null
    },
    isAdmin: (state): boolean => state.user?.role === 'admin' || state.user?.roles?.includes('admin') || false,
    isVerifikator: (state): boolean => state.user?.role === 'verifikator' || state.user?.roles?.includes('verifikator') || false,
    isOperator: (state): boolean => state.user?.role === 'operator' || state.user?.roles?.includes('operator') || false,
    isLoggedIn: (state): boolean => state.isAuthenticated,
    isSimAsnAuthenticated: (state): boolean => state.user?.is_sim_asn_authenticated || false,
  },

  actions: {
    _persistToken(token: string): void {
      this.token = token
      this.isAuthenticated = true
      if (import.meta.client) {
        localStorage.setItem('auth_token', token)
      }
      if (import.meta.server) {
        useCookie('auth_token', { maxAge: 60 * 60 * 24 }).value = token
      }
    },

    _clearToken(): void {
      this.token = null
      this.isAuthenticated = false
      this.user = null
      if (import.meta.client) {
        localStorage.removeItem('auth_token')
      }
      if (import.meta.server) {
        useCookie('auth_token').value = null
      }
    },

    _loadToken(): void {
      if (!this.token && import.meta.client) {
        const stored = localStorage.getItem('auth_token')
        if (stored) {
          this.token = stored
        }
      }
    },

    async login(credentials: { email: string; password: string }): Promise<void> {
      const api = useApi()
      const res = await api.post<ApiResponse<AuthResponse>>('/api/v1/auth/login', credentials)
      this.user = res.data.user
      this._persistToken(res.data.token)
    },

    async loginWithToken(token: string): Promise<void> {
      this._persistToken(token)
      await this.fetchUser()
    },

    async logout(): Promise<void> {
      const api = useApi()
      try {
        await api.post('/api/v1/auth/logout')
      } catch {
        // ignore errors on logout
      }
      this._clearToken()
    },

    async fetchUser(): Promise<void> {
      this._loadToken()
      if (!this.token) return

      const api = useApi()
      try {
        const res = await api.get<ApiResponse<User>>('/api/v1/auth/me')
        this.user = res.data
        this.isAuthenticated = true
      } catch {
        this._clearToken()
      }
    },

    clearToken(): void {
      this._clearToken()
    },

    setOAuthRegistrationPayload(encodedPayload: string): void {
      try {
        const decoded = JSON.parse(atob(encodedPayload)) as OAuthRegistrationPayload
        this.oauthRegistrationPayload = decoded
      } catch {
        this.oauthRegistrationPayload = null
      }
    },

    clearOAuthRegistrationPayload(): void {
      this.oauthRegistrationPayload = null
    },
  },
})
