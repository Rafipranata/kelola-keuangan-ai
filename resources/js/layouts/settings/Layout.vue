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
    <div class="px-2 py-4 sm:px-4 sm:py-6 max-w-5xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                Pengaturan Akun
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Kelola profil, keamanan, dan preferensi tampilan tema aplikasi Anda.
            </p>
        </div>

        <div class="flex flex-col md:flex-row gap-6">
            <!-- Sidebar Settings Navigation -->
            <aside class="w-full md:w-56 shrink-0">
                <nav class="flex md:flex-col gap-1 p-1.5 rounded-2xl bg-slate-100/80 dark:bg-slate-900/80 border border-slate-200/80 dark:border-white/10 backdrop-blur-md">
                    <Link
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        :href="item.href"
                        :class="[
                            'flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl text-xs sm:text-sm font-medium transition-all w-full',
                            isCurrentOrParentUrl(item.href)
                                ? 'bg-pink-500 text-white shadow-md shadow-pink-500/25 font-semibold'
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-white/5'
                        ]"
                    >
                        <component :is="item.icon" class="size-4 shrink-0" />
                        <span>{{ item.title }}</span>
                    </Link>
                </nav>
            </aside>

            <!-- Settings Content Panel -->
            <div class="flex-1 rounded-2xl bg-white/80 dark:bg-slate-900/70 border border-slate-200/80 dark:border-white/10 p-5 sm:p-7 backdrop-blur-md shadow-sm dark:shadow-none">
                <slot />
            </div>
        </div>
    </div>
</template>
