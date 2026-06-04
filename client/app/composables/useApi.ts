import type { ApiError } from "~/types/api";

export interface RequestOptions extends RequestInit {
  params?: Record<string, string | number | boolean | undefined | null>;
}

export function useApi() {
  const config = useRuntimeConfig();
  const authStore = useAuthStore();
  const baseURL = (config.public.apiBase as string) || "http://localhost:8000/api";

  function getToken(): string | null {
    if (import.meta.client) {
      return localStorage.getItem("auth_token");
    }
    return authStore.token;
  }

  async function request<T>(
    method: string,
    endpoint: string,
    options: RequestOptions = {},
  ): Promise<T> {
    const { params, ...fetchOptions } = options;
    const url = new URL(`${baseURL}${endpoint}`, baseURL);

    if (params) {
      for (const [key, value] of Object.entries(params)) {
        if (value !== undefined && value !== null && value !== "") {
          url.searchParams.append(key, String(value));
        }
      }
    }

    const token = getToken();

    const headers: HeadersInit = {
      "Content-Type": "application/json",
      Accept: "application/json",
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...fetchOptions.headers,
    };

    const response = await fetch(url.toString(), {
      method,
      headers,
      ...fetchOptions,
    });

    if (!response.ok) {
      const error: ApiError = {
        status: response.status,
        message: response.statusText,
        errors: {},
      };

      try {
        const body = await response.json();
        error.message = body.message || body.error || response.statusText;
        error.errors = body.errors || {};
      } catch {
        // use status text as fallback
      }

      // On 401, clear token so next request doesn't send a stale token
      if (response.status === 401) {
        const authStore = useAuthStore();
        authStore.token = null;
        authStore.isAuthenticated = false;
        if (import.meta.client) {
          localStorage.removeItem("auth_token");
        }
      }

      throw createError({
        statusCode: response.status,
        message: error.message,
        data: error,
      });
    }

    if (response.status === 204) {
      return {} as T;
    }

    return response.json() as Promise<T>;
  }

  function get<T>(endpoint: string, params?: RequestOptions["params"]): Promise<T> {
    return request<T>("GET", endpoint, { params });
  }

  function post<T>(endpoint: string, data?: unknown): Promise<T> {
    return request<T>("POST", endpoint, {
      body: data !== undefined ? JSON.stringify(data) : undefined,
    });
  }

  function postFormData<T>(
    endpoint: string,
    formData: FormData,
    token?: string | null,
  ): Promise<T> {
    return request<T>("POST", endpoint, {
      body: formData,
      headers: {
        "Content-Type": "multipart/form-data",
        Accept: "application/json",
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      },
    });
  }

  function put<T>(endpoint: string, data?: unknown): Promise<T> {
    return request<T>("PUT", endpoint, {
      body: data !== undefined ? JSON.stringify(data) : undefined,
    });
  }

  function del<T>(endpoint: string): Promise<T> {
    return request<T>("DELETE", endpoint);
  }

  return {
    request,
    get,
    post,
    postFormData,
    put,
    del,
    baseURL,
  };
}
