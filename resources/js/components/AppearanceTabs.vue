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
        class="inline-flex gap-1.5 rounded-2xl border border-slate-200 bg-slate-100 p-1.5 dark:border-white/10 dark:bg-slate-950/80"
    >
        <button
            v-for="{ value, Icon, label } in tabs"
            :key="value"
            @click="updateAppearance(value)"
            type="button"
            :class="[
                'flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-semibold transition-all sm:text-sm',
                appearance === value
                    ? 'bg-pink-500 text-white shadow-md shadow-pink-500/30'
                    : 'text-slate-600 hover:bg-slate-200/60 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/60 dark:hover:text-white',
            ]"
        >
            <component :is="Icon" class="size-4 shrink-0" />
            <span>{{ label }}</span>
        </button>
    </div>
</template>
