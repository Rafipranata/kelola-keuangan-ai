<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowDownRight,
    ArrowUpRight,
    Bot,
    ChevronRight,
    DollarSign,
    Mic,
    MicOff,
    PieChart,
    Plus,
    RefreshCw,
    Sparkles,
    TrendingDown,
    TrendingUp,
    Wallet,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { useSpeechRecognition } from '@/composables/useSpeechRecognition';

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
        '/dashboard',
        {
            bulan: selectedBulan.value,
            tahun: selectedTahun.value,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const getCsrfToken = () => {
    const meta = document.querySelector(
        'meta[name="csrf-token"]',
    ) as HTMLMetaElement | null;
    if (meta && meta.content) return meta.content;
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
};

// Voice Input (Speech Recognition)
const { isSupported: isSpeechSupported, isListening: isSpeechListening, toggleListening } = useSpeechRecognition();

const handleVoiceInput = () => {
    aiErrorMessage.value = null;
    toggleListening(
        (transcript: string, isFinal: boolean) => {
            aiPrompt.value = transcript;
        },
        (errorMsg: string) => {
            aiErrorMessage.value = errorMsg;
        }
    );
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
                Accept: 'application/json',
            },
            body: JSON.stringify({ prompt: aiPrompt.value }),
        });

        const data = await res.json();
        if (res.ok && data.success) {
            if (data.transactions && data.transactions.length > 1) {
                const details = data.transactions
                    .map((t: any) => `${t.deskripsi} (${formatRupiah(t.nominal)})`)
                    .join(', ');
                aiToastMessage.value = `${data.transactions.length} transaksi disimpan: ${details}`;
            } else {
                aiToastMessage.value = `Berhasil disimpan: ${data.data.deskripsi} (${formatRupiah(data.data.nominal)})`;
            }
            aiPrompt.value = '';
            // Refresh data
            router.reload({
                only: [
                    'summary',
                    'budgets',
                    'expenses_by_category',
                    'recent_transactions',
                ],
            });
        } else {
            aiErrorMessage.value =
                data.message || 'Gagal memproses transaksi dengan AI.';
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
    text = text.replace(
        /\*\*(.*?)\*\*/g,
        '<strong class="text-white font-bold">$1</strong>',
    );

    // Ubah italic markdown: *teks* atau _teks_ menjadi <em class="text-pink-300 not-italic font-medium">$1</em>
    text = text.replace(
        /\*(.*?)\*/g,
        '<em class="text-pink-300 not-italic font-medium">$1</em>',
    );

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
                Accept: 'application/json',
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
            adviceError.value =
                data.message || 'Gagal menghasilkan nasihat keuangan.';
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
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1
                        class="flex items-center gap-2 text-2xl font-bold tracking-tight text-slate-900 dark:text-white"
                    >
                        Dashboard Keuangan
                    </h1>
                    <p
                        class="text-xs text-slate-500 sm:text-sm dark:text-slate-400"
                    >
                        Pantau arus kas, status anggaran, dan insight cerdas AI
                        Anda.
                    </p>
                </div>

                <!-- Month & Year Filter Selector -->
                <div
                    class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white p-1.5 shadow-sm backdrop-blur-md dark:border-white/10 dark:bg-slate-900/80 dark:shadow-none"
                >
                    <select
                        v-model="selectedBulan"
                        @change="applyFilter"
                        class="cursor-pointer bg-transparent px-2 py-1 text-xs font-medium text-slate-700 outline-none sm:text-sm dark:text-slate-200"
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
                        class="cursor-pointer border-l border-slate-200 bg-transparent px-2 py-1 text-xs font-medium text-slate-700 outline-none sm:text-sm dark:border-white/10 dark:text-slate-200"
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
            </div>

            <!-- 1. Grid Ringkasan Atas (Total Saldo, Pemasukan, Pengeluaran, Sisa Anggaran) -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Total Saldo -->
                <div
                    class="relative overflow-hidden rounded-2xl border border-pink-500/30 bg-gradient-to-br from-white via-white to-pink-50/50 p-5 shadow-sm backdrop-blur-md transition-colors dark:from-slate-900/90 dark:via-slate-900/70 dark:to-pink-950/20 dark:shadow-lg dark:shadow-pink-500/5"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >Total Saldo</span
                        >
                        <div
                            class="rounded-xl bg-pink-500/15 p-2 text-pink-500"
                        >
                            <Wallet class="size-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <h2
                            class="text-2xl font-black tracking-tight text-slate-900 dark:text-white"
                        >
                            {{ formatRupiah(summary.total_saldo) }}
                        </h2>
                        <span
                            class="mt-1 inline-block text-[11px] text-slate-500 dark:text-slate-400"
                            >Akumulasi seluruh akun</span
                        >
                    </div>
                </div>

                <!-- Pemasukan Bulan Ini -->
                <div
                    class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm backdrop-blur-md transition-colors dark:border-white/10 dark:bg-slate-900/70 dark:shadow-lg"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >Pemasukan Bulan Ini</span
                        >
                        <div
                            class="rounded-xl bg-emerald-500/15 p-2 text-emerald-500"
                        >
                            <TrendingUp class="size-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <h2
                            class="text-2xl font-black tracking-tight text-emerald-600 dark:text-emerald-400"
                        >
                            {{ formatRupiah(summary.pemasukan_bulan_ini) }}
                        </h2>
                        <span
                            class="mt-1 inline-block text-[11px] text-slate-500 dark:text-slate-400"
                            >Periode {{ namaBulan[selectedBulan - 1] }}
                            {{ selectedTahun }}</span
                        >
                    </div>
                </div>

                <!-- Pengeluaran Bulan Ini -->
                <div
                    class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm backdrop-blur-md transition-colors dark:border-white/10 dark:bg-slate-900/70 dark:shadow-lg"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >Pengeluaran Bulan Ini</span
                        >
                        <div
                            class="rounded-xl bg-pink-500/15 p-2 text-pink-500"
                        >
                            <TrendingDown class="size-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <h2
                            class="text-2xl font-black tracking-tight text-pink-600 dark:text-pink-400"
                        >
                            {{ formatRupiah(summary.pengeluaran_bulan_ini) }}
                        </h2>
                        <span
                            class="mt-1 inline-block text-[11px] text-slate-500 dark:text-slate-400"
                            >Periode {{ namaBulan[selectedBulan - 1] }}
                            {{ selectedTahun }}</span
                        >
                    </div>
                </div>

                <!-- Sisa Anggaran -->
                <div
                    class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm backdrop-blur-md transition-colors dark:border-white/10 dark:bg-slate-900/70 dark:shadow-lg"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                            >Sisa Anggaran</span
                        >
                        <div
                            class="rounded-xl bg-purple-500/15 p-2 text-purple-500"
                        >
                            <PieChart class="size-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <h2
                            class="text-2xl font-black tracking-tight text-slate-900 dark:text-white"
                        >
                            {{ formatRupiah(summary.sisa_anggaran_total) }}
                        </h2>
                        <div class="mt-2 flex items-center gap-2">
                            <div
                                class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800"
                            >
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :class="
                                        getProgressBarColor(
                                            summary.persentase_anggaran_total,
                                        )
                                    "
                                    :style="{
                                        width: `${Math.min(100, summary.persentase_anggaran_total)}%`,
                                    }"
                                ></div>
                            </div>
                            <span
                                class="text-[11px] font-semibold text-slate-600 dark:text-slate-300"
                                >{{ summary.persentase_anggaran_total }}%</span
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Panel Quick Smart AI Input (Prominent Card) -->
            <div
                class="rounded-2xl border border-pink-500/40 bg-gradient-to-r from-pink-50/70 via-white to-pink-50/40 p-5 shadow-md backdrop-blur-md transition-colors dark:from-slate-900/90 dark:via-slate-900/80 dark:to-pink-950/20 dark:shadow-xl dark:shadow-pink-500/10"
            >
                <div class="mb-3 flex items-center gap-2.5">
                    <div
                        class="flex size-8 items-center justify-center rounded-xl bg-gradient-to-r from-pink-500 to-rose-500 text-white shadow-md shadow-pink-500/30"
                    >
                        <Sparkles class="size-4" />
                    </div>
                    <div>
                        <h3
                            class="flex items-center gap-2 text-base font-bold text-slate-900 dark:text-white"
                        >
                            Catat Transaksi Cepat dengan AI
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Cukup ketik secara bebas dalam Bahasa Indonesia, AI
                            akan membedah nominal, kategori, dan metode
                            pembayaran.
                        </p>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <div class="relative flex-1 flex items-center">
                        <input
                            v-model="aiPrompt"
                            @keyup.enter="submitAiTransaction"
                            type="text"
                            :placeholder="isSpeechListening ? 'Mendengarkan... Contoh: Beli nasi padang 20 ribu cash' : 'Ketik transaksi bebas... cth: Beli kopi 22rb pakai QRIS atau Terima gaji 5jt'"
                            class="w-full rounded-xl border border-slate-300 bg-white py-3 pl-4 pr-12 text-sm text-slate-900 placeholder-slate-400 transition outline-none focus:border-pink-500 focus:ring-1 focus:ring-pink-500 dark:border-white/10 dark:bg-slate-950/80 dark:text-slate-100 dark:placeholder-slate-500"
                            :disabled="isProcessingAi"
                        />
                        <!-- Voice Mic Button inside Input -->
                        <button
                            v-if="isSpeechSupported"
                            type="button"
                            @click="handleVoiceInput"
                            :class="[
                                'absolute right-2.5 p-2 rounded-lg transition-all',
                                isSpeechListening
                                    ? 'bg-rose-500 text-white animate-pulse shadow-md shadow-rose-500/30'
                                    : 'text-slate-400 hover:text-pink-500 hover:bg-pink-50 dark:hover:bg-pink-950/40'
                            ]"
                            :title="isSpeechListening ? 'Sedang merekam (klik untuk selesai)' : 'Gunakan Mikrofon (Bicara)'"
                        >
                            <MicOff v-if="isSpeechListening" class="size-4" />
                            <Mic v-else class="size-4" />
                        </button>
                    </div>
                    <button
                        @click="submitAiTransaction"
                        :disabled="isProcessingAi || !aiPrompt.trim()"
                        class="flex transform items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-pink-500 to-rose-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-pink-500/30 transition hover:from-pink-600 hover:to-rose-700 active:scale-95 disabled:opacity-50"
                    >
                        <span
                            v-if="isProcessingAi"
                            class="inline-block size-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                        ></span>
                        <Sparkles v-else class="size-4" />
                        <span>{{
                            isProcessingAi ? 'Menganalisis...' : 'Kirim AI'
                        }}</span>
                    </button>
                </div>

                <!-- Toast / Banner Result -->
                <div
                    v-if="aiToastMessage"
                    class="mt-3 flex items-center justify-between rounded-xl border border-emerald-500/30 bg-emerald-500/15 p-3 text-xs text-emerald-600 dark:text-emerald-300"
                >
                    <span>✨ {{ aiToastMessage }}</span>
                    <button
                        @click="aiToastMessage = null"
                        class="font-bold text-slate-400 hover:text-slate-700 dark:hover:text-white"
                    >
                        ✕
                    </button>
                </div>
                <div
                    v-if="aiErrorMessage"
                    class="mt-3 flex items-center justify-between rounded-xl border border-rose-500/30 bg-rose-500/15 p-3 text-xs text-rose-600 dark:text-rose-300"
                >
                    <span>⚠️ {{ aiErrorMessage }}</span>
                    <button
                        @click="aiErrorMessage = null"
                        class="font-bold text-slate-400 hover:text-slate-700 dark:hover:text-white"
                    >
                        ✕
                    </button>
                </div>
            </div>

            <!-- 3. Panel AI Financial Advisor -->
            <div
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm backdrop-blur-md transition-colors dark:border-white/10 dark:bg-slate-900/70 dark:shadow-lg"
            >
                <div
                    class="flex flex-col gap-4 border-b border-slate-200 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-white/10"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="rounded-xl bg-purple-500/15 p-2.5 text-purple-500"
                        >
                            <Bot class="size-5" />
                        </div>
                        <div>
                            <h3
                                class="text-base font-bold text-slate-900 dark:text-white"
                            >
                                AI Financial Advisor
                            </h3>
                            <p
                                class="text-xs text-slate-500 dark:text-slate-400"
                            >
                                Dapatkan evaluasi pengeluaran & rekomendasi
                                berhemat bulan ini.
                            </p>
                        </div>
                    </div>
                    <button
                        @click="requestFinancialAdvice"
                        :disabled="isGeneratingAdvice"
                        class="inline-flex transform items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-pink-500 to-rose-600 px-4 py-2 text-xs font-semibold text-white shadow-md shadow-pink-500/20 transition hover:from-pink-600 hover:to-rose-700 active:scale-95 disabled:opacity-50"
                    >
                        <RefreshCw
                            v-if="isGeneratingAdvice"
                            class="size-3.5 animate-spin"
                        />
                        <Sparkles v-else class="size-3.5" />
                        <span>{{
                            isGeneratingAdvice
                                ? 'Menganalisis Keuangan...'
                                : 'Analisis Keuangan Bulan Ini'
                        }}</span>
                    </button>
                </div>

                <!-- Advice Content Display -->
                <div class="mt-4">
                    <div
                        v-if="adviceError"
                        class="rounded-xl border border-rose-500/30 bg-rose-500/15 p-3 text-xs text-rose-600 dark:text-rose-300"
                    >
                        {{ adviceError }}
                    </div>

                    <div
                        v-else-if="aiAdvice"
                        class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm leading-relaxed whitespace-pre-line text-slate-800 dark:border-white/5 dark:bg-slate-950/60 dark:text-slate-200"
                        v-html="formattedAdvice"
                    ></div>

                    <div v-else class="py-6 text-center text-xs text-slate-500">
                        Klik tombol
                        <strong>"Analisis Keuangan Bulan Ini"</strong> untuk
                        meminta Gemini mengevaluasi performa finansial Anda.
                    </div>
                </div>
            </div>

            <!-- 4. Dua Kolom: Widget Progress Anggaran & Kategori Pengeluaran Terbesar -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Progress Anggaran per Kategori -->
                <div
                    class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm backdrop-blur-md transition-colors dark:border-white/10 dark:bg-slate-900/70 dark:shadow-lg"
                >
                    <div>
                        <div
                            class="mb-4 flex items-center justify-between border-b border-slate-200 pb-3 dark:border-white/10"
                        >
                            <h3
                                class="flex items-center gap-2 text-base font-bold text-slate-900 dark:text-white"
                            >
                                <PieChart
                                    class="size-4 text-pink-500 dark:text-pink-400"
                                />
                                <span>Status Anggaran Kategori</span>
                            </h3>
                            <span
                                class="text-xs text-slate-500 dark:text-slate-400"
                                >{{ budgets.length }} Kategori Dibatasi</span
                            >
                        </div>

                        <div
                            v-if="budgets.length === 0"
                            class="py-8 text-center text-xs text-slate-500"
                        >
                            Belum ada anggaran yang diatur untuk bulan ini.
                        </div>

                        <div v-else class="space-y-4">
                            <div
                                v-for="b in budgets"
                                :key="b.id"
                                class="space-y-1.5"
                            >
                                <div
                                    class="flex items-center justify-between text-xs"
                                >
                                    <span
                                        class="font-medium text-slate-700 dark:text-slate-200"
                                        >{{ b.kategori_nama }}</span
                                    >
                                    <span
                                        class="text-slate-500 dark:text-slate-400"
                                    >
                                        <span
                                            class="font-semibold"
                                            :class="
                                                b.persentase > 100
                                                    ? 'text-rose-600 dark:text-rose-400'
                                                    : 'text-slate-900 dark:text-slate-200'
                                            "
                                            >{{
                                                formatRupiah(b.terpakai)
                                            }}</span
                                        >
                                        / {{ formatRupiah(b.batas_anggaran) }}
                                    </span>
                                </div>
                                <div
                                    class="h-2 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800"
                                >
                                    <div
                                        class="h-full rounded-full transition-all duration-500"
                                        :class="
                                            getProgressBarColor(b.persentase)
                                        "
                                        :style="{
                                            width: `${Math.min(100, b.persentase)}%`,
                                        }"
                                    ></div>
                                </div>
                                <div
                                    class="flex items-center justify-between text-[10px] text-slate-500"
                                >
                                    <span
                                        >Sisa: {{ formatRupiah(b.sisa) }}</span
                                    >
                                    <span
                                        :class="
                                            b.persentase > 100
                                                ? 'font-bold text-rose-600 dark:text-rose-400'
                                                : b.persentase > 80
                                                  ? 'font-bold text-amber-600 dark:text-amber-400'
                                                  : ''
                                        "
                                    >
                                        {{ b.persentase }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pengeluaran per Kategori -->
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm backdrop-blur-md transition-colors dark:border-white/10 dark:bg-slate-900/70 dark:shadow-lg"
                >
                    <div
                        class="mb-4 flex items-center justify-between border-b border-slate-200 pb-3 dark:border-white/10"
                    >
                        <h3
                            class="flex items-center gap-2 text-base font-bold text-slate-900 dark:text-white"
                        >
                            <TrendingDown
                                class="size-4 text-pink-500 dark:text-pink-400"
                            />
                            <span>Pengeluaran per Kategori</span>
                        </h3>
                        <span
                            class="text-xs text-slate-500 dark:text-slate-400"
                            >{{
                                formatRupiah(summary.pengeluaran_bulan_ini)
                            }}</span
                        >
                    </div>

                    <div
                        v-if="expenses_by_category.length === 0"
                        class="py-8 text-center text-xs text-slate-500"
                    >
                        Belum ada transaksi pengeluaran pada bulan ini.
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="cat in expenses_by_category"
                            :key="cat.id"
                            class="flex items-center justify-between rounded-xl border border-slate-200/80 bg-slate-50 p-3 dark:border-white/5 dark:bg-slate-950/40"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="size-3 rounded-full"
                                    :style="{
                                        backgroundColor: cat.warna || '#ec4899',
                                    }"
                                ></div>
                                <span
                                    class="text-sm font-medium text-slate-800 dark:text-slate-200"
                                    >{{ cat.nama }}</span
                                >
                            </div>
                            <div class="text-right">
                                <span
                                    class="text-sm font-bold text-slate-900 dark:text-slate-100"
                                    >{{ formatRupiah(cat.total) }}</span
                                >
                                <span
                                    class="block text-[11px] text-slate-500 dark:text-slate-400"
                                    >{{ cat.persentase }}%</span
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. 10 Transaksi Terbaru (Responsive Table & Stacked Cards) -->
            <div
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm backdrop-blur-md transition-colors dark:border-white/10 dark:bg-slate-900/70 dark:shadow-lg"
            >
                <div
                    class="mb-4 flex items-center justify-between border-b border-slate-200 pb-3 dark:border-white/10"
                >
                    <h3
                        class="text-base font-bold text-slate-900 dark:text-white"
                    >
                        Transaksi Terbaru
                    </h3>
                    <a
                        href="/transactions"
                        class="flex items-center gap-1 text-xs font-semibold text-pink-600 hover:text-pink-700 dark:text-pink-400 dark:hover:text-pink-300"
                    >
                        <span>Lihat Semua</span>
                        <ChevronRight class="size-3.5" />
                    </a>
                </div>

                <div
                    v-if="recent_transactions.length === 0"
                    class="py-8 text-center text-xs text-slate-500"
                >
                    Belum ada riwayat transaksi.
                </div>

                <div v-else>
                    <!-- Desktop Table (md:block) -->
                    <div class="hidden overflow-x-auto md:block">
                        <table
                            class="w-full text-left text-sm text-slate-700 dark:text-slate-300"
                        >
                            <thead
                                class="border-b border-slate-200 bg-slate-100/80 text-xs text-slate-500 uppercase dark:border-white/10 dark:bg-slate-950/50 dark:text-slate-400"
                            >
                                <tr>
                                    <th class="px-4 py-3">Tanggal</th>
                                    <th class="px-4 py-3">Deskripsi</th>
                                    <th class="px-4 py-3">Kategori</th>
                                    <th class="px-4 py-3">Metode</th>
                                    <th class="px-4 py-3 text-right">
                                        Nominal
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-slate-200 dark:divide-white/5"
                            >
                                <tr
                                    v-for="t in recent_transactions"
                                    :key="t.id"
                                    class="transition hover:bg-slate-50 dark:hover:bg-white/5"
                                >
                                    <td
                                        class="px-4 py-3 text-xs whitespace-nowrap text-slate-500 dark:text-slate-400"
                                    >
                                        {{ t.tanggal_transaksi }}
                                    </td>
                                    <td
                                        class="flex items-center gap-2 px-4 py-3 font-medium text-slate-900 dark:text-slate-200"
                                    >
                                        <span>{{ t.deskripsi }}</span>
                                        <span
                                            v-if="t.is_ai_generated"
                                            class="inline-flex items-center gap-1 rounded border border-pink-500/30 bg-pink-500/15 px-1.5 py-0.5 text-[10px] font-semibold text-pink-600 dark:text-pink-400"
                                            title="Dicatat otomatis dengan Smart AI"
                                        >
                                            <Sparkles class="size-2.5" /> AI
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-block rounded-lg border border-slate-200 bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 dark:border-transparent dark:bg-slate-800 dark:text-slate-300"
                                        >
                                            {{ t.category?.nama }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400"
                                    >
                                        {{ t.metode_pembayaran }}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right font-bold whitespace-nowrap"
                                        :class="
                                            t.category?.tipe === 'income'
                                                ? 'text-emerald-600 dark:text-emerald-400'
                                                : 'text-pink-600 dark:text-pink-400'
                                        "
                                    >
                                        {{
                                            t.category?.tipe === 'income'
                                                ? '+'
                                                : '-'
                                        }}
                                        {{ formatRupiah(t.nominal) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Stacked Card (< md) -->
                    <div class="space-y-3 md:hidden">
                        <div
                            v-for="t in recent_transactions"
                            :key="t.id"
                            class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-white/5 dark:bg-slate-950/60"
                        >
                            <div class="space-y-1">
                                <div class="flex items-center gap-1.5">
                                    <span
                                        class="text-sm font-medium text-slate-900 dark:text-slate-200"
                                        >{{ t.deskripsi }}</span
                                    >
                                    <Sparkles
                                        v-if="t.is_ai_generated"
                                        class="size-3 text-pink-500"
                                    />
                                </div>
                                <div
                                    class="flex items-center gap-2 text-[11px] text-slate-500 dark:text-slate-400"
                                >
                                    <span>{{ t.tanggal_transaksi }}</span>
                                    <span>•</span>
                                    <span>{{ t.category?.nama }}</span>
                                    <span>•</span>
                                    <span>{{ t.metode_pembayaran }}</span>
                                </div>
                            </div>
                            <div
                                class="text-right text-sm font-bold"
                                :class="
                                    t.category?.tipe === 'income'
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-pink-600 dark:text-pink-400'
                                "
                            >
                                {{ t.category?.tipe === 'income' ? '+' : '-' }}
                                {{ formatRupiah(t.nominal) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
