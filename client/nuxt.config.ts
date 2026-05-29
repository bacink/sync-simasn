export default defineNuxtConfig({
  compatibilityDate: "2025-07-15",
  devtools: { enabled: true },

  ssr: false,

  modules: [
    "@nuxt/icon",
    "@nuxt/image",
    "@nuxt/test-utils",
    "@nuxtjs/tailwindcss",
    "@pinia/nuxt",
    "dayjs-nuxt",
    "nuxt-headlessui",
    "nuxt-mcp-dev",
  ],

  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || "http://localhost:8000/api",
    },
  },

  typescript: {
    strict: true,
  },

  mcp: {
    dev: {
      enabled: true,
    },
  },

  vite: {
    server: {
      allowedHosts: ["localhost", "kgb.test"],
      watch: {
        usePolling: true,
      },
    },
  },
});
