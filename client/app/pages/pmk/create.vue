<script setup lang="ts">
import { pmkService } from "~/services/pmk.service";
import type { PmkFormData } from "~/types/pmk";

const router = useRouter();

const form = ref<PmkFormData>({
  nip: "",
  masa_kerja_baru_tahun: 0,
  masa_kerja_baru_bulan: 0,
  dasar_pmk: "",
});

const loading = ref(false);
const error = ref<string | null>(null);
const file = ref<File | null>(null);

async function submit() {
  loading.value = true;
  error.value = null;
  try {
    await pmkService.createPmk(form.value);
    router.push("/pmk");
  } catch (e: any) {
    error.value = e.data?.message || "Gagal membuat PMK";
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Input PMK Baru</h1>

    <div class="max-w-2xl bg-white rounded-lg shadow-sm p-6">
      <form @submit.prevent="submit">
        <!-- NIP -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">NIP</label>
          <input
            v-model="form.nip"
            type="text"
            placeholder="Masukkan NIP..."
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
            required
          />
        </div>

        <!-- Masa Kerja Baru -->
        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2"
              >Masa Kerja Baru (Tahun)</label
            >
            <input
              v-model.number="form.masa_kerja_baru_tahun"
              type="number"
              min="0"
              max="40"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
              required
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2"
              >Masa Kerja Baru (Bulan)</label
            >
            <input
              v-model.number="form.masa_kerja_baru_bulan"
              type="number"
              min="0"
              max="11"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
              required
            />
          </div>
        </div>

        <!-- Dasar PMK -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Dasar PMK</label>
          <input
            v-model="form.dasar_pmk"
            type="text"
            placeholder="Contoh: PMK No. 123/2024"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
            required
          />
        </div>

        <!-- Error -->
        <div v-if="error" class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg">
          {{ error }}
        </div>

        <!-- Actions -->
        <div class="flex gap-4">
          <button
            type="submit"
            :disabled="loading"
            class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50"
          >
            {{ loading ? "Menyimpan..." : "Simpan" }}
          </button>
          <NuxtLink
            to="/pmk"
            class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
          >
            Batal
          </NuxtLink>
        </div>
      </form>
    </div>
  </div>
</template>
