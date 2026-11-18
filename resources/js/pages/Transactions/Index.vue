<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Edit,
    Filter,
    Plus,
    ReceiptText,
    Search,
    Sparkles,
    Trash2,
} from '@lucide/vue';
import { ref } from 'vue';

interface Category {
    id: number;
    nama: string;
    tipe: 'income' | 'expense';
    ikon: string;
    warna: string;
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

interface Pagination<T> {
    data: T[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
}

const props = defineProps<{
    transactions: Pagination<Transaction>;
    categories: Category[];
    filters: {
        bulan?: string | number;
        tahun?: string | number;
        tipe?: string;
        category_id?: string | number;
        search?: string;
    };
}>();

const formatRupiah = (val: number | string) => {
    const num = typeof val === 'string' ? parseFloat(val) : val;
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(num || 0);
};

const filterSearch = ref(props.filters.search || '');
const filterTipe = ref(props.filters.tipe || '');
const filterCategory = ref(props.filters.category_id || '');
const filterBulan = ref(props.filters.bulan || '');
const filterTahun = ref(props.filters.tahun || '');

const handleFilter = () => {
    router.get(
        '/transactions',
        {
            search: filterSearch.value,
            tipe: filterTipe.value,
            category_id: filterCategory.value,
            bulan: filterBulan.value,
            tahun: filterTahun.value,
        },
        { preserveState: true, replace: true },
    );
};

const resetFilter = () => {
    filterSearch.value = '';
    filterTipe.value = '';
    filterCategory.value = '';
    filterBulan.value = '';
    filterTahun.value = '';
    router.get('/transactions');
};

// Modal Tambah / Edit
const showModal = ref(false);
const editingTransaction = ref<Transaction | null>(null);

const form = useForm({
    category_id: '',
    nominal: '',
    deskripsi: '',
    tanggal_transaksi: new Date().toISOString().substring(0, 10),
    metode_pembayaran: 'Cash',
});

const openCreateModal = () => {
    editingTransaction.value = null;
    form.reset();
    form.tanggal_transaksi = new Date().toISOString().substring(0, 10);
    form.metode_pembayaran = 'Cash';
    showModal.value = true;
};

const openEditModal = (t: Transaction) => {
    editingTransaction.value = t;
    form.category_id = String(t.category_id);
    form.nominal = String(t.nominal);
    form.deskripsi = t.deskripsi;
    form.tanggal_transaksi = t.tanggal_transaksi;
    form.metode_pembayaran = t.metode_pembayaran;
    showModal.value = true;
};

const submitForm = () => {
    if (editingTransaction.value) {
        form.put(`/transactions/${editingTransaction.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post('/transactions', {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    }
};

const deleteTransaction = (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus transaksi ini?')) {
        router.delete(`/transactions/${id}`);
    }
};
</script>

<template>
    <div>
        <Head title="Daftar Transaksi" />

        <div class="space-y-6">
            <!-- Header Bar -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1
                        class="flex items-center gap-2 text-2xl font-bold tracking-tight text-slate-900 dark:text-white"
                    >
                        <ReceiptText class="size-6 text-pink-500" />
                        <span>Daftar Transaksi</span>
                    </h1>
                    <p
                        class="text-xs text-slate-500 sm:text-sm dark:text-slate-400"
                    >
                        Kelola seluruh arus kas masuk dan keluar secara
                        terorganisir.
                    </p>
                </div>

                <button
                    @click="openCreateModal"
                    class="inline-flex transform items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-pink-500 to-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-pink-500/25 transition hover:from-pink-600 hover:to-rose-700 active:scale-95"
                >
                    <Plus class="size-4" />
                    <span>Tambah Transaksi</span>
                </button>
            </div>

            <!-- Filter Card -->
            <div
                class="space-y-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm backdrop-blur-md transition-colors dark:border-white/10 dark:bg-slate-900/70 dark:shadow-lg"
            >
                <div
                    class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5"
                >
                    <!-- Search Input -->
                    <div class="relative">
                        <Search
                            class="absolute top-3 left-3 size-4 text-slate-400 dark:text-slate-500"
                        />
                        <input
                            v-model="filterSearch"
                            @keyup.enter="handleFilter"
                            type="text"
                            placeholder="Cari deskripsi / metode..."
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pr-3 pl-9 text-xs text-slate-900 placeholder-slate-400 outline-none focus:border-pink-500 dark:border-white/10 dark:bg-slate-950/70 dark:text-slate-100 dark:placeholder-slate-500"
                        />
                    </div>

                    <!-- Tipe Filter -->
                    <select
                        v-model="filterTipe"
                        @change="handleFilter"
                        class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-800 outline-none focus:border-pink-500 dark:border-white/10 dark:bg-slate-950/70 dark:text-slate-100"
                    >
                        <option value="">Semua Tipe</option>
                        <option value="income">Pemasukan (Income)</option>
                        <option value="expense">Pengeluaran (Expense)</option>
                    </select>

                    <!-- Kategori Filter -->
                    <select
                        v-model="filterCategory"
                        @change="handleFilter"
                        class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-800 outline-none focus:border-pink-500 dark:border-white/10 dark:bg-slate-950/70 dark:text-slate-100"
                    >
                        <option value="">Semua Kategori</option>
                        <option
                            v-for="c in categories"
                            :key="c.id"
                            :value="c.id"
                        >
                            {{ c.nama }} ({{ c.tipe }})
                        </option>
                    </select>

                    <!-- Bulan Filter -->
                    <select
                        v-model="filterBulan"
                        @change="handleFilter"
                        class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-800 outline-none focus:border-pink-500 dark:border-white/10 dark:bg-slate-950/70 dark:text-slate-100"
                    >
                        <option value="">Semua Bulan</option>
                        <option value="1">Januari</option>
                        <option value="2">Februari</option>
                        <option value="3">Maret</option>
                        <option value="4">April</option>
                        <option value="5">Mei</option>
                        <option value="6">Juni</option>
                        <option value="7">Juli</option>
                        <option value="8">Agustus</option>
                        <option value="9">September</option>
                        <option value="10">Oktober</option>
                        <option value="11">November</option>
                        <option value="12">Desember</option>
                    </select>

                    <!-- Actions -->
                    <div class="flex items-center gap-2">
                        <button
                            @click="handleFilter"
                            class="flex-1 rounded-xl border border-pink-500/30 bg-pink-500/15 py-2 text-center text-xs font-semibold text-pink-600 transition hover:bg-pink-500/25 dark:text-pink-400"
                        >
                            Terapkan
                        </button>
                        <button
                            @click="resetFilter"
                            class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-medium text-slate-600 transition hover:text-slate-900 dark:bg-slate-800 dark:text-slate-400 dark:hover:text-white"
                        >
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- Transactions List -->
            <div
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm backdrop-blur-md transition-colors dark:border-white/10 dark:bg-slate-900/70 dark:shadow-lg"
            >
                <div
                    v-if="transactions.data.length === 0"
                    class="py-12 text-center text-sm text-slate-500"
                >
                    Tidak ada transaksi yang cocok dengan filter atau kriteria
                    pencarian.
                </div>

                <div v-else>
                    <!-- Desktop Table -->
                    <div class="hidden overflow-x-auto md:block">
                        <table
                            class="w-full text-left text-sm text-slate-700 dark:text-slate-300"
                        >
                            <thead
                                class="border-b border-slate-200 bg-slate-100/80 text-xs text-slate-500 uppercase dark:border-white/10 dark:bg-slate-950/60 dark:text-slate-400"
                            >
                                <tr>
                                    <th class="px-4 py-3">Tanggal</th>
                                    <th class="px-4 py-3">Deskripsi</th>
                                    <th class="px-4 py-3">Kategori</th>
                                    <th class="px-4 py-3">Metode</th>
                                    <th class="px-4 py-3 text-right">
                                        Nominal
                                    </th>
                                    <th class="px-4 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-slate-200 dark:divide-white/5"
                            >
                                <tr
                                    v-for="t in transactions.data"
                                    :key="t.id"
                                    class="transition hover:bg-slate-50 dark:hover:bg-white/5"
                                >
                                    <td
                                        class="px-4 py-3 text-xs whitespace-nowrap text-slate-500 dark:text-slate-400"
                                    >
                                        {{ t.tanggal_transaksi }}
                                    </td>
                                    <td
                                        class="px-4 py-3 font-medium text-slate-900 dark:text-slate-200"
                                    >
                                        <div class="flex items-center gap-2">
                                            <span>{{ t.deskripsi }}</span>
                                            <span
                                                v-if="t.is_ai_generated"
                                                class="inline-flex items-center gap-1 rounded border border-pink-500/30 bg-pink-500/15 px-1.5 py-0.5 text-[10px] font-semibold text-pink-600 dark:text-pink-400"
                                                title="Dicatat otomatis dengan AI"
                                            >
                                                <Sparkles class="size-2.5" /> AI
                                            </span>
                                        </div>
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
                                    <td class="px-4 py-3 text-center">
                                        <div
                                            class="flex items-center justify-center gap-2"
                                        >
                                            <button
                                                @click="openEditModal(t)"
                                                class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-800 dark:hover:bg-slate-800 dark:hover:text-white"
                                                title="Edit"
                                            >
                                                <Edit class="size-3.5" />
                                            </button>
                                            <button
                                                @click="deleteTransaction(t.id)"
                                                class="rounded-lg p-1.5 text-rose-500 transition hover:bg-rose-500/10 hover:text-rose-600 dark:text-rose-400 dark:hover:text-rose-300"
                                                title="Hapus"
                                            >
                                                <Trash2 class="size-3.5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Stacked Card -->
                    <div
                        class="divide-y divide-slate-200 p-2 md:hidden dark:divide-white/5"
                    >
                        <div
                            v-for="t in transactions.data"
                            :key="t.id"
                            class="space-y-2 p-3"
                        >
                            <div class="flex items-start justify-between">
                                <div>
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
                                        class="mt-1 flex items-center gap-2 text-[11px] text-slate-500 dark:text-slate-400"
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
                                    {{
                                        t.category?.tipe === 'income'
                                            ? '+'
                                            : '-'
                                    }}
                                    {{ formatRupiah(t.nominal) }}
                                </div>
                            </div>
                            <div
                                class="flex justify-end gap-2 border-t border-slate-100 pt-1 dark:border-white/5"
                            >
                                <button
                                    @click="openEditModal(t)"
                                    class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs text-slate-600 hover:text-slate-900 dark:bg-slate-800 dark:text-slate-300 dark:hover:text-white"
                                >
                                    Edit
                                </button>
                                <button
                                    @click="deleteTransaction(t.id)"
                                    class="rounded-lg bg-rose-500/10 px-2.5 py-1 text-xs text-rose-600 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div
                        class="flex items-center justify-between border-t border-slate-200 p-4 text-xs text-slate-500 dark:border-white/10 dark:text-slate-400"
                    >
                        <span>Total: {{ transactions.total }} transaksi</span>
                        <div class="flex items-center gap-2">
                            <button
                                v-if="transactions.prev_page_url"
                                @click="router.get(transactions.prev_page_url)"
                                class="rounded-lg bg-slate-100 px-3 py-1.5 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                            >
                                Sebelumnya
                            </button>
                            <span
                                >Halaman {{ transactions.current_page }} dari
                                {{ transactions.last_page }}</span
                            >
                            <button
                                v-if="transactions.next_page_url"
                                @click="router.get(transactions.next_page_url)"
                                class="rounded-lg bg-slate-100 px-3 py-1.5 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                            >
                                Selanjutnya
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create / Edit Modal -->
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
                        {{
                            editingTransaction
                                ? 'Edit Transaksi'
                                : 'Tambah Transaksi Baru'
                        }}
                    </h3>
                    <button
                        @click="showModal = false"
                        class="text-slate-400 hover:text-slate-700 dark:hover:text-white"
                    >
                        ✕
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="mt-4 space-y-4">
                    <div>
                        <label
                            class="mb-1 block text-xs font-medium text-slate-700 dark:text-slate-300"
                            >Kategori</label
                        >
                        <select
                            v-model="form.category_id"
                            required
                            class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-900 outline-none focus:border-pink-500 dark:border-white/10 dark:bg-slate-900 dark:text-slate-100"
                        >
                            <option value="" disabled>Pilih Kategori</option>
                            <option
                                v-for="cat in categories"
                                :key="cat.id"
                                :value="cat.id"
                            >
                                {{ cat.nama }} ({{ cat.tipe }})
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-xs font-medium text-slate-700 dark:text-slate-300"
                            >Nominal (Rp)</label
                        >
                        <input
                            v-model="form.nominal"
                            type="number"
                            min="1"
                            step="any"
                            required
                            placeholder="cth: 50000"
                            class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-900 outline-none focus:border-pink-500 dark:border-white/10 dark:bg-slate-900 dark:text-slate-100"
                        />
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-xs font-medium text-slate-700 dark:text-slate-300"
                            >Deskripsi</label
                        >
                        <input
                            v-model="form.deskripsi"
                            type="text"
                            required
                            placeholder="cth: Beli makan siang nasi padang"
                            class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-900 outline-none focus:border-pink-500 dark:border-white/10 dark:bg-slate-900 dark:text-slate-100"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label
                                class="mb-1 block text-xs font-medium text-slate-700 dark:text-slate-300"
                                >Tanggal</label
                            >
                            <input
                                v-model="form.tanggal_transaksi"
                                type="date"
                                required
                                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-900 outline-none focus:border-pink-500 dark:border-white/10 dark:bg-slate-900 dark:text-slate-100"
                            />
                        </div>

                        <div>
                            <label
                                class="mb-1 block text-xs font-medium text-slate-700 dark:text-slate-300"
                                >Metode</label
                            >
                            <select
                                v-model="form.metode_pembayaran"
                                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-900 outline-none focus:border-pink-500 dark:border-white/10 dark:bg-slate-900 dark:text-slate-100"
                            >
                                <option value="Cash">Cash</option>
                                <option value="QRIS">QRIS</option>
                                <option value="Transfer Bank">
                                    Transfer Bank
                                </option>
                                <option value="E-Wallet">E-Wallet</option>
                            </select>
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
                            {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
