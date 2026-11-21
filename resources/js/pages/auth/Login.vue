<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Masuk ke Akun Anda',
        description: 'Masukkan email dan password Anda untuk mengelola keuangan',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head title="Masuk" />

    <div
        v-if="status"
        class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3 text-center text-sm font-medium text-emerald-600 dark:text-emerald-400"
    >
        {{ status }}
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-5"
    >
        <div class="grid gap-4">
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
                    required
                    v-focus
                    :tabindex="1"
                    autocomplete="email"
                    placeholder="nama@email.com"
                    class="rounded-xl border-slate-300 bg-slate-50 text-sm focus:border-pink-500 focus:ring-1 focus:ring-pink-500 dark:border-white/10 dark:bg-slate-950/80 dark:text-slate-100"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-1.5">
                <div class="flex items-center justify-between">
                    <Label
                        for="password"
                        class="text-xs font-semibold text-slate-700 dark:text-slate-300"
                        >Password</Label
                    >
                    <Link
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-xs font-medium text-pink-600 transition hover:text-pink-500 dark:text-pink-400"
                        :tabindex="5"
                    >
                        Lupa password?
                    </Link>
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    placeholder="Masukkan password Anda"
                    class="rounded-xl border-slate-300 bg-slate-50 text-sm focus:border-pink-500 focus:ring-1 focus:ring-pink-500 dark:border-white/10 dark:bg-slate-950/80 dark:text-slate-100"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center justify-between pt-1">
                <Label
                    for="remember"
                    class="flex cursor-pointer items-center space-x-2 text-xs text-slate-600 dark:text-slate-400"
                >
                    <Checkbox id="remember" name="remember" :tabindex="3" />
                    <span>Ingat saya</span>
                </Label>
            </div>

            <Button
                type="submit"
                class="mt-2 flex w-full transform items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-pink-500 to-rose-600 py-2.5 text-sm font-semibold text-white shadow-lg shadow-pink-500/25 transition hover:from-pink-600 hover:to-rose-700 active:scale-95 disabled:opacity-50"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" />
                <span>Masuk Sekarang</span>
            </Button>
        </div>

        <div
            class="text-center text-xs text-slate-500 sm:text-sm dark:text-slate-400"
        >
            Belum memiliki akun?
            <Link
                :href="register()"
                :tabindex="5"
                class="font-semibold text-pink-600 underline-offset-4 hover:underline dark:text-pink-400"
            >
                Daftar sekarang
            </Link>
        </div>
    </Form>
</template>
