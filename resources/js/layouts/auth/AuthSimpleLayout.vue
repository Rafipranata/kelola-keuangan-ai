<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogo from '@/components/AppLogo.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col items-center justify-center bg-slate-50 p-6 transition-colors duration-200 md:p-10 dark:bg-[#090d16]"
    >
        <!-- Glow accents halus di background khas KelolaUang AI -->
        <div
            class="pointer-events-none fixed -top-40 right-0 h-96 w-96 rounded-full bg-pink-500/10 blur-3xl dark:bg-pink-500/10"
        ></div>
        <div
            class="pointer-events-none fixed top-1/2 -left-40 h-96 w-96 rounded-full bg-purple-500/10 blur-3xl dark:bg-purple-500/10"
        ></div>

        <div class="relative z-10 w-full max-w-md">
            <div
                class="rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-xl shadow-slate-200/50 backdrop-blur-xl transition-all duration-200 sm:p-10 dark:border-white/10 dark:bg-slate-900/80 dark:shadow-pink-500/5"
            >
                <div class="flex flex-col gap-6">
                    <div class="flex flex-col items-center gap-4">
                        <Link :href="home()" class="flex flex-col items-center">
                            <AppLogo />
                        </Link>
                        <div class="space-y-1.5 text-center">
                            <h1
                                class="text-xl font-bold tracking-tight text-slate-900 dark:text-white"
                            >
                                {{ title }}
                            </h1>
                            <p
                                class="text-center text-xs text-slate-500 sm:text-sm dark:text-slate-400"
                            >
                                {{ description }}
                            </p>
                        </div>
                    </div>
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
