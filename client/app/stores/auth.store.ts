import { defineStore } from "pinia";
import type { User } from "~/types/api";
import type { ApiResponse } from "~/types/api";

interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  // OAuth registration payload — set when SIM-ASN user is not found
  oauthRegistrationPayload: string | null;
}

export const useAuthStore = defineStore("auth", {
  state: (): AuthState => ({
    user: null,
    token: null,
    isAuthenticated: false,
    oauthRegistrationPayload: null,
  }),

  getters: {
    userRole: (state): string | null => state.user?.role || state.user?.roles?.[0] || null,
    isAdmin: (state) => state.user?.role === "admin" || state.user?.roles?.includes("admin"),
    isVerifikator: (state) =>
      state.user?.role === "verifikator" || state.user?.roles?.includes("verifikator"),
    isOperator: (state) =>
      state.user?.role === "operator" || state.user?.roles?.includes("operator"),
    isLoggedIn: (state) => state.isAuthenticated,
    isSimAsnAuthenticated: (state) => state.user?.is_sim_asn_authenticated ?? false,
    canRegisterFromOAuth: (state) => !!state.oauthRegistrationPayload,
  },

  actions: {
    _persistToken(token: string) {
      this.token = token;
      this.isAuthenticated = true;
      if (import.meta.client) {
        localStorage.setItem("auth_token", token);
      }
      if (import.meta.server) {
        useCookie("auth_token", { maxAge: 60 * 60 * 24 }).value = token;
      }
    },

    _clearToken() {
      this.token = null;
      this.isAuthenticated = false;
      if (import.meta.client) {
        localStorage.removeItem("auth_token");
      }
      if (import.meta.server) {
        useCookie("auth_token").value = null;
      }
    },

    _loadToken() {
      if (import.meta.client && !this.token) {
        this.token = localStorage.getItem("auth_token") || null;
      }
      return this.token;
    },

    async login(credentials: {
      email: string;
      password: string;
      remember_me?: boolean;
    }): Promise<void> {
      const api = useApi();
      const res = await api.post<ApiResponse<{ user: User; token: string }>>(
        "/api/v1/auth/login",
        credentials,
      );
      this.user = res.data.user;
      this.token = res.data.token;
      this.isAuthenticated = true;
      this.oauthRegistrationPayload = null;
      this._persistToken(res.data.token);
    },

    async loginWithToken(token: string): Promise<void> {
      this.token = token;
      this.isAuthenticated = true;
      this.oauthRegistrationPayload = null;
      this._persistToken(token);
      await this.fetchUser();
    },

    async logout(): Promise<void> {
      const api = useApi();
      try {
        await api.post("/api/v1/auth/logout");
      } catch {
        // ignore errors on logout
      }
      this.user = null;
      this.token = null;
      this.isAuthenticated = false;
      this.oauthRegistrationPayload = null;
      this._clearToken();
    },

    async fetchUser(): Promise<void> {
      const token = this._loadToken();
      if (!token) return;

      const api = useApi();
      try {
        const res = await api.get<ApiResponse<User>>("/api/v1/auth/me");
        this.user = res.data;
        this.isAuthenticated = true;
      } catch (e: any) {
        // 401 = token invalid/expired — clear it
        if (e.data?.status === 401) {
          this.user = null;
          this.token = null;
          this.isAuthenticated = false;
          this._clearToken();
        }
      }
    },

    /**
     * Store the OAuth registration payload from the callback URL.
     * The frontend uses this to pre-fill the registration form.
     */
    setOAuthRegistrationPayload(payload: string) {
      this.oauthRegistrationPayload = payload;
    },

    clearOAuthRegistrationPayload() {
      this.oauthRegistrationPayload = null;
    },

    setToken(token: string) {
      this.token = token;
      this.isAuthenticated = true;
    },
  },
});
