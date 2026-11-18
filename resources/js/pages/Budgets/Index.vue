<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle2, PieChart, Plus, Trash2 } from '@lucide/vue';
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
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
];

const selectedBulan = ref(props.filter.bulan);
const selectedTahun = ref(props.filter.tahun);

const applyFilter = () => {
    router.get(
        '/budgets',
        {
            bulan: selectedBulan.value,
            tahun: selectedTahun.value,
        },
        { preserveState: true, preserveScroll: true },
    );
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
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1
                        class="flex items-center gap-2 text-2xl font-bold tracking-tight text-slate-900 dark:text-white"
                    >
                        <PieChart class="size-6 text-pink-500" />
                        <span>Manajemen Anggaran</span>
                    </h1>
                    <p
                        class="text-xs text-slate-500 sm:text-sm dark:text-slate-400"
                    >
                        Kendalikan pengeluaran bulanan agar tidak melebihi
                        kapasitas finansial Anda.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Filter Selector -->
                    <div
                        class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white p-1.5 shadow-sm backdrop-blur-md dark:border-white/10 dark:bg-slate-900/80 dark:shadow-none"
                    >
                        <select
                            v-model="selectedBulan"
                            @change="applyFilter"
                            class="cursor-pointer bg-transparent px-2 py-1 text-xs font-medium text-slate-700 outline-none dark:text-slate-200"
                        >
                            <option
                                v-for="(nama, idx) in namaBulan"
                                :key="idx"
                                :value="idx + 1"
                                class="bg-white text-slate-900 dark:bg-slate-900 dark:text-white"
                            >
                                {{ nama }}
                            </option>
                        </select>

                        <select
                            v-model="selectedTahun"
                            @change="applyFilter"
                            class="cursor-pointer border-l border-slate-200 bg-transparent px-2 py-1 text-xs font-medium text-slate-700 outline-none dark:border-white/10 dark:text-slate-200"
                        >
                            <option
                                v-for="y in [2024, 2025, 2026, 2027]"
                                :key="y"
                                :value="y"
                                class="bg-white text-slate-900 dark:bg-slate-900 dark:text-white"
                            >
                                {{ y }}
                            </option>
                        </select>
                    </div>

                    <button
                        @click="openSetModal()"
                        class="inline-flex transform items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-pink-500 to-rose-600 px-4 py-2 text-xs font-semibold text-white shadow-md shadow-pink-500/25 transition hover:from-pink-600 hover:to-rose-700 active:scale-95 sm:text-sm"
                    >
                        <Plus class="size-4" />
                        <span>Atur Anggaran</span>
                    </button>
                </div>
            </div>

            <!-- Ringkasan Anggaran Bulanan -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm backdrop-blur-md transition-colors dark:border-white/10 dark:bg-slate-900/70 dark:shadow-lg"
                >
                    <span
                        class="text-xs font-semibold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                        >Total Anggaran Dialokasikan</span
                    >
                    <h2
                        class="mt-2 text-2xl font-black text-slate-900 dark:text-white"
                    >
                        {{ formatRupiah(total_batas) }}
                    </h2>
                    <span
                        class="mt-1 block text-[11px] text-slate-500 dark:text-slate-400"
                        >Periode {{ namaBulan[selectedBulan - 1] }}
                        {{ selectedTahun }}</span
                    >
                </div>

                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm backdrop-blur-md transition-colors dark:border-white/10 dark:bg-slate-900/70 dark:shadow-lg"
                >
                    <span
                        class="text-xs font-semibold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                        >Total Terpakai</span
                    >
                    <h2
                        class="mt-2 text-2xl font-black text-pink-600 dark:text-pink-400"
                    >
                        {{ formatRupiah(total_terpakai) }}
                    </h2>
                    <span
                        class="mt-1 block text-[11px] text-slate-500 dark:text-slate-400"
                    >
                        {{
                            total_batas > 0
                                ? Math.round(
                                      (total_terpakai / total_batas) * 100,
                                  )
                                : 0
                        }}% dari kuota anggaran
                    </span>
                </div>

                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm backdrop-blur-md transition-colors dark:border-white/10 dark:bg-slate-900/70 dark:shadow-lg"
                >
                    <span
                        class="text-xs font-semibold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                        >Sisa Anggaran Aman</span
                    >
                    <h2
                        class="mt-2 text-2xl font-black text-emerald-600 dark:text-emerald-400"
                    >
                        {{ formatRupiah(total_sisa) }}
                    </h2>
                    <span
                        class="mt-1 block text-[11px] text-slate-500 dark:text-slate-400"
                        >Tersisa untuk dialokasikan</span
                    >
                </div>
            </div>

            <!-- Daftar Anggaran Kartu -->
            <div class="space-y-4">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                    Rincian Anggaran per Kategori
                </h3>

                <div
                    v-if="budgets.length === 0"
                    class="rounded-2xl border border-slate-200 bg-white p-12 text-center text-sm text-slate-500 transition-colors dark:border-white/10 dark:bg-slate-900/70"
                >
                    Belum ada kategori yang memiliki batasan anggaran pada bulan
                    {{ namaBulan[selectedBulan - 1] }} {{ selectedTahun }}.
                    <div class="mt-3">
                        <button
                            @click="openSetModal()"
                            class="rounded-xl border border-pink-500/30 bg-pink-500/15 px-4 py-2 text-xs font-semibold text-pink-600 transition hover:bg-pink-500/25 dark:text-pink-400"
                        >
                            + Atur Anggaran Sekarang
                        </button>
                    </div>
                </div>

                <div
                    v-else
                    class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3"
                >
                    <div
                        v-for="b in budgets"
                        :key="b.id"
                        class="relative flex flex-col justify-between overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm backdrop-blur-md transition-colors dark:border-white/10 dark:bg-slate-900/70 dark:shadow-lg"
                    >
                        <div>
                            <div
                                class="flex items-center justify-between border-b border-slate-200 pb-3 dark:border-white/10"
                            >
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="size-3.5 rounded-full"
                                        :style="{
                                            backgroundColor:
                                                b.kategori_warna || '#ec4899',
                                        }"
                                    ></div>
                                    <h4
                                        class="text-base font-bold text-slate-900 dark:text-white"
                                    >
                                        {{ b.kategori_nama }}
                                    </h4>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button
                                        @click="
                                            openSetModal(
                                                b.category_id,
                                                b.batas_anggaran,
                                            )
                                        "
                                        class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs text-slate-600 hover:text-slate-900 dark:bg-slate-800 dark:text-slate-400 dark:hover:text-white"
                                        title="Ubah Anggaran"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        @click="deleteBudget(b.id)"
                                        class="rounded-lg p-1.5 text-xs text-rose-500 hover:bg-rose-500/10 hover:text-rose-600 dark:text-rose-400 dark:hover:text-rose-300"
                                        title="Hapus Anggaran"
                                    >
                                        <Trash2 class="size-3.5" />
                                    </button>
                                </div>
                            </div>

                            <div class="mt-4 space-y-3">
                                <div
                                    class="flex items-center justify-between text-xs"
                                >
                                    <span
                                        class="text-slate-500 dark:text-slate-400"
                                        >Terpakai:</span
                                    >
                                    <span
                                        class="font-bold"
                                        :class="
                                            b.persentase > 100
                                                ? 'text-rose-600 dark:text-rose-400'
                                                : 'text-slate-900 dark:text-slate-200'
                                        "
                                    >
                                        {{ formatRupiah(b.terpakai) }}
                                    </span>
                                </div>

                                <div
                                    class="flex items-center justify-between text-xs"
                                >
                                    <span
                                        class="text-slate-500 dark:text-slate-400"
                                        >Batas Anggaran:</span
                                    >
                                    <span
                                        class="font-bold text-slate-900 dark:text-slate-200"
                                        >{{
                                            formatRupiah(b.batas_anggaran)
                                        }}</span
                                    >
                                </div>

                                <!-- Progress Bar -->
                                <div class="space-y-1">
                                    <div
                                        class="h-2.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800"
                                    >
                                        <div
                                            class="h-full rounded-full transition-all duration-500"
                                            :class="
                                                getProgressBarColor(
                                                    b.persentase,
                                                )
                                            "
                                            :style="{
                                                width: `${Math.min(100, b.persentase)}%`,
                                            }"
                                        ></div>
                                    </div>
                                    <div
                                        class="flex items-center justify-between text-[11px]"
                                    >
                                        <span class="text-slate-500"
                                            >Sisa:
                                            {{ formatRupiah(b.sisa) }}</span
                                        >
                                        <span
                                            class="font-bold"
                                            :class="
                                                b.persentase > 100
                                                    ? 'text-rose-600 dark:text-rose-400'
                                                    : b.persentase > 80
                                                      ? 'text-amber-600 dark:text-amber-400'
                                                      : 'text-slate-700 dark:text-slate-300'
                                            "
                                        >
                                            {{ b.persentase }}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Warning Alert if >80% or >100% -->
                        <div
                            v-if="b.persentase > 100"
                            class="mt-4 flex items-center gap-2 rounded-xl border border-rose-500/30 bg-rose-500/10 p-2.5 text-xs text-rose-600 dark:bg-rose-500/15 dark:text-rose-300"
                        >
                            <AlertCircle class="size-4 shrink-0" />
                            <span
                                >Anggaran terlampaui sebesar
                                {{
                                    formatRupiah(b.terpakai - b.batas_anggaran)
                                }}!</span
                            >
                        </div>
                        <div
                            v-else-if="b.persentase >= 80"
                            class="mt-4 flex items-center gap-2 rounded-xl border border-amber-500/30 bg-amber-500/10 p-2.5 text-xs text-amber-600 dark:bg-amber-500/15 dark:text-amber-300"
                        >
                            <AlertCircle class="size-4 shrink-0" />
                            <span
                                >Peringatan: Pemakaian anggaran telah mencapai
                                {{ b.persentase }}%!</span
                            >
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Set Anggaran -->
        <div
            v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
        >
            <div
                class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-white/10 dark:bg-[#0f172a]"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-200 pb-3 dark:border-white/10"
                >
                    <h3
                        class="text-base font-bold text-slate-900 dark:text-white"
                    >
                        Atur Batas Anggaran Kategori
                    </h3>
                    <button
                        @click="showModal = false"
                        class="text-slate-400 hover:text-slate-700 dark:hover:text-white"
                    >
                        ✕
                    </button>
                </div>

                <form @submit.prevent="submitBudget" class="mt-4 space-y-4">
                    <div>
                        <label
                            class="mb-1 block text-xs font-medium text-slate-700 dark:text-slate-300"
                            >Kategori Pengeluaran</label
                        >
                        <select
                            v-model="form.category_id"
                            required
                            class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-900 outline-none focus:border-pink-500 dark:border-white/10 dark:bg-slate-900 dark:text-slate-100"
                        >
                            <option value="" disabled>
                                Pilih Kategori Pengeluaran
                            </option>
                            <option
                                v-for="c in expense_categories"
                                :key="c.id"
                                :value="c.id"
                            >
                                {{ c.nama }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-xs font-medium text-slate-700 dark:text-slate-300"
                            >Batas Maksimal Anggaran (Rp)</label
                        >
                        <input
                            v-model="form.batas_anggaran"
                            type="number"
                            min="1"
                            step="any"
                            required
                            placeholder="cth: 1500000"
                            class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-900 outline-none focus:border-pink-500 dark:border-white/10 dark:bg-slate-900 dark:text-slate-100"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label
                                class="mb-1 block text-xs font-medium text-slate-700 dark:text-slate-300"
                                >Bulan</label
                            >
                            <select
                                v-model="form.bulan"
                                required
                                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-900 outline-none focus:border-pink-500 dark:border-white/10 dark:bg-slate-900 dark:text-slate-100"
                            >
                                <option
                                    v-for="(nama, idx) in namaBulan"
                                    :key="idx"
                                    :value="idx + 1"
                                >
                                    {{ nama }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-xs font-medium text-slate-700 dark:text-slate-300"
                                >Tahun</label
                            >
                            <input
                                v-model="form.tahun"
                                type="number"
                                required
                                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-900 outline-none focus:border-pink-500 dark:border-white/10 dark:bg-slate-900 dark:text-slate-100"
                            />
                        </div>
                    </div>

                    <div
                        class="flex justify-end gap-2 border-t border-slate-200 pt-3 dark:border-white/10"
                    >
                        <button
                            type="button"
                            @click="showModal = false"
                            class="rounded-xl bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 dark:bg-slate-800 dark:text-slate-400 dark:hover:text-white"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-xl bg-pink-500 px-5 py-2 text-xs font-semibold text-white transition hover:bg-pink-600 disabled:opacity-50"
                        >
                            {{
                                form.processing
                                    ? 'Menyimpan...'
                                    : 'Simpan Anggaran'
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
