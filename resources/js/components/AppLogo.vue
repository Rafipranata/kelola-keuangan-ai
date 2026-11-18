<script setup lang="ts">
import { Sparkles, Wallet } from '@lucide/vue';
</script>

<template>
    <div class="flex items-center gap-2.5">
        <div
            class="flex size-9 items-center justify-center rounded-xl bg-gradient-to-tr from-pink-500 to-rose-500 shadow-md ring-1 shadow-pink-500/25 ring-white/20"
        >
            <Wallet class="size-5 text-white" />
        </div>
        <div class="flex flex-col text-left">
            <span
                class="flex items-center gap-1.5 text-base font-bold tracking-tight text-pink-600 transition-colors dark:text-white"
            >
                KelolaUang
                <span
                    class="flex items-center font-extrabold text-pink-500 dark:text-pink-400"
                    >AI
                    <Sparkles
                        class="ml-0.5 inline size-3.5 text-pink-500 dark:text-pink-400"
                /></span>
            </span>
            <span
                class="-mt-1 text-[10px] font-medium tracking-wider text-slate-500 uppercase dark:text-slate-400"
            >
                Smart Personal Finance
            </span>
        </div>
    </div>
</template>
