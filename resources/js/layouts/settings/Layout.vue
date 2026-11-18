<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Lock, Palette, User } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profil',
        href: editProfile(),
        icon: User,
    },
    {
        title: 'Keamanan',
        href: editSecurity(),
        icon: Lock,
    },
    {
        title: 'Tampilan & Tema',
        href: editAppearance(),
        icon: Palette,
    },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="mx-auto max-w-5xl space-y-6 px-2 py-4 sm:px-4 sm:py-6">
        <div>
            <h1
                class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white"
            >
                Pengaturan Akun
            </h1>
            <p
                class="mt-1 text-xs text-slate-500 sm:text-sm dark:text-slate-400"
            >
                Kelola profil, keamanan, dan preferensi tampilan tema aplikasi
                Anda.
            </p>
        </div>

        <div class="flex flex-col gap-6 md:flex-row">
            <!-- Sidebar Settings Navigation -->
            <aside class="w-full shrink-0 md:w-56">
                <nav
                    class="flex gap-1 rounded-2xl border border-slate-200/80 bg-slate-100/80 p-1.5 backdrop-blur-md md:flex-col dark:border-white/10 dark:bg-slate-900/80"
                >
                    <Link
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        :href="item.href"
                        :class="[
                            'flex w-full items-center gap-2.5 rounded-xl px-3.5 py-2.5 text-xs font-medium transition-all sm:text-sm',
                            isCurrentOrParentUrl(item.href)
                                ? 'bg-pink-500 font-semibold text-white shadow-md shadow-pink-500/25'
                                : 'text-slate-600 hover:bg-slate-200/60 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-white/5 dark:hover:text-white',
                        ]"
                    >
                        <component :is="item.icon" class="size-4 shrink-0" />
                        <span>{{ item.title }}</span>
                    </Link>
                </nav>
            </aside>

            <!-- Settings Content Panel -->
            <div
                class="flex-1 rounded-2xl border border-slate-200/80 bg-white/80 p-5 shadow-sm backdrop-blur-md sm:p-7 dark:border-white/10 dark:bg-slate-900/70 dark:shadow-none"
            >
                <slot />
            </div>
        </div>
    </div>
</template>
