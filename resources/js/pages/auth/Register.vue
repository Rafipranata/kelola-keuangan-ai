<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Buat Akun Baru',
        description: 'Daftar sekarang untuk mulai mengelola keuangan pribadi Anda',
    },
});
</script>

<template>
    <Head title="Daftar Akun" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-5"
    >
        <div class="grid gap-4">
            <div class="grid gap-1.5">
                <Label
                    for="name"
                    class="text-xs font-semibold text-slate-700 dark:text-slate-300"
                    >Nama Lengkap</Label
                >
                <Input
                    id="name"
                    type="text"
                    required
                    v-focus
                    :tabindex="1"
                    autocomplete="name"
                    name="name"
                    placeholder="Nama lengkap Anda"
                    class="rounded-xl border-slate-300 bg-slate-50 text-sm focus:border-pink-500 focus:ring-1 focus:ring-pink-500 dark:border-white/10 dark:bg-slate-950/80 dark:text-slate-100"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-1.5">
                <Label
                    for="email"
                    class="text-xs font-semibold text-slate-700 dark:text-slate-300"
                    >Alamat Email</Label
                >
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="2"
                    autocomplete="email"
                    name="email"
                    placeholder="nama@email.com"
                    class="rounded-xl border-slate-300 bg-slate-50 text-sm focus:border-pink-500 focus:ring-1 focus:ring-pink-500 dark:border-white/10 dark:bg-slate-950/80 dark:text-slate-100"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-1.5">
                <Label
                    for="password"
                    class="text-xs font-semibold text-slate-700 dark:text-slate-300"
                    >Password</Label
                >
                <PasswordInput
                    id="password"
                    required
                    :tabindex="3"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Minimal 8 karakter"
                    :passwordrules="passwordRules"
                    class="rounded-xl border-slate-300 bg-slate-50 text-sm focus:border-pink-500 focus:ring-1 focus:ring-pink-500 dark:border-white/10 dark:bg-slate-950/80 dark:text-slate-100"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-1.5">
                <Label
                    for="password_confirmation"
                    class="text-xs font-semibold text-slate-700 dark:text-slate-300"
                    >Konfirmasi Password</Label
                >
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Ulangi password Anda"
                    :passwordrules="passwordRules"
                    class="rounded-xl border-slate-300 bg-slate-50 text-sm focus:border-pink-500 focus:ring-1 focus:ring-pink-500 dark:border-white/10 dark:bg-slate-950/80 dark:text-slate-100"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-2 flex w-full transform items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-pink-500 to-rose-600 py-2.5 text-sm font-semibold text-white shadow-lg shadow-pink-500/25 transition hover:from-pink-600 hover:to-rose-700 active:scale-95 disabled:opacity-50"
                tabindex="5"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                <span>Daftar Akun</span>
            </Button>
        </div>

        <div
            class="text-center text-xs text-slate-500 sm:text-sm dark:text-slate-400"
        >
            Sudah memiliki akun?
            <Link
                :href="login()"
                class="font-semibold text-pink-600 underline-offset-4 hover:underline dark:text-pink-400"
                :tabindex="6"
            >
                Masuk di sini
            </Link>
        </div>
    </Form>
</template>
