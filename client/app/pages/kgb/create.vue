<script setup lang="ts">
import { useKgbStore } from "~/stores/kgb.store";
import { simasnService } from "~/services/simasn.service";
import type { Pegawai } from "~/types/simasn";
import type { ApiError } from "~/types/api";

const router = useRouter();
const store = useKgbStore();

const form = ref({
  pegawai_id: 0,
  masa_kerja_tahun: 0,
  masa_kerja_bulan: 0,
  jenis_kgb: "reguler" as "reguler" | "penyesuaian",
});

const searchQuery = ref("");
const pegawaiOptions = ref<Pegawai[]>([]);
const selectedPegawai = ref<Pegawai | null>(null);

async function searchPegawai() {
  if (searchQuery.value.length < 3) {
    pegawaiOptions.value = [];
    return;
  }
  const res = await simasnService.fetchPegawaiList({ search: searchQuery.value });
  pegawaiOptions.value = res.data;
}

async function submit() {
  if (!form.value.pegawai_id) return;

  try {
    await store.generateDraft(
      form.value.pegawai_id,
      form.value.masa_kerja_tahun,
      form.value.masa_kerja_bulan,
      form.value.jenis_kgb,
    );
    router.push("/kgb");
  } catch (e: unknown) {
    const err = e as { data?: ApiError };
    store.error = err.data?.message || "Gagal membuat KGB";
  }
}

function selectPegawai(p: Pegawai) {
  selectedPegawai.value = p;
  form.value.pegawai_id = p.id;
  pegawaiOptions.value = [];
  searchQuery.value = "";
}
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Buat KGB Baru</h1>

    <div class="max-w-2xl bg-white rounded-lg shadow-sm p-6">
      <form @submit.prevent="submit">
        <!-- Pegawai Search -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Pegawai</label>
          <div class="relative">
            <input
              v-model="searchQuery"
              @input="searchPegawai"
              type="text"
              placeholder="Cari nama atau NIP..."
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
            />
            <ul
              v-if="pegawaiOptions.length > 0"
              class="absolute z-10 w-full mt-1 bg-white border rounded-lg shadow-lg max-h-60 overflow-auto"
            >
              <li
                v-for="p in pegawaiOptions"
                :key="p.id"
                @click="selectPegawai(p)"
                class="px-4 py-2 cursor-pointer hover:bg-gray-50"
              >
                <div class="font-medium">{{ p.nama }}</div>
                <div class="text-sm text-gray-500">{{ p.nip }} - {{ p.jabatan_nama }}</div>
              </li>
            </ul>
          </div>
          <div v-if="selectedPegawai" class="mt-2 p-3 bg-gray-50 rounded-lg">
            <div class="font-medium">{{ selectedPegawai.nama }}</div>
            <div class="text-sm text-gray-600">
              {{ selectedPegawai.nip }} - {{ selectedPegawai.jabatan_nama }}
            </div>
          </div>
        </div>

        <!-- Masa Kerja -->
        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Masa Kerja (Tahun)</label>
            <input
              v-model.number="form.masa_kerja_tahun"
              type="number"
              min="0"
              max="32"
              class="w-full px-4 py-2 border rounded-lg"
              required
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Masa Kerja (Bulan)</label>
            <input
              v-model.number="form.masa_kerja_bulan"
              type="number"
              min="0"
              max="11"
              class="w-full px-4 py-2 border rounded-lg"
              required
            />
          </div>
        </div>

        <!-- Jenis KGB -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Jenis KGB</label>
          <select v-model="form.jenis_kgb" class="w-full px-4 py-2 border rounded-lg">
            <option value="reguler">Reguler</option>
            <option value="penyesuaian">Penyesuaian</option>
          </select>
        </div>

        <!-- Error -->
        <div v-if="store.error" class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg">
          {{ store.error }}
        </div>

        <!-- Actions -->
        <div class="flex gap-4">
          <button
            type="submit"
            :disabled="store.isSubmitting || !form.pegawai_id"
            class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50"
          >
            {{ store.isSubmitting ? "Menyimpan..." : "Simpan" }}
          </button>
          <NuxtLink
            to="/kgb"
            class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
          >
            Batal
          </NuxtLink>
        </div>
      </form>
    </div>
  </div>
</template>
