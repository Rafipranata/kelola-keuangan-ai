<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowDownRight,
    ArrowUpRight,
    Bot,
    ChevronRight,
    DollarSign,
    PieChart,
    Plus,
    RefreshCw,
    Sparkles,
    TrendingDown,
    TrendingUp,
    Wallet,
} from '@lucide/vue';
import { computed, ref } from 'vue';

interface Category {
    id: number;
    nama: string;
    tipe: 'income' | 'expense';
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

interface ExpenseCategorySummary {
    id: number;
    nama: string;
    ikon: string;
    warna: string;
    total: number;
    persentase: number;
}

interface Transaction {
    id: number;
    user_id: number;
    category_id: number;
    nominal: string | number;
    deskripsi: string;
    tanggal_transaksi: string;
    metode_pembayaran: string;
    is_ai_generated: boolean;
    category?: Category;
}

const props = defineProps<{
    filter: {
        bulan: number;
        tahun: number;
    };
    summary: {
        total_saldo: number;
        pemasukan_bulan_ini: number;
        pengeluaran_bulan_ini: number;
        total_batas_anggaran: number;
        total_anggaran_terpakai: number;
        sisa_anggaran_total: number;
        persentase_anggaran_total: number;
    };
    budgets: BudgetSummary[];
    expenses_by_category: ExpenseCategorySummary[];
    recent_transactions: Transaction[];
    categories: Category[];
}>();

// Formatter Rupiah
const formatRupiah = (val: number | string) => {
    const num = typeof val === 'string' ? parseFloat(val) : val;
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(num || 0);
};

// Bulan names
const namaBulan = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];

const selectedBulan = ref(props.filter.bulan);
const selectedTahun = ref(props.filter.tahun);

const applyFilter = () => {
    router.get('/dashboard', {
        bulan: selectedBulan.value,
        tahun: selectedTahun.value,
    }, { preserveState: true, preserveScroll: true });
};

const getCsrfToken = () => {
    const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
    if (meta && meta.content) return meta.content;
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
};

// Quick Smart AI Box in Dashboard
const aiPrompt = ref('');
const isProcessingAi = ref(false);
const aiToastMessage = ref<string | null>(null);
const aiErrorMessage = ref<string | null>(null);

const submitAiTransaction = async () => {
    if (!aiPrompt.value.trim() || isProcessingAi.value) return;
    isProcessingAi.value = true;
    aiToastMessage.value = null;
    aiErrorMessage.value = null;

    try {
        const res = await fetch('/ai/parse-transaction', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-XSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({ prompt: aiPrompt.value }),
        });

        const data = await res.json();
        if (res.ok && data.success) {
            aiToastMessage.value = `Berhasil disimpan: ${data.data.deskripsi} (${formatRupiah(data.data.nominal)})`;
            aiPrompt.value = '';
            // Refresh data
            router.reload({ only: ['summary', 'budgets', 'expenses_by_category', 'recent_transactions'] });
        } else {
            aiErrorMessage.value = data.message || 'Gagal memproses transaksi dengan AI.';
        }
    } catch (err: any) {
        aiErrorMessage.value = err.message || 'Terjadi kesalahan sistem.';
    } finally {
        isProcessingAi.value = false;
    }
};

// AI Financial Advisor
const isGeneratingAdvice = ref(false);
const aiAdvice = ref<string | null>(null);
const adviceError = ref<string | null>(null);

const formattedAdvice = computed(() => {
    if (!aiAdvice.value) return '';
    // Escape HTML dasar untuk keamanan
    let text = aiAdvice.value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

    // Ubah bold markdown: **teks** menjadi <strong class="text-white font-bold">teks</strong>
    text = text.replace(/\*\*(.*?)\*\*/g, '<strong class="text-white font-bold">$1</strong>');

    // Ubah italic markdown: *teks* atau _teks_ menjadi <em class="text-pink-300 not-italic font-medium">$1</em>
    text = text.replace(/\*(.*?)\*/g, '<em class="text-pink-300 not-italic font-medium">$1</em>');

    return text;
});

