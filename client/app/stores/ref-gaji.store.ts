import { defineStore } from "pinia";
import type { RefGajiItem, RefGajiGolongan, RefGajiPeraturan } from "~/services/ref-gaji.service";
import { refGajiService } from "~/services/ref-gaji.service";

interface RefGajiState {
  items: RefGajiItem[];
  golongan: RefGajiGolongan[];
  peraturan: RefGajiPeraturan[];
  activeTab: "pns" | "pppk";
  loading: boolean;
  error: string | null;
}

export const useRefGajiStore = defineStore("refGaji", {
  state: (): RefGajiState => ({
    items: [],
    golongan: [],
    peraturan: [],
    activeTab: "pns",
    loading: false,
    error: null,
  }),

  getters: {
    isLoading: (state) => state.loading,

    groupedByGolongan: (state) => {
      const groups: Record<string, RefGajiItem[]> = {};
      for (const item of state.items) {
        const key = item.sub_golongan ? `${item.golongan}${item.sub_golongan}` : item.golongan;
        if (!groups[key]) groups[key] = [];
        groups[key].push(item);
      }
      return groups;
    },

    uniqueGolongan: (state) => {
      const seen = new Set<string>();
      return state.golongan.filter((g) => {
        const key = g.sub_golongan ? `${g.golongan}${g.sub_golongan}` : g.golongan;
        if (seen.has(key)) return false;
        seen.add(key);
        return true;
      });
    },
  },

  actions: {
    async fetchList(jenisAsn: "pns" | "pppk") {
      this.loading = true;
      this.error = null;
      try {
        const res = await refGajiService.fetchList(jenisAsn);
        this.items = res.data.items;
        this.golongan = res.data.golongan;
        this.peraturan = res.data.peraturan;
        this.activeTab = jenisAsn;
      } catch (e: any) {
        this.error = e.data?.message || "Gagal mengambil data referensi gaji";
      } finally {
        this.loading = false;
      }
    },

    setTab(tab: "pns" | "pppk") {
      if (this.activeTab !== tab) {
        this.activeTab = tab;
        this.fetchList(tab);
      }
    },

    clearError() {
      this.error = null;
    },
  },
});
