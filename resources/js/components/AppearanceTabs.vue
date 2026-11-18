<script setup lang="ts">
import { Monitor, Moon, Sun } from '@lucide/vue';
import { useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    { value: 'light', Icon: Sun, label: 'Terang (Light)' },
    { value: 'dark', Icon: Moon, label: 'Gelap (Dark)' },
    { value: 'system', Icon: Monitor, label: 'Sistem' },
] as const;
</script>

<template>
    <div
        class="inline-flex gap-1.5 rounded-2xl bg-slate-100 p-1.5 dark:bg-slate-950/80 border border-slate-200 dark:border-white/10"
    >
        <button
            v-for="{ value, Icon, label } in tabs"
            :key="value"
            @click="updateAppearance(value)"
            type="button"
            :class="[
                'flex items-center gap-2 rounded-xl px-4 py-2 text-xs sm:text-sm font-semibold transition-all',
                appearance === value
                    ? 'bg-pink-500 text-white shadow-md shadow-pink-500/30'
                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-slate-800/60',
            ]"
        >
            <component :is="Icon" class="size-4 shrink-0" />
            <span>{{ label }}</span>
        </button>
    </div>
</template>
