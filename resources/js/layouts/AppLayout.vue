<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Download,
    LayoutDashboard,
    LogOut,
    PieChart,
    PlusCircle,
    ReceiptText,
    Settings,
    Sparkles,
    User,
    Wallet,
    X,
} from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import { Toaster } from '@/components/ui/sonner';

defineProps<{
    breadcrumbs?: Array<{ title: string; href?: string }>;
}>();

const page = usePage();
const currentUrl = computed(() => page.url);
const authUser = computed(() => (page.props.auth as any)?.user);

const isNavActive = (url: string) => {
    if (url === '/dashboard') {
        return currentUrl.value === '/dashboard' || currentUrl.value === '/';
    }
    return currentUrl.value.startsWith(url);
};

// PWA Install Prompt handling
const deferredPrompt = ref<any>(null);
const canInstallPwa = ref(false);

onMounted(() => {
    window.addEventListener('beforeinstallprompt', (e: Event) => {
        e.preventDefault();
        deferredPrompt.value = e;
        canInstallPwa.value = true;
    });

    window.addEventListener('appinstalled', () => {
        deferredPrompt.value = null;
        canInstallPwa.value = false;
    });
});

const installPwa = async () => {
    if (!deferredPrompt.value) return;
    deferredPrompt.value.prompt();
    const { outcome } = await deferredPrompt.value.userChoice;
    if (outcome === 'accepted') {
        canInstallPwa.value = false;
    }
    deferredPrompt.value = null;
};

// Global Quick Catat AI Modal trigger
const showQuickAiModal = ref(false);
const quickPrompt = ref('');
const isSubmittingAi = ref(false);
const aiError = ref<string | null>(null);

const triggerAiModal = () => {
    showQuickAiModal.value = true;
    aiError.value = null;
};

const getCsrfToken = () => {
    const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
    if (meta && meta.content) return meta.content;

    // Fallback ambil dari cookie XSRF-TOKEN bawaan Laravel jika ada
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
};

const submitQuickAi = async () => {
    if (!quickPrompt.value.trim() || isSubmittingAi.value) return;
    isSubmittingAi.value = true;
    aiError.value = null;

    try {
        const response = await fetch('/ai/parse-transaction', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-XSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({ prompt: quickPrompt.value }),
        });

        const data = await response.json();
        if (response.ok && data.success) {
            showQuickAiModal.value = false;
            quickPrompt.value = '';
            window.location.reload();
        } else {
            aiError.value = data.message || 'Gagal memproses transaksi dengan AI.';
        }
    } catch (err: any) {
        aiError.value = err.message || 'Terjadi kesalahan koneksi.';
    } finally {
        isSubmittingAi.value = false;
    }
};

// Mobile Profile Drawer state
const showMobileProfileModal = ref(false);
</script>

