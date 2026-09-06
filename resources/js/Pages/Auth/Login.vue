<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Activity, Lock, Mail, ArrowRight, AlertCircle } from 'lucide-vue-next';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <Head title="Giriş Yap" />

    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <Link href="/" class="inline-flex items-center space-x-3 mb-6 group">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center shadow-lg shadow-indigo-500/20 group-hover:scale-105 transition-transform">
                    <Activity class="w-6 h-6 text-white" />
                </div>
                <span class="text-2xl font-bold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-white via-slate-200 to-slate-400">
                    Seovy
                </span>
            </Link>
            <h2 class="text-2xl font-bold tracking-tight text-white">Hesabınıza Giriş Yapın</h2>
            <p class="mt-2 text-sm text-slate-400">
                Teknik SEO platformunuza erişmek için bilgilerinizi girin.
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4">
            <div class="bg-slate-900/60 backdrop-blur-xl border border-slate-800/80 py-8 px-6 sm:px-10 shadow-2xl rounded-3xl">
                <!-- Errors -->
                <div v-if="form.errors.email" class="mb-4 p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs flex items-center space-x-2">
                    <AlertCircle class="w-4 h-4 shrink-0" />
                    <span>{{ form.errors.email }}</span>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                            E-Posta Adresi
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                                <Mail class="w-4 h-4" />
                            </div>
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                autofocus
                                class="w-full pl-10 pr-4 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all"
                                placeholder="ad@sirket.com"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                            Parola
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                                <Lock class="w-4 h-4" />
                            </div>
                            <input
                                v-model="form.password"
                                type="password"
                                required
                                class="w-full pl-10 pr-4 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all"
                                placeholder="••••••••"
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-xs">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="rounded bg-slate-800 border-slate-700 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="text-slate-400">Beni Hatırla</span>
                        </label>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold text-sm shadow-lg shadow-indigo-600/25 flex items-center justify-center space-x-2 transition-all disabled:opacity-50"
                    >
                        <span>Giriş Yap</span>
                        <ArrowRight class="w-4 h-4" />
                    </button>
                </form>

                <div class="mt-6 text-center text-xs text-slate-400 border-t border-slate-800 pt-5">
                    Hesabınız yok mu?
                    <Link href="/register" class="text-indigo-400 hover:text-indigo-300 font-semibold ml-1">
                        Ücretsiz Kayıt Olun
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
