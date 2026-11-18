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
    const meta = document.querySelector(
        'meta[name="csrf-token"]',
    ) as HTMLMetaElement | null;
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
                Accept: 'application/json',
            },
            body: JSON.stringify({ prompt: quickPrompt.value }),
        });

        const data = await response.json();
        if (response.ok && data.success) {
            showQuickAiModal.value = false;
            quickPrompt.value = '';
            window.location.reload();
        } else {
            aiError.value =
                data.message || 'Gagal memproses transaksi dengan AI.';
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
    <div
        class="relative flex min-h-screen flex-col bg-slate-50 text-slate-800 transition-colors duration-200 selection:bg-pink-500 selection:text-white dark:bg-[#090d16] dark:text-slate-100"
    >
        <!-- Glow accents halus di background -->
        <div
            class="pointer-events-none fixed -top-40 right-0 h-96 w-96 rounded-full bg-pink-500/10 blur-3xl dark:bg-pink-500/10"
        ></div>
        <div
            class="pointer-events-none fixed top-1/2 -left-40 h-96 w-96 rounded-full bg-purple-500/10 blur-3xl dark:bg-purple-500/10"
        ></div>

        <!-- Top Header for Mobile (< lg) -->
        <header
            class="sticky top-0 z-40 flex h-16 items-center justify-between border-b border-slate-200 bg-white/80 px-4 backdrop-blur-md lg:hidden dark:border-white/10 dark:bg-[#090d16]/80"
        >
            <Link href="/dashboard" class="flex items-center">
                <AppLogo />
            </Link>

            <div class="flex items-center gap-2">
                <button
                    v-if="canInstallPwa"
                    @click="installPwa"
                    class="flex items-center gap-1.5 rounded-lg border border-pink-500/30 bg-pink-500/10 px-2.5 py-1.5 text-xs font-medium text-pink-600 transition-all hover:bg-pink-500/20 dark:text-pink-400"
                >
                    <Download class="size-3.5" />
                    <span>Install</span>
                </button>
            </div>
        </header>

        <div class="flex flex-1">
            <!-- Desktop Sidebar (lg:flex) -->
            <aside
                class="sticky top-0 z-30 hidden h-screen w-64 flex-col justify-between border-r border-slate-200 bg-white/85 p-4 backdrop-blur-md transition-colors duration-200 lg:flex dark:border-white/10 dark:bg-[#0c121e]/80"
            >
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
                                'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all',
                                isNavActive('/dashboard')
                                    ? 'border border-pink-500/30 bg-pink-500/15 font-semibold text-pink-600 shadow-sm shadow-pink-500/10 dark:text-pink-400'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-white/5 dark:hover:text-slate-200',
                            ]"
                        >
                            <LayoutDashboard class="size-4" />
                            <span>Dashboard</span>
                        </Link>

                        <Link
                            href="/transactions"
                            :class="[
                                'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all',
                                isNavActive('/transactions')
                                    ? 'border border-pink-500/30 bg-pink-500/15 font-semibold text-pink-600 shadow-sm shadow-pink-500/10 dark:text-pink-400'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-white/5 dark:hover:text-slate-200',
                            ]"
                        >
                            <ReceiptText class="size-4" />
                            <span>Transaksi</span>
                        </Link>

                        <Link
                            href="/budgets"
                            :class="[
                                'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all',
                                isNavActive('/budgets')
                                    ? 'border border-pink-500/30 bg-pink-500/15 font-semibold text-pink-600 shadow-sm shadow-pink-500/10 dark:text-pink-400'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-white/5 dark:hover:text-slate-200',
                            ]"
                        >
                            <PieChart class="size-4" />
                            <span>Anggaran</span>
                        </Link>

                        <Link
                            href="/settings/profile"
                            :class="[
                                'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all',
                                isNavActive('/settings')
                                    ? 'border border-pink-500/30 bg-pink-500/15 font-semibold text-pink-600 shadow-sm shadow-pink-500/10 dark:text-pink-400'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-white/5 dark:hover:text-slate-200',
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
                            class="flex w-full transform items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-pink-500 to-rose-600 px-3 py-2.5 text-sm font-semibold text-white shadow-md shadow-pink-500/25 transition-all hover:from-pink-600 hover:to-rose-700 active:scale-95"
                        >
                            <Sparkles class="size-4" />
                            <span>Catat Cepat (AI)</span>
                        </button>
                    </div>

                    <!-- Install App PWA Banner (Desktop) -->
                    <div
                        v-if="canInstallPwa"
                        class="rounded-xl border border-pink-500/20 bg-pink-500/5 p-3 text-center"
                    >
                        <p
                            class="mb-2 text-xs text-slate-600 dark:text-slate-300"
                        >
                            Install aplikasi untuk akses cepat tanpa browser!
                        </p>
                        <button
                            @click="installPwa"
                            class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-pink-500 py-1.5 text-xs font-semibold text-white transition hover:bg-pink-600"
                        >
                            <Download class="size-3.5" />
                            <span>Install App</span>
                        </button>
                    </div>
                </div>

                <!-- Footer user profile & Logout (Desktop) -->
                <div
                    class="border-t border-slate-200 pt-3 dark:border-white/10"
                >
                    <div
                        class="flex items-center justify-between rounded-xl border border-slate-200/80 bg-slate-100/80 p-2 dark:border-white/5 dark:bg-slate-900/60"
                    >
                        <Link
                            href="/settings/profile"
                            class="group flex flex-1 items-center gap-2.5 overflow-hidden"
                        >
                            <div
                                class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-pink-500/20 text-xs font-bold text-pink-600 uppercase transition group-hover:bg-pink-500/30 dark:text-pink-400"
                            >
                                {{
                                    authUser?.name
                                        ? authUser.name.charAt(0)
                                        : 'U'
                                }}
                            </div>
                            <div class="truncate text-left text-xs">
                                <p
                                    class="truncate font-medium text-slate-900 transition group-hover:text-pink-600 dark:text-slate-200 dark:group-hover:text-pink-400"
                                >
                                    {{ authUser?.name || 'User' }}
                                </p>
                                <p class="truncate text-[10px] text-slate-500">
                                    {{ authUser?.email || '' }}
                                </p>
                            </div>
                        </Link>

                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            class="ml-1 shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-200 hover:text-rose-500 dark:hover:bg-slate-800"
                            title="Keluar"
                        >
                            <LogOut class="size-4" />
                        </Link>
                    </div>
                </div>
            </aside>

            <!-- Main Content Area -->
            <main class="flex min-w-0 flex-1 flex-col pb-24 lg:pb-8">
                <div class="mx-auto w-full max-w-7xl p-4 sm:p-6 lg:p-8">
                    <slot />
                </div>
            </main>
        </div>

        <!-- Mobile Bottom Navigation Bar (< lg) -->
        <nav
            class="fixed right-0 bottom-0 left-0 z-40 flex items-center justify-around border-t border-slate-200 bg-white/90 px-4 py-2 backdrop-blur-lg lg:hidden dark:border-white/10 dark:bg-slate-950/90"
        >
            <Link
                href="/dashboard"
                :class="[
                    'flex flex-col items-center gap-1 text-xs transition-colors',
                    isNavActive('/dashboard')
                        ? 'font-semibold text-pink-600 dark:text-pink-400'
                        : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200',
                ]"
            >
                <LayoutDashboard class="size-5" />
                <span>Home</span>
            </Link>

            <Link
                href="/transactions"
                :class="[
                    'flex flex-col items-center gap-1 text-xs transition-colors',
                    isNavActive('/transactions')
                        ? 'font-semibold text-pink-600 dark:text-pink-400'
                        : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200',
                ]"
            >
                <ReceiptText class="size-5" />
                <span>Transaksi</span>
            </Link>

            <!-- Middle Pop AI Button -->
            <div class="-mt-6 flex flex-col items-center">
                <button
                    @click="triggerAiModal"
                    class="flex size-13 items-center justify-center rounded-full bg-gradient-to-r from-pink-500 to-rose-600 text-white shadow-lg ring-4 shadow-pink-500/40 ring-slate-50 transition-transform active:scale-95 dark:ring-[#090d16]"
                    title="Catat AI"
                >
                    <Sparkles class="size-6" />
                </button>
                <span
                    class="mt-1 text-[10px] font-semibold text-pink-600 dark:text-pink-400"
                    >Catat AI</span
                >
            </div>

            <Link
                href="/budgets"
                :class="[
                    'flex flex-col items-center gap-1 text-xs transition-colors',
                    isNavActive('/budgets')
                        ? 'font-semibold text-pink-600 dark:text-pink-400'
                        : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200',
                ]"
            >
                <PieChart class="size-5" />
                <span>Anggaran</span>
            </Link>

            <!-- Mobile Profil Menu Trigger -->
            <button
                @click="showMobileProfileModal = true"
                :class="[
                    'flex flex-col items-center gap-1 text-xs transition-colors',
                    isNavActive('/settings') || showMobileProfileModal
                        ? 'font-semibold text-pink-600 dark:text-pink-400'
                        : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200',
                ]"
            >
                <User class="size-5" />
                <span>Profil</span>
            </button>
        </nav>

        <!-- Mobile Profile Bottom Sheet / Modal -->
        <div
            v-if="showMobileProfileModal"
            class="fixed inset-0 z-50 flex flex-col justify-end bg-black/70 backdrop-blur-sm transition-opacity lg:hidden"
            @click.self="showMobileProfileModal = false"
        >
            <div
                class="w-full animate-in space-y-4 rounded-t-3xl border-t border-slate-200 bg-white p-6 shadow-2xl duration-200 slide-in-from-bottom dark:border-white/10 dark:bg-[#0c121e]"
            >
                <!-- Handle bar & Header -->
                <div
                    class="flex items-center justify-between border-b border-slate-200 pb-3 dark:border-white/10"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex size-11 items-center justify-center rounded-xl bg-gradient-to-tr from-pink-500 to-rose-500 text-base font-bold text-white shadow-md shadow-pink-500/25"
                        >
                            {{ authUser?.name ? authUser.name.charAt(0) : 'U' }}
                        </div>
                        <div>
                            <h3
                                class="text-base leading-tight font-bold text-slate-900 dark:text-white"
                            >
                                {{ authUser?.name || 'User' }}
                            </h3>
                            <p
                                class="text-xs text-slate-500 dark:text-slate-400"
                            >
                                {{ authUser?.email || '' }}
                            </p>
                        </div>
                    </div>
                    <button
                        @click="showMobileProfileModal = false"
                        class="rounded-full bg-slate-100 p-1.5 text-slate-500 hover:text-slate-900 dark:bg-slate-800 dark:text-slate-400 dark:hover:text-white"
                    >
                        <X class="size-4" />
                    </button>
                </div>

                <!-- Navigation List inside Profile Modal -->
                <div class="space-y-2 pt-1">
                    <Link
                        href="/settings/profile"
                        @click="showMobileProfileModal = false"
                        class="flex w-full items-center gap-3 rounded-xl border border-slate-200/80 bg-slate-100/80 p-3 text-sm font-medium text-slate-700 transition hover:bg-slate-200 dark:border-white/5 dark:bg-slate-900/80 dark:text-slate-200 dark:hover:bg-slate-800"
                    >
                        <User class="size-4 text-pink-500" />
                        <span>Edit Profil Akun</span>
                    </Link>

                    <Link
                        href="/settings/security"
                        @click="showMobileProfileModal = false"
                        class="flex w-full items-center gap-3 rounded-xl border border-slate-200/80 bg-slate-100/80 p-3 text-sm font-medium text-slate-700 transition hover:bg-slate-200 dark:border-white/5 dark:bg-slate-900/80 dark:text-slate-200 dark:hover:bg-slate-800"
                    >
                        <Settings class="size-4 text-pink-500" />
                        <span>Keamanan & Password</span>
                    </Link>

                    <Link
                        href="/settings/appearance"
                        @click="showMobileProfileModal = false"
                        class="flex w-full items-center gap-3 rounded-xl border border-slate-200/80 bg-slate-100/80 p-3 text-sm font-medium text-slate-700 transition hover:bg-slate-200 dark:border-white/5 dark:bg-slate-900/80 dark:text-slate-200 dark:hover:bg-slate-800"
                    >
                        <Sparkles class="size-4 text-pink-500" />
                        <span>Tema & Tampilan (Light / Dark)</span>
                    </Link>
                </div>

                <!-- Tombol Keluar (Logout) Terintegrasi di Menu Profil HP -->
                <div
                    class="border-t border-slate-200 pt-2 dark:border-white/10"
                >
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="flex w-full items-center justify-center gap-2 rounded-xl border border-rose-500/30 bg-rose-500/10 py-3 text-sm font-semibold text-rose-600 transition hover:bg-rose-500/20 active:scale-98 dark:bg-rose-500/15 dark:text-rose-400"
                    >
                        <LogOut class="size-4" />
                        <span>Keluar dari Akun (Logout)</span>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Quick AI Modal / Drawer -->
        <div
            v-if="showQuickAiModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
        >
            <div
                class="relative w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-white/10 dark:bg-[#0f172a]"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-200 pb-3 dark:border-white/10"
                >
                    <div class="flex items-center gap-2">
                        <div
                            class="rounded-xl bg-pink-500/15 p-2 text-pink-500"
                        >
                            <Sparkles class="size-5" />
                        </div>
                        <div>
                            <h3
                                class="text-base font-bold text-slate-900 dark:text-white"
                            >
                                Smart AI Transaction Parser
                            </h3>
                            <p
                                class="text-xs text-slate-500 dark:text-slate-400"
                            >
                                Ketik bebas dalam Bahasa Indonesia
                            </p>
                        </div>
                    </div>
                    <button
                        @click="showQuickAiModal = false"
                        class="px-2 py-1 text-lg font-semibold text-slate-400 hover:text-slate-700 dark:hover:text-white"
                    >
                        ✕
                    </button>
                </div>

                <div class="mt-4 space-y-3">
                    <label
                        class="text-xs font-medium text-slate-600 dark:text-slate-300"
                        >Contoh: "Beli ayam geprek 18rb pakai QRIS" atau "Gaji
                        bulanan 6.5jt"</label
                    >
                    <textarea
                        v-model="quickPrompt"
                        rows="3"
                        class="w-full rounded-xl border border-slate-300 bg-slate-50 p-3 text-sm text-slate-900 placeholder-slate-400 transition outline-none focus:border-pink-500 focus:ring-1 focus:ring-pink-500 dark:border-white/10 dark:bg-slate-900/80 dark:text-slate-100 dark:placeholder-slate-500"
                        placeholder="Ketik transaksi Anda di sini..."
                        :disabled="isSubmittingAi"
                    ></textarea>

                    <div
                        v-if="aiError"
                        class="rounded-lg border border-rose-500/30 bg-rose-500/15 p-3 text-xs text-rose-500 dark:text-rose-300"
                    >
                        {{ aiError }}
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="showQuickAiModal = false"
                            class="rounded-xl bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-200 hover:text-slate-900 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-white"
                            :disabled="isSubmittingAi"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="submitQuickAi"
                            :disabled="isSubmittingAi || !quickPrompt.trim()"
                            class="flex transform items-center gap-2 rounded-xl bg-gradient-to-r from-pink-500 to-rose-600 px-5 py-2 text-xs font-semibold text-white shadow-md shadow-pink-500/30 transition hover:from-pink-600 hover:to-rose-700 active:scale-95 disabled:opacity-50"
                        >
                            <span
                                v-if="isSubmittingAi"
                                class="inline-block size-3.5 animate-spin rounded-full border-2 border-white border-t-transparent"
                            ></span>
                            <Sparkles v-else class="size-3.5" />
                            <span>{{
                                isSubmittingAi
                                    ? 'Memproses AI...'
                                    : 'Simpan Transaksi'
                            }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <Toaster />
    </div>
</template>
