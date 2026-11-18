<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    AlertCircle,
    CheckCircle2,
    PieChart,
    Plus,
    Trash2,
} from '@lucide/vue';
import { ref } from 'vue';

interface Category {
    id: number;
    nama: string;
    tipe: 'expense';
    ikon: string;
    warna: string;
}

interface BudgetSummary {
    id: number;
    category_id: number;
    kategori_nama: string;
    kategori_ikon: string;
    kategori_warna: string;
    batas_anggaran: number;
    terpakai: number;
    sisa: number;
    persentase: number;
}

const props = defineProps<{
    budgets: BudgetSummary[];
    expense_categories: Category[];
    filter: {
        bulan: number;
        tahun: number;
    };
    total_batas: number;
    total_terpakai: number;
    total_sisa: number;
}>();

const formatRupiah = (val: number | string) => {
    const num = typeof val === 'string' ? parseFloat(val) : val;
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(num || 0);
};

const namaBulan = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];

const selectedBulan = ref(props.filter.bulan);
const selectedTahun = ref(props.filter.tahun);

const applyFilter = () => {
    router.get('/budgets', {
        bulan: selectedBulan.value,
        tahun: selectedTahun.value,
    }, { preserveState: true, preserveScroll: true });
};

// Modal Set Anggaran
const showModal = ref(false);

const form = useForm({
    category_id: '',
    batas_anggaran: '',
    bulan: props.filter.bulan,
    tahun: props.filter.tahun,
});

const openSetModal = (presetCategoryId?: number, presetBatas?: number) => {
    form.category_id = presetCategoryId ? String(presetCategoryId) : '';
    form.batas_anggaran = presetBatas ? String(presetBatas) : '';
    form.bulan = selectedBulan.value;
    form.tahun = selectedTahun.value;
    showModal.value = true;
};

const submitBudget = () => {
    form.post('/budgets', {
        onSuccess: () => {
            showModal.value = false;
            form.reset();
        },
    });
};

const deleteBudget = (id: number) => {
    if (confirm('Hapus batas anggaran kategori ini?')) {
        router.delete(`/budgets/${id}`);
    }
};

const getProgressBarColor = (pct: number) => {
    if (pct > 100) return 'bg-rose-500 shadow-rose-500/50';
    if (pct > 80) return 'bg-amber-400 shadow-amber-400/50';
    return 'bg-pink-500 shadow-pink-500/50';
};
</script>