const requestFinancialAdvice = async () => {
    if (isGeneratingAdvice.value) return;
    isGeneratingAdvice.value = true;
    adviceError.value = null;

    try {
        const res = await fetch('/ai/get-advice', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-XSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                bulan: selectedBulan.value,
                tahun: selectedTahun.value,
            }),
        });

        const data = await res.json();
        if (res.ok && data.success) {
            aiAdvice.value = data.advice;
        } else {
            adviceError.value = data.message || 'Gagal menghasilkan nasihat keuangan.';
        }
    } catch (err: any) {
        adviceError.value = err.message || 'Terjadi kesalahan jaringan.';
    } finally {
        isGeneratingAdvice.value = false;
    }
};

// Progress bar color helper
const getProgressBarColor = (pct: number) => {
    if (pct > 100) return 'bg-rose-500 shadow-rose-500/50';
    if (pct > 80) return 'bg-amber-400 shadow-amber-400/50';
    return 'bg-pink-500 shadow-pink-500/50';
};
</script>

<template>
    <div>
        <Head title="Dashboard" />

        <div class="space-y-6">
            <!-- Header Bar & Month Filter -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
                        Dashboard Keuangan
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-400">
                        Pantau arus kas, status anggaran, dan insight cerdas AI Anda.
                    </p>
                </div>

                <!-- Month & Year Filter Selector -->
                <div class="flex items-center gap-2 bg-slate-900/80 border border-white/10 rounded-xl p-1.5 backdrop-blur-md">
                    <select
                        v-model="selectedBulan"
                        @change="applyFilter"
                        class="bg-transparent text-xs sm:text-sm text-slate-200 font-medium px-2 py-1 outline-none cursor-pointer"
                    >
                        <option v-for="(nama, idx) in namaBulan" :key="idx" :value="idx + 1" class="bg-slate-900 text-white">
                            {{ nama }}
                        </option>
                    </select>

                    <select
                        v-model="selectedTahun"
                        @change="applyFilter"
                        class="bg-transparent text-xs sm:text-sm text-slate-200 font-medium px-2 py-1 outline-none cursor-pointer border-l border-white/10"
                    >
                        <option v-for="y in [2024, 2025, 2026, 2027]" :key="y" :value="y" class="bg-slate-900 text-white">
                            {{ y }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- 1. Grid Ringkasan Atas (Total Saldo, Pemasukan, Pengeluaran, Sisa Anggaran) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Saldo -->
                <div class="rounded-2xl border border-pink-500/30 bg-gradient-to-br from-slate-900/90 via-slate-900/70 to-pink-950/20 p-5 backdrop-blur-md relative overflow-hidden shadow-lg shadow-pink-500/5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Total Saldo</span>
                        <div class="p-2 rounded-xl bg-pink-500/15 text-pink-400">
                            <Wallet class="size-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <h2 class="text-2xl font-black text-white tracking-tight">
                            {{ formatRupiah(summary.total_saldo) }}
                        </h2>
                        <span class="text-[11px] text-slate-400 mt-1 inline-block">Akumulasi seluruh akun</span>
                    </div>
                </div>

                <!-- Pemasukan Bulan Ini -->
                <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-5 backdrop-blur-md relative overflow-hidden shadow-lg">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Pemasukan Bulan Ini</span>
                        <div class="p-2 rounded-xl bg-emerald-500/15 text-emerald-400">
                            <TrendingUp class="size-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <h2 class="text-2xl font-black text-emerald-400 tracking-tight">
                            {{ formatRupiah(summary.pemasukan_bulan_ini) }}
                        </h2>
                        <span class="text-[11px] text-slate-400 mt-1 inline-block">Periode {{ namaBulan[selectedBulan - 1] }} {{ selectedTahun }}</span>
                    </div>
                </div>

                <!-- Pengeluaran Bulan Ini -->
                <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-5 backdrop-blur-md relative overflow-hidden shadow-lg">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Pengeluaran Bulan Ini</span>
                        <div class="p-2 rounded-xl bg-pink-500/15 text-pink-400">
                            <TrendingDown class="size-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <h2 class="text-2xl font-black text-pink-400 tracking-tight">
                            {{ formatRupiah(summary.pengeluaran_bulan_ini) }}
                        </h2>
                        <span class="text-[11px] text-slate-400 mt-1 inline-block">Periode {{ namaBulan[selectedBulan - 1] }} {{ selectedTahun }}</span>
                    </div>
                </div>

                <!-- Sisa Anggaran -->
                <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-5 backdrop-blur-md relative overflow-hidden shadow-lg">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Sisa Anggaran</span>
                        <div class="p-2 rounded-xl bg-purple-500/15 text-purple-400">
                            <PieChart class="size-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <h2 class="text-2xl font-black text-white tracking-tight">
                            {{ formatRupiah(summary.sisa_anggaran_total) }}
                        </h2>
                        <div class="flex items-center gap-2 mt-2">
                            <div class="h-1.5 flex-1 bg-slate-800 rounded-full overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :class="getProgressBarColor(summary.persentase_anggaran_total)"
                                    :style="{ width: `${Math.min(100, summary.persentase_anggaran_total)}%` }"
                                ></div>
                            </div>
                            <span class="text-[11px] font-semibold text-slate-300">{{ summary.persentase_anggaran_total }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Panel Quick Smart AI Input (Prominent Card) -->
            <div class="rounded-2xl border border-pink-500/40 bg-gradient-to-r from-slate-900/90 via-slate-900/80 to-pink-950/20 p-5 backdrop-blur-md shadow-xl shadow-pink-500/10">
                <div class="flex items-center gap-2.5 mb-3">
                    <div class="flex size-8 items-center justify-center rounded-xl bg-gradient-to-r from-pink-500 to-rose-500 text-white shadow-md shadow-pink-500/30">
                        <Sparkles class="size-4" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white flex items-center gap-2">
                            Catat Transaksi Cepat dengan AI
                        </h3>
                        <p class="text-xs text-slate-400">
                            Cukup ketik secara bebas dalam Bahasa Indonesia, AI akan membedah nominal, kategori, dan metode pembayaran.
                        </p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <input
                            v-model="aiPrompt"
                            @keyup.enter="submitAiTransaction"
                            type="text"
                            placeholder="Ketik transaksi bebas... cth: Beli kopi kenangan 22rb pakai QRIS atau Terima gaji 5jt"
                            class="w-full rounded-xl border border-white/10 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 placeholder-slate-500 outline-none focus:border-pink-500 focus:ring-1 focus:ring-pink-500 transition"
                            :disabled="isProcessingAi"
                        />
                    </div>
                    <button
                        @click="submitAiTransaction"
                        :disabled="isProcessingAi || !aiPrompt.trim()"
                        class="flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-pink-500 to-rose-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-pink-500/30 hover:from-pink-600 hover:to-rose-700 disabled:opacity-50 transition transform active:scale-95"
                    >
                        <span v-if="isProcessingAi" class="animate-spin inline-block size-4 border-2 border-white border-t-transparent rounded-full"></span>
                        <Sparkles v-else class="size-4" />
                        <span>{{ isProcessingAi ? 'Menganalisis...' : 'Kirim AI' }}</span>
                    </button>
                </div>

                <!-- Toast / Banner Result -->
                <div v-if="aiToastMessage" class="mt-3 p-3 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 text-xs flex items-center justify-between">
                    <span>✨ {{ aiToastMessage }}</span>
                    <button @click="aiToastMessage = null" class="font-bold text-slate-400 hover:text-white">✕</button>
                </div>
                <div v-if="aiErrorMessage" class="mt-3 p-3 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs flex items-center justify-between">
                    <span>⚠️ {{ aiErrorMessage }}</span>
                    <button @click="aiErrorMessage = null" class="font-bold text-slate-400 hover:text-white">✕</button>
                </div>
            </div>

            <!-- 3. Panel AI Financial Advisor -->
            <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-5 backdrop-blur-md shadow-lg">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-white/10">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 rounded-xl bg-purple-500/15 text-purple-400">
                            <Bot class="size-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white">AI Financial Advisor</h3>
                            <p class="text-xs text-slate-400">Dapatkan evaluasi pengeluaran & rekomendasi berhemat bulan ini.</p>
                        </div>
                    </div>
                    <button
                        @click="requestFinancialAdvice"
                        :disabled="isGeneratingAdvice"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold text-white bg-gradient-to-r from-pink-500 to-rose-600 hover:from-pink-600 hover:to-rose-700 shadow-md shadow-pink-500/20 disabled:opacity-50 transition transform active:scale-95"
                    >
                        <RefreshCw v-if="isGeneratingAdvice" class="size-3.5 animate-spin" />
                        <Sparkles v-else class="size-3.5" />
                        <span>{{ isGeneratingAdvice ? 'Menganalisis Keuangan...' : 'Analisis Keuangan Bulan Ini' }}</span>
                    </button>
                </div>

                <!-- Advice Content Display -->
                <div class="mt-4">
                    <div v-if="adviceError" class="p-3 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs">
                        {{ adviceError }}
                    </div>

                    <div v-else-if="aiAdvice" class="p-4 rounded-xl bg-slate-950/60 border border-white/5 text-sm text-slate-200 leading-relaxed whitespace-pre-line" v-html="formattedAdvice"></div>

                    <div v-else class="text-center py-6 text-slate-500 text-xs">
                        Klik tombol <strong>"Analisis Keuangan Bulan Ini"</strong> untuk meminta Gemini mengevaluasi performa finansial Anda.
                    </div>
                </div>
            </div>

            <!-- 4. Dua Kolom: Widget Progress Anggaran & Kategori Pengeluaran Terbesar -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Progress Anggaran per Kategori -->
                <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-5 backdrop-blur-md shadow-lg flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between pb-3 border-b border-white/10 mb-4">
                            <h3 class="text-base font-bold text-white flex items-center gap-2">
                                <PieChart class="size-4 text-pink-400" />
                                <span>Status Anggaran Kategori</span>
                            </h3>
                            <span class="text-xs text-slate-400">{{ budgets.length }} Kategori Dibatasi</span>
                        </div>

                        <div v-if="budgets.length === 0" class="text-center py-8 text-slate-500 text-xs">
                            Belum ada anggaran yang diatur untuk bulan ini.
                        </div>

                        <div v-else class="space-y-4">
                            <div v-for="b in budgets" :key="b.id" class="space-y-1.5">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-medium text-slate-200">{{ b.kategori_nama }}</span>
                                    <span class="text-slate-400">
                                        <span class="font-semibold" :class="b.persentase > 100 ? 'text-rose-400' : 'text-slate-200'">{{ formatRupiah(b.terpakai) }}</span>
                                        / {{ formatRupiah(b.batas_anggaran) }}
                                    </span>
                                </div>
                                <div class="h-2 w-full bg-slate-800 rounded-full overflow-hidden">
                                    <div
                                        class="h-full rounded-full transition-all duration-500"
                                        :class="getProgressBarColor(b.persentase)"
                                        :style="{ width: `${Math.min(100, b.persentase)}%` }"
                                    ></div>
                                </div>
                                <div class="flex items-center justify-between text-[10px] text-slate-500">
                                    <span>Sisa: {{ formatRupiah(b.sisa) }}</span>
                                    <span :class="b.persentase > 100 ? 'text-rose-400 font-bold' : b.persentase > 80 ? 'text-amber-400 font-bold' : ''">
                                        {{ b.persentase }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pengeluaran per Kategori -->
                <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-5 backdrop-blur-md shadow-lg">
                    <div class="flex items-center justify-between pb-3 border-b border-white/10 mb-4">
                        <h3 class="text-base font-bold text-white flex items-center gap-2">
                            <TrendingDown class="size-4 text-pink-400" />
                            <span>Pengeluaran per Kategori</span>
                        </h3>
                        <span class="text-xs text-slate-400">{{ formatRupiah(summary.pengeluaran_bulan_ini) }}</span>
                    </div>

                    <div v-if="expenses_by_category.length === 0" class="text-center py-8 text-slate-500 text-xs">
                        Belum ada transaksi pengeluaran pada bulan ini.
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="cat in expenses_by_category"
                            :key="cat.id"
                            class="flex items-center justify-between p-3 rounded-xl bg-slate-950/40 border border-white/5"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="size-3 rounded-full"
                                    :style="{ backgroundColor: cat.warna || '#ec4899' }"
                                ></div>
                                <span class="text-sm font-medium text-slate-200">{{ cat.nama }}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-bold text-slate-100">{{ formatRupiah(cat.total) }}</span>
                                <span class="block text-[11px] text-slate-400">{{ cat.persentase }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. 10 Transaksi Terbaru (Responsive Table & Stacked Cards) -->
            <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-5 backdrop-blur-md shadow-lg">
                <div class="flex items-center justify-between pb-3 border-b border-white/10 mb-4">
                    <h3 class="text-base font-bold text-white">Transaksi Terbaru</h3>
                    <a href="/transactions" class="text-xs text-pink-400 hover:text-pink-300 font-semibold flex items-center gap-1">
                        <span>Lihat Semua</span>
                        <ChevronRight class="size-3.5" />
                    </a>
                </div>

                <div v-if="recent_transactions.length === 0" class="text-center py-8 text-slate-500 text-xs">
                    Belum ada riwayat transaksi.
                </div>

                <div v-else>
                    <!-- Desktop Table (md:block) -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-300">
                            <thead class="text-xs uppercase bg-slate-950/50 text-slate-400 border-b border-white/10">
                                <tr>
                                    <th class="py-3 px-4">Tanggal</th>
                                    <th class="py-3 px-4">Deskripsi</th>
                                    <th class="py-3 px-4">Kategori</th>
                                    <th class="py-3 px-4">Metode</th>
                                    <th class="py-3 px-4 text-right">Nominal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr v-for="t in recent_transactions" :key="t.id" class="hover:bg-white/5 transition">
                                    <td class="py-3 px-4 text-xs text-slate-400 whitespace-nowrap">{{ t.tanggal_transaksi }}</td>
                                    <td class="py-3 px-4 font-medium text-slate-200 flex items-center gap-2">
                                        <span>{{ t.deskripsi }}</span>
                                        <span
                                            v-if="t.is_ai_generated"
                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-pink-500/20 text-pink-400 border border-pink-500/30"
                                            title="Dicatat otomatis dengan Smart AI"
                                        >
                                            <Sparkles class="size-2.5" /> AI
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-800 text-slate-300">
                                            {{ t.category?.nama }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-xs text-slate-400">{{ t.metode_pembayaran }}</td>
                                    <td class="py-3 px-4 text-right font-bold whitespace-nowrap" :class="t.category?.tipe === 'income' ? 'text-emerald-400' : 'text-pink-400'">
                                        {{ t.category?.tipe === 'income' ? '+' : '-' }} {{ formatRupiah(t.nominal) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Stacked Card (< md) -->
                    <div class="md:hidden space-y-3">
                        <div
                            v-for="t in recent_transactions"
                            :key="t.id"
                            class="p-3 rounded-xl bg-slate-950/60 border border-white/5 flex items-center justify-between"
                        >
                            <div class="space-y-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-medium text-sm text-slate-200">{{ t.deskripsi }}</span>
                                    <Sparkles v-if="t.is_ai_generated" class="size-3 text-pink-400" />
                                </div>
                                <div class="flex items-center gap-2 text-[11px] text-slate-400">
                                    <span>{{ t.tanggal_transaksi }}</span>
                                    <span>•</span>
                                    <span>{{ t.category?.nama }}</span>
                                    <span>•</span>
                                    <span>{{ t.metode_pembayaran }}</span>
                                </div>
                            </div>
                            <div class="text-right font-bold text-sm" :class="t.category?.tipe === 'income' ? 'text-emerald-400' : 'text-pink-400'">
                                {{ t.category?.tipe === 'income' ? '+' : '-' }} {{ formatRupiah(t.nominal) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
