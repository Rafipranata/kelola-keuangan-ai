<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Lupa Password',
        description: 'Masukkan email Anda untuk menerima tautan reset password',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Lupa Password" />

    <div
        v-if="status"
        class="mb-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3 text-center text-sm font-medium text-emerald-600 dark:text-emerald-400"
    >
        {{ status }}
    </div>

    <div class="space-y-5">
        <Form v-bind="email.form()" v-slot="{ errors, processing }">
            <div class="grid gap-1.5">
                <Label
                    for="email"
                    class="text-xs font-semibold text-slate-700 dark:text-slate-300"
                    >Alamat Email</Label
                >
                <Input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="off"
                    v-focus
                    placeholder="nama@email.com"
                    class="rounded-xl border-slate-300 bg-slate-50 text-sm focus:border-pink-500 focus:ring-1 focus:ring-pink-500 dark:border-white/10 dark:bg-slate-950/80 dark:text-slate-100"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="my-5 flex items-center justify-start">
                <Button
                    class="flex w-full transform items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-pink-500 to-rose-600 py-2.5 text-sm font-semibold text-white shadow-lg shadow-pink-500/25 transition hover:from-pink-600 hover:to-rose-700 active:scale-95 disabled:opacity-50"
                    :disabled="processing"
                    data-test="email-password-reset-link-button"
                >
                    <Spinner v-if="processing" />
                    <span>Kirim Tautan Reset</span>
                </Button>
            </div>
        </Form>

        <div
            class="text-center text-xs text-slate-500 sm:text-sm dark:text-slate-400"
        >
            <span>Kembali ke halaman</span>
            <Link
                :href="login()"
                class="ml-1 font-semibold text-pink-600 underline-offset-4 hover:underline dark:text-pink-400"
                >Masuk</Link
            >
        </div>
    </div>
</template>