<template>
    <div>
        <Head title="Manajemen Anggaran" />

        <div class="space-y-6">
            <!-- Header Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                        <PieChart class="size-6 text-pink-500" />
                        <span>Manajemen Anggaran</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                        Kendalikan pengeluaran bulanan agar tidak melebihi kapasitas finansial Anda.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Filter Selector -->
                    <div class="flex items-center gap-2 bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-white/10 rounded-xl p-1.5 backdrop-blur-md shadow-sm dark:shadow-none">
                        <select
                            v-model="selectedBulan"
                            @change="applyFilter"
                            class="bg-transparent text-xs text-slate-700 dark:text-slate-200 font-medium px-2 py-1 outline-none cursor-pointer"
                        >
                            <option v-for="(nama, idx) in namaBulan" :key="idx" :value="idx + 1" class="bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
                                {{ nama }}
                            </option>
                        </select>

                        <select
                            v-model="selectedTahun"
                            @change="applyFilter"
                            class="bg-transparent text-xs text-slate-700 dark:text-slate-200 font-medium px-2 py-1 outline-none cursor-pointer border-l border-slate-200 dark:border-white/10"
                        >
                            <option v-for="y in [2024, 2025, 2026, 2027]" :key="y" :value="y" class="bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
                                {{ y }}
                            </option>
                        </select>
                    </div>

                    <button
                        @click="openSetModal()"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-white bg-gradient-to-r from-pink-500 to-rose-600 hover:from-pink-600 hover:to-rose-700 shadow-md shadow-pink-500/25 transition transform active:scale-95"
                    >
                        <Plus class="size-4" />
                        <span>Atur Anggaran</span>
                    </button>
                </div>
            </div>

            <!-- Ringkasan Anggaran Bulanan -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-slate-900/70 p-5 backdrop-blur-md shadow-sm dark:shadow-lg transition-colors">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Anggaran Dialokasikan</span>
                    <h2 class="text-2xl font-black text-slate-900 dark:text-white mt-2">{{ formatRupiah(total_batas) }}</h2>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 block">Periode {{ namaBulan[selectedBulan - 1] }} {{ selectedTahun }}</span>
                </div>

                <div class="rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-slate-900/70 p-5 backdrop-blur-md shadow-sm dark:shadow-lg transition-colors">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Terpakai</span>
                    <h2 class="text-2xl font-black text-pink-600 dark:text-pink-400 mt-2">{{ formatRupiah(total_terpakai) }}</h2>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 block">
                        {{ total_batas > 0 ? Math.round((total_terpakai / total_batas) * 100) : 0 }}% dari kuota anggaran
                    </span>
                </div>

                <div class="rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-slate-900/70 p-5 backdrop-blur-md shadow-sm dark:shadow-lg transition-colors">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sisa Anggaran Aman</span>
                    <h2 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-2">{{ formatRupiah(total_sisa) }}</h2>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 block">Tersisa untuk dialokasikan</span>
                </div>
            </div>

            <!-- Daftar Anggaran Kartu -->
            <div class="space-y-4">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Rincian Anggaran per Kategori</h3>

                <div v-if="budgets.length === 0" class="rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-slate-900/70 p-12 text-center text-slate-500 text-sm transition-colors">
                    Belum ada kategori yang memiliki batasan anggaran pada bulan {{ namaBulan[selectedBulan - 1] }} {{ selectedTahun }}.
                    <div class="mt-3">
                        <button
                            @click="openSetModal()"
                            class="px-4 py-2 rounded-xl bg-pink-500/15 text-pink-600 dark:text-pink-400 border border-pink-500/30 text-xs font-semibold hover:bg-pink-500/25 transition"
                        >
                            + Atur Anggaran Sekarang
                        </button>
                    </div>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="b in budgets"
                        :key="b.id"
                        class="rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-slate-900/70 p-5 backdrop-blur-md shadow-sm dark:shadow-lg flex flex-col justify-between relative overflow-hidden transition-colors"
                    >
                        <div>
                            <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-white/10">
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="size-3.5 rounded-full"
                                        :style="{ backgroundColor: b.kategori_warna || '#ec4899' }"
                                    ></div>
                                    <h4 class="font-bold text-slate-900 dark:text-white text-base">{{ b.kategori_nama }}</h4>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button
                                        @click="openSetModal(b.category_id, b.batas_anggaran)"
                                        class="text-xs text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800"
                                        title="Ubah Anggaran"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        @click="deleteBudget(b.id)"
                                        class="text-xs text-rose-500 hover:text-rose-600 dark:text-rose-400 dark:hover:text-rose-300 p-1.5 rounded-lg hover:bg-rose-500/10"
                                        title="Hapus Anggaran"
                                    >
                                        <Trash2 class="size-3.5" />
                                    </button>
                                </div>
                            </div>

                            <div class="mt-4 space-y-3">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-500 dark:text-slate-400">Terpakai:</span>
                                    <span class="font-bold" :class="b.persentase > 100 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-slate-200'">
                                        {{ formatRupiah(b.terpakai) }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-500 dark:text-slate-400">Batas Anggaran:</span>
                                    <span class="font-bold text-slate-900 dark:text-slate-200">{{ formatRupiah(b.batas_anggaran) }}</span>
                                </div>

                                <!-- Progress Bar -->
                                <div class="space-y-1">
                                    <div class="h-2.5 w-full bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                                        <div
                                            class="h-full rounded-full transition-all duration-500"
                                            :class="getProgressBarColor(b.persentase)"
                                            :style="{ width: `${Math.min(100, b.persentase)}%` }"
                                        ></div>
                                    </div>
                                    <div class="flex items-center justify-between text-[11px]">
                                        <span class="text-slate-500">Sisa: {{ formatRupiah(b.sisa) }}</span>
                                        <span class="font-bold" :class="b.persentase > 100 ? 'text-rose-600 dark:text-rose-400' : b.persentase > 80 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-700 dark:text-slate-300'">
                                            {{ b.persentase }}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Warning Alert if >80% or >100% -->
                        <div v-if="b.persentase > 100" class="mt-4 p-2.5 rounded-xl bg-rose-500/10 dark:bg-rose-500/15 border border-rose-500/30 text-rose-600 dark:text-rose-300 text-xs flex items-center gap-2">
                            <AlertCircle class="size-4 shrink-0" />
                            <span>Anggaran terlampaui sebesar {{ formatRupiah(b.terpakai - b.batas_anggaran) }}!</span>
                        </div>
                        <div v-else-if="b.persentase >= 80" class="mt-4 p-2.5 rounded-xl bg-amber-500/10 dark:bg-amber-500/15 border border-amber-500/30 text-amber-600 dark:text-amber-300 text-xs flex items-center gap-2">
                            <AlertCircle class="size-4 shrink-0" />
                            <span>Peringatan: Pemakaian anggaran telah mencapai {{ b.persentase }}%!</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Set Anggaran -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-[#0f172a] p-6 shadow-2xl relative">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-white/10">
                    <h3 class="font-bold text-base text-slate-900 dark:text-white">Atur Batas Anggaran Kategori</h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-700 dark:hover:text-white">✕</button>
                </div>

                <form @submit.prevent="submitBudget" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Kategori Pengeluaran</label>
                        <select
                            v-model="form.category_id"
                            required
                            class="w-full rounded-xl border border-slate-300 dark:border-white/10 bg-slate-50 dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 outline-none focus:border-pink-500"
                        >
                            <option value="" disabled>Pilih Kategori Pengeluaran</option>
                            <option v-for="c in expense_categories" :key="c.id" :value="c.id">
                                {{ c.nama }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Batas Maksimal Anggaran (Rp)</label>
                        <input
                            v-model="form.batas_anggaran"
                            type="number"
                            min="1"
                            step="any"
                            required
                            placeholder="cth: 1500000"
                            class="w-full rounded-xl border border-slate-300 dark:border-white/10 bg-slate-50 dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 outline-none focus:border-pink-500"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Bulan</label>
                            <select
                                v-model="form.bulan"
                                required
                                class="w-full rounded-xl border border-slate-300 dark:border-white/10 bg-slate-50 dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 outline-none focus:border-pink-500"
                            >
                                <option v-for="(nama, idx) in namaBulan" :key="idx" :value="idx + 1">
                                    {{ nama }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Tahun</label>
                            <input
                                v-model="form.tahun"
                                type="number"
                                required
                                class="w-full rounded-xl border border-slate-300 dark:border-white/10 bg-slate-50 dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 outline-none focus:border-pink-500"
                            />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-200 dark:border-white/10">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-slate-800"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2 rounded-xl text-xs font-semibold text-white bg-pink-500 hover:bg-pink-600 disabled:opacity-50 transition"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Anggaran' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
