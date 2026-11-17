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
    router.get('/transactions', {
        search: filterSearch.value,
        tipe: filterTipe.value,
        category_id: filterCategory.value,
        bulan: filterBulan.value,
        tahun: filterTahun.value,
    }, { preserveState: true, replace: true });
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
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
                        <ReceiptText class="size-6 text-pink-500" />
                        <span>Daftar Transaksi</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-400">
                        Kelola seluruh arus kas masuk dan keluar secara terorganisir.
                    </p>
                </div>

                <button
                    @click="openCreateModal"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-pink-500 to-rose-600 hover:from-pink-600 hover:to-rose-700 shadow-md shadow-pink-500/25 transition transform active:scale-95"
                >
                    <Plus class="size-4" />
                    <span>Tambah Transaksi</span>
                </button>
            </div>

            <!-- Filter Card -->
            <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-4 backdrop-blur-md shadow-lg space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    <!-- Search Input -->
                    <div class="relative">
                        <Search class="absolute left-3 top-3 size-4 text-slate-500" />
                        <input
                            v-model="filterSearch"
                            @keyup.enter="handleFilter"
                            type="text"
                            placeholder="Cari deskripsi / metode..."
                            class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-white/10 bg-slate-950/70 text-slate-100 placeholder-slate-500 outline-none focus:border-pink-500"
                        />
                    </div>

                    <!-- Tipe Filter -->
                    <select
                        v-model="filterTipe"
                        @change="handleFilter"
                        class="px-3 py-2 text-xs rounded-xl border border-white/10 bg-slate-950/70 text-slate-100 outline-none focus:border-pink-500"
                    >
                        <option value="">Semua Tipe</option>
                        <option value="income">Pemasukan (Income)</option>
                        <option value="expense">Pengeluaran (Expense)</option>
                    </select>

                    <!-- Kategori Filter -->
                    <select
                        v-model="filterCategory"
                        @change="handleFilter"
                        class="px-3 py-2 text-xs rounded-xl border border-white/10 bg-slate-950/70 text-slate-100 outline-none focus:border-pink-500"
                    >
                        <option value="">Semua Kategori</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">
                            {{ c.nama }} ({{ c.tipe }})
                        </option>
                    </select>

                    <!-- Bulan Filter -->
                    <select
                        v-model="filterBulan"
                        @change="handleFilter"
                        class="px-3 py-2 text-xs rounded-xl border border-white/10 bg-slate-950/70 text-slate-100 outline-none focus:border-pink-500"
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
                            class="flex-1 py-2 rounded-xl bg-pink-500/20 text-pink-400 border border-pink-500/30 text-xs font-semibold hover:bg-pink-500/30 transition text-center"
                        >
                            Terapkan
                        </button>
                        <button
                            @click="resetFilter"
                            class="px-3 py-2 rounded-xl bg-slate-800 text-slate-400 text-xs font-medium hover:text-white transition"
                        >
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- Transactions List -->
            <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur-md shadow-lg overflow-hidden">
                <div v-if="transactions.data.length === 0" class="text-center py-12 text-slate-500 text-sm">
                    Tidak ada transaksi yang cocok dengan filter atau kriteria pencarian.
                </div>

                <div v-else>
                    <!-- Desktop Table -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-300">
                            <thead class="text-xs uppercase bg-slate-950/60 text-slate-400 border-b border-white/10">
                                <tr>
                                    <th class="py-3 px-4">Tanggal</th>
                                    <th class="py-3 px-4">Deskripsi</th>
                                    <th class="py-3 px-4">Kategori</th>
                                    <th class="py-3 px-4">Metode</th>
                                    <th class="py-3 px-4 text-right">Nominal</th>
                                    <th class="py-3 px-4 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr v-for="t in transactions.data" :key="t.id" class="hover:bg-white/5 transition">
                                    <td class="py-3 px-4 text-xs text-slate-400 whitespace-nowrap">{{ t.tanggal_transaksi }}</td>
                                    <td class="py-3 px-4 font-medium text-slate-200">
                                        <div class="flex items-center gap-2">
                                            <span>{{ t.deskripsi }}</span>
                                            <span
                                                v-if="t.is_ai_generated"
                                                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-pink-500/20 text-pink-400 border border-pink-500/30"
                                                title="Dicatat otomatis dengan AI"
                                            >
                                                <Sparkles class="size-2.5" /> AI
                                            </span>
                                        </div>
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
                                    <td class="py-3 px-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button
                                                @click="openEditModal(t)"
                                                class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition"
                                                title="Edit"
                                            >
                                                <Edit class="size-3.5" />
                                            </button>
                                            <button
                                                @click="deleteTransaction(t.id)"
                                                class="p-1.5 rounded-lg text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 transition"
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
                    <div class="md:hidden divide-y divide-white/5 p-2">
                        <div
                            v-for="t in transactions.data"
                            :key="t.id"
                            class="p-3 space-y-2"
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-medium text-sm text-slate-200">{{ t.deskripsi }}</span>
                                        <Sparkles v-if="t.is_ai_generated" class="size-3 text-pink-400" />
                                    </div>
                                    <div class="flex items-center gap-2 text-[11px] text-slate-400 mt-1">
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
                            <div class="flex justify-end gap-2 pt-1 border-t border-white/5">
                                <button
                                    @click="openEditModal(t)"
                                    class="text-xs text-slate-300 hover:text-white px-2 py-1 rounded bg-slate-800"
                                >
                                    Edit
                                </button>
                                <button
                                    @click="deleteTransaction(t.id)"
                                    class="text-xs text-rose-400 hover:text-rose-300 px-2 py-1 rounded bg-rose-500/10"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="p-4 border-t border-white/10 flex items-center justify-between text-xs text-slate-400">
                        <span>Total: {{ transactions.total }} transaksi</span>
                        <div class="flex items-center gap-2">
                            <button
                                v-if="transactions.prev_page_url"
                                @click="router.get(transactions.prev_page_url)"
                                class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200"
                            >
                                Sebelumnya
                            </button>
                            <span>Halaman {{ transactions.current_page }} dari {{ transactions.last_page }}</span>
                            <button
                                v-if="transactions.next_page_url"
                                @click="router.get(transactions.next_page_url)"
                                class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200"
                            >
                                Selanjutnya
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create / Edit Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-2xl border border-white/10 bg-[#0f172a] p-6 shadow-2xl relative">
                <div class="flex items-center justify-between pb-3 border-b border-white/10">
                    <h3 class="font-bold text-base text-white">
                        {{ editingTransaction ? 'Edit Transaksi' : 'Tambah Transaksi Baru' }}
                    </h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-white">✕</button>
                </div>

                <form @submit.prevent="submitForm" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Kategori</label>
                        <select
                            v-model="form.category_id"
                            required
                            class="w-full rounded-xl border border-white/10 bg-slate-900 px-3 py-2 text-sm text-slate-100 outline-none focus:border-pink-500"
                        >
                            <option value="" disabled>Pilih Kategori</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                {{ cat.nama }} ({{ cat.tipe }})
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Nominal (Rp)</label>
                        <input
                            v-model="form.nominal"
                            type="number"
                            min="1"
                            step="any"
                            required
                            placeholder="cth: 50000"
                            class="w-full rounded-xl border border-white/10 bg-slate-900 px-3 py-2 text-sm text-slate-100 outline-none focus:border-pink-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Deskripsi</label>
                        <input
                            v-model="form.deskripsi"
                            type="text"
                            required
                            placeholder="cth: Beli makan siang nasi padang"
                            class="w-full rounded-xl border border-white/10 bg-slate-900 px-3 py-2 text-sm text-slate-100 outline-none focus:border-pink-500"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Tanggal</label>
                            <input
                                v-model="form.tanggal_transaksi"
                                type="date"
                                required
                                class="w-full rounded-xl border border-white/10 bg-slate-900 px-3 py-2 text-sm text-slate-100 outline-none focus:border-pink-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Metode</label>
                            <select
                                v-model="form.metode_pembayaran"
                                class="w-full rounded-xl border border-white/10 bg-slate-900 px-3 py-2 text-sm text-slate-100 outline-none focus:border-pink-500"
                            >
                                <option value="Cash">Cash</option>
                                <option value="QRIS">QRIS</option>
                                <option value="Transfer Bank">Transfer Bank</option>
                                <option value="E-Wallet">E-Wallet</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-white/10">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-white bg-slate-800"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2 rounded-xl text-xs font-semibold text-white bg-pink-500 hover:bg-pink-600 disabled:opacity-50 transition"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