<template>
    <div class="min-h-screen bg-[#090d16] text-slate-100 flex flex-col relative selection:bg-pink-500 selection:text-white">
        <!-- Glow accents halus di background -->
        <div class="pointer-events-none fixed -top-40 right-0 h-96 w-96 rounded-full bg-pink-500/10 blur-3xl"></div>
        <div class="pointer-events-none fixed top-1/2 -left-40 h-96 w-96 rounded-full bg-purple-500/10 blur-3xl"></div>

        <!-- Top Header for Mobile (< lg) -->
        <header class="lg:hidden sticky top-0 z-40 flex h-16 items-center justify-between border-b border-white/10 bg-[#090d16]/80 px-4 backdrop-blur-md">
            <Link href="/dashboard" class="flex items-center">
                <AppLogo />
            </Link>

            <div class="flex items-center gap-2">
                <button
                    v-if="canInstallPwa"
                    @click="installPwa"
                    class="flex items-center gap-1.5 rounded-lg border border-pink-500/30 bg-pink-500/10 px-2.5 py-1.5 text-xs font-medium text-pink-400 hover:bg-pink-500/20 transition-all"
                >
                    <Download class="size-3.5" />
                    <span>Install</span>
                </button>
            </div>
        </header>

        <div class="flex flex-1">
            <!-- Desktop Sidebar (lg:flex) -->
            <aside class="hidden lg:flex w-64 flex-col border-r border-white/10 bg-[#0c121e]/80 backdrop-blur-md sticky top-0 h-screen p-4 justify-between z-30">
                <div class="space-y-6">
                    <div class="px-2 py-1">
                        <Link href="/dashboard">
                            <AppLogo />
                        </Link>
                    </div>

                    <!-- Navigation Items -->
                    <nav class="space-y-1.5">
                        <Link
                            href="/dashboard"
                            :class="[
                                'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all',
                                isNavActive('/dashboard')
                                    ? 'bg-pink-500/15 text-pink-400 border border-pink-500/30 shadow-sm shadow-pink-500/10'
                                    : 'text-slate-400 hover:text-slate-200 hover:bg-white/5'
                            ]"
                        >
                            <LayoutDashboard class="size-4" />
                            <span>Dashboard</span>
                        </Link>

                        <Link
                            href="/transactions"
                            :class="[
                                'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all',
                                isNavActive('/transactions')
                                    ? 'bg-pink-500/15 text-pink-400 border border-pink-500/30 shadow-sm shadow-pink-500/10'
                                    : 'text-slate-400 hover:text-slate-200 hover:bg-white/5'
                            ]"
                        >
                            <ReceiptText class="size-4" />
                            <span>Transaksi</span>
                        </Link>

                        <Link
                            href="/budgets"
                            :class="[
                                'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all',
                                isNavActive('/budgets')
                                    ? 'bg-pink-500/15 text-pink-400 border border-pink-500/30 shadow-sm shadow-pink-500/10'
                                    : 'text-slate-400 hover:text-slate-200 hover:bg-white/5'
                            ]"
                        >
                            <PieChart class="size-4" />
                            <span>Anggaran</span>
                        </Link>

                        <Link
                            href="/settings/profile"
                            :class="[
                                'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all',
                                isNavActive('/settings')
                                    ? 'bg-pink-500/15 text-pink-400 border border-pink-500/30 shadow-sm shadow-pink-500/10'
                                    : 'text-slate-400 hover:text-slate-200 hover:bg-white/5'
                            ]"
                        >
                            <User class="size-4" />
                            <span>Profil & Akun</span>
                        </Link>
                    </nav>

                    <!-- Quick Catat AI Button (Sidebar) -->
                    <div class="pt-2">
                        <button
                            @click="triggerAiModal"
                            class="w-full flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-pink-500 to-rose-600 hover:from-pink-600 hover:to-rose-700 shadow-md shadow-pink-500/25 transition-all transform active:scale-95"
                        >
                            <Sparkles class="size-4" />
                            <span>Catat Cepat (AI)</span>
                        </button>
                    </div>

                    <!-- Install App PWA Banner (Desktop) -->
                    <div v-if="canInstallPwa" class="rounded-xl border border-pink-500/20 bg-pink-500/5 p-3 text-center">
                        <p class="text-xs text-slate-300 mb-2">Install aplikasi untuk akses cepat tanpa browser!</p>
                        <button
                            @click="installPwa"
                            class="inline-flex items-center justify-center gap-1.5 w-full py-1.5 rounded-lg bg-pink-500 text-xs font-semibold text-white hover:bg-pink-600 transition"
                        >
                            <Download class="size-3.5" />
                            <span>Install App</span>
                        </button>
                    </div>
                </div>

                <!-- Footer user profile & Logout (Desktop) -->
                <div class="border-t border-white/10 pt-3">
                    <div class="flex items-center justify-between p-2 rounded-xl bg-slate-900/60 border border-white/5">
                        <Link href="/settings/profile" class="flex items-center gap-2.5 overflow-hidden flex-1 group">
                            <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-pink-500/20 text-pink-400 font-bold text-xs uppercase group-hover:bg-pink-500/30 transition">
                                {{ authUser?.name ? authUser.name.charAt(0) : 'U' }}
                            </div>
                            <div class="truncate text-left text-xs">
                                <p class="font-medium text-slate-200 truncate group-hover:text-pink-400 transition">{{ authUser?.name || 'User' }}</p>
                                <p class="text-[10px] text-slate-500 truncate">{{ authUser?.email || '' }}</p>
                            </div>
                        </Link>

                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            class="text-slate-400 hover:text-rose-400 p-1.5 rounded-lg hover:bg-slate-800 transition shrink-0 ml-1"
                            title="Keluar"
                        >
                            <LogOut class="size-4" />
                        </Link>
                    </div>
                </div>
            </aside>

            <!-- Main Content Area -->
            <main class="flex-1 flex flex-col min-w-0 pb-24 lg:pb-8">
                <div class="p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
                    <slot />
                </div>
            </main>
        </div>

        <!-- Mobile Bottom Navigation Bar (< lg) -->
        <nav class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-slate-950/90 backdrop-blur-lg border-t border-white/10 px-4 py-2 flex items-center justify-around">
            <Link
                href="/dashboard"
                :class="[
                    'flex flex-col items-center gap-1 text-xs transition-colors',
                    isNavActive('/dashboard') ? 'text-pink-400 font-semibold' : 'text-slate-400 hover:text-slate-200'
                ]"
            >
                <LayoutDashboard class="size-5" />
                <span>Home</span>
            </Link>

            <Link
                href="/transactions"
                :class="[
                    'flex flex-col items-center gap-1 text-xs transition-colors',
                    isNavActive('/transactions') ? 'text-pink-400 font-semibold' : 'text-slate-400 hover:text-slate-200'
                ]"
            >
                <ReceiptText class="size-5" />
                <span>Transaksi</span>
            </Link>

            <!-- Middle Pop AI Button -->
            <div class="-mt-6 flex flex-col items-center">
                <button
                    @click="triggerAiModal"
                    class="flex size-13 items-center justify-center rounded-full bg-gradient-to-r from-pink-500 to-rose-600 text-white shadow-lg shadow-pink-500/40 ring-4 ring-[#090d16] active:scale-95 transition-transform"
                    title="Catat AI"
                >
                    <Sparkles class="size-6" />
                </button>
                <span class="text-[10px] font-semibold text-pink-400 mt-1">Catat AI</span>
            </div>

            <Link
                href="/budgets"
                :class="[
                    'flex flex-col items-center gap-1 text-xs transition-colors',
                    isNavActive('/budgets') ? 'text-pink-400 font-semibold' : 'text-slate-400 hover:text-slate-200'
                ]"
            >
                <PieChart class="size-5" />
                <span>Anggaran</span>
            </Link>

            <!-- Mobile Profil Menu Trigger (Membuka panel profil dengan opsi logout) -->
            <button
                @click="showMobileProfileModal = true"
                :class="[
                    'flex flex-col items-center gap-1 text-xs transition-colors',
                    isNavActive('/settings') || showMobileProfileModal ? 'text-pink-400 font-semibold' : 'text-slate-400 hover:text-slate-200'
                ]"
            >
                <User class="size-5" />
                <span>Profil</span>
            </button>
        </nav>

        <!-- Mobile Profile Bottom Sheet / Modal -->
        <div
            v-if="showMobileProfileModal"
            class="lg:hidden fixed inset-0 z-50 flex flex-col justify-end bg-black/80 backdrop-blur-sm transition-opacity"
            @click.self="showMobileProfileModal = false"
        >
            <div class="w-full rounded-t-3xl border-t border-white/10 bg-[#0c121e] p-6 shadow-2xl space-y-4 animate-in slide-in-from-bottom duration-200">
                <!-- Handle bar & Header -->
                <div class="flex items-center justify-between pb-3 border-b border-white/10">
                    <div class="flex items-center gap-3">
                        <div class="flex size-11 items-center justify-center rounded-xl bg-gradient-to-tr from-pink-500 to-rose-500 text-white font-bold text-base shadow-md shadow-pink-500/25">
                            {{ authUser?.name ? authUser.name.charAt(0) : 'U' }}
                        </div>
                        <div>
                            <h3 class="font-bold text-base text-white leading-tight">{{ authUser?.name || 'User' }}</h3>
                            <p class="text-xs text-slate-400">{{ authUser?.email || '' }}</p>
                        </div>
                    </div>
                    <button
                        @click="showMobileProfileModal = false"
                        class="p-1.5 rounded-full bg-slate-800 text-slate-400 hover:text-white"
                    >
                        <X class="size-4" />
                    </button>
                </div>

                <!-- Navigation List inside Profile Modal -->
                <div class="space-y-2 pt-1">
                    <Link
                        href="/settings/profile"
                        @click="showMobileProfileModal = false"
                        class="flex items-center gap-3 w-full p-3 rounded-xl bg-slate-900/80 border border-white/5 text-sm font-medium text-slate-200 hover:bg-slate-800 transition"
                    >
                        <User class="size-4 text-pink-400" />
                        <span>Edit Profil Akun</span>
                    </Link>

                    <Link
                        href="/settings/security"
                        @click="showMobileProfileModal = false"
                        class="flex items-center gap-3 w-full p-3 rounded-xl bg-slate-900/80 border border-white/5 text-sm font-medium text-slate-200 hover:bg-slate-800 transition"
                    >
                        <Settings class="size-4 text-pink-400" />
                        <span>Keamanan & Password</span>
                    </Link>
                </div>

                <!-- Tombol Keluar (Logout) Terintegrasi di Menu Profil HP -->
                <div class="pt-2 border-t border-white/10">
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-rose-500/15 border border-rose-500/30 text-sm font-semibold text-rose-400 hover:bg-rose-500/25 transition active:scale-98"
                    >
                        <LogOut class="size-4" />
                        <span>Keluar dari Akun (Logout)</span>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Quick AI Modal / Drawer -->
        <div v-if="showQuickAiModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm">
            <div class="w-full max-w-lg rounded-2xl border border-white/10 bg-[#0f172a] p-6 shadow-2xl relative">
                <div class="flex items-center justify-between pb-3 border-b border-white/10">
                    <div class="flex items-center gap-2">
                        <div class="p-2 rounded-xl bg-pink-500/20 text-pink-400">
                            <Sparkles class="size-5" />
                        </div>
                        <div>
                            <h3 class="font-bold text-base text-white">Smart AI Transaction Parser</h3>
                            <p class="text-xs text-slate-400">Ketik bebas dalam Bahasa Indonesia</p>
                        </div>
                    </div>
                    <button
                        @click="showQuickAiModal = false"
                        class="text-slate-400 hover:text-white text-lg font-semibold px-2 py-1"
                    >
                        ✕
                    </button>
                </div>

                <div class="mt-4 space-y-3">
                    <label class="text-xs text-slate-300 font-medium">Contoh: "Beli ayam geprek 18rb pakai QRIS" atau "Gaji bulanan 6.5jt"</label>
                    <textarea
                        v-model="quickPrompt"
                        rows="3"
                        class="w-full rounded-xl border border-white/10 bg-slate-900/80 p-3 text-sm text-slate-100 placeholder-slate-500 focus:border-pink-500 focus:ring-1 focus:ring-pink-500 outline-none transition"
                        placeholder="Ketik transaksi Anda di sini..."
                        :disabled="isSubmittingAi"
                    ></textarea>

                    <div v-if="aiError" class="p-3 rounded-lg bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs">
                        {{ aiError }}
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="showQuickAiModal = false"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 transition"
                            :disabled="isSubmittingAi"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="submitQuickAi"
                            :disabled="isSubmittingAi || !quickPrompt.trim()"
                            class="flex items-center gap-2 px-5 py-2 rounded-xl text-xs font-semibold text-white bg-gradient-to-r from-pink-500 to-rose-600 hover:from-pink-600 hover:to-rose-700 disabled:opacity-50 shadow-md shadow-pink-500/30 transition transform active:scale-95"
                        >
                            <span v-if="isSubmittingAi" class="animate-spin inline-block size-3.5 border-2 border-white border-t-transparent rounded-full"></span>
                            <Sparkles v-else class="size-3.5" />
                            <span>{{ isSubmittingAi ? 'Memproses AI...' : 'Simpan Transaksi' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <Toaster />
    </div>
</template>
