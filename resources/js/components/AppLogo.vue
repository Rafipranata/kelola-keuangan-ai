<script setup lang="ts">
import { Sparkles, Wallet } from '@lucide/vue';
</script>

<template>
    <div class="flex items-center gap-2.5">
        <div
            class="flex size-9 items-center justify-center rounded-xl bg-gradient-to-tr from-pink-500 to-rose-500 shadow-md shadow-pink-500/25 ring-1 ring-white/20"
        >
            <Wallet class="size-5 text-white" />
        </div>
        <div class="flex flex-col text-left">
            <span class="text-base font-bold tracking-tight text-pink-600 dark:text-white flex items-center gap-1.5 transition-colors">
                KelolaUang <span class="text-pink-500 dark:text-pink-400 font-extrabold flex items-center">AI <Sparkles class="size-3.5 ml-0.5 inline text-pink-500 dark:text-pink-400" /></span>
            </span>
            <span class="text-[10px] -mt-1 font-medium text-slate-500 dark:text-slate-400 tracking-wider uppercase">
                Smart Personal Finance
            </span>
        </div>
    </div>
</template>
