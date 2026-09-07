<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Activity, Lock, Mail, User, ArrowRight, AlertCircle } from 'lucide-vue-next';
import LanguageSelector from '@/Components/LanguageSelector.vue';

const page = usePage();
const systemSettings = computed(() => ((page.props as any)?.system_settings) || {});
const logoDark = computed(() => systemSettings.value.logo_dark || systemSettings.value.logo || null);
const brandName = computed(() => systemSettings.value.brand_name || 'Seovy');
const logoFailed = ref(false);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/register', {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <Head :title="$t('auth.register_page_title')" />

    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8 relative">
        <!-- Language Switcher in top right -->
        <div class="absolute top-6 right-6">
            <LanguageSelector placement="bottom" />
        </div>

        <div class="sm:mx-auto sm:w-full sm:max-w-xl text-center px-4">
            <Link href="/" class="inline-flex items-center justify-center space-x-3 mb-6 group max-w-full">
                <template v-if="logoDark && !logoFailed">
                    <img
                        :key="logoDark"
                        :src="logoDark"
                        :alt="brandName"
                        class="h-[120px] max-h-[140px] max-w-full object-contain rounded-2xl shadow-sm group-hover:scale-105 transition-transform"
                        @error="logoFailed = true"
                        @load="logoFailed = false"
                    />
                </template>
                <template v-else>
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center shadow-lg shadow-indigo-500/20 group-hover:scale-105 transition-transform">
                        <Activity class="w-6 h-6 text-white" />
                    </div>
                    <span class="text-2xl font-bold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-white via-slate-200 to-slate-400">
                        {{ brandName }}
                    </span>
                </template>
            </Link>
            <h2 class="text-2xl font-bold tracking-tight text-white">{{ $t('auth.register_title') }}</h2>
            <p class="mt-2 text-sm text-slate-400">
                {{ $t('auth.register_subtitle') }}
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4">
            <div class="bg-slate-900/60 backdrop-blur-xl border border-slate-800/80 py-8 px-6 sm:px-10 shadow-2xl rounded-3xl">
                <!-- Errors -->
                <div v-if="Object.keys(form.errors).length > 0" class="mb-4 p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs space-y-1">
                    <div v-for="(err, field) in form.errors" :key="field" class="flex items-center space-x-2">
                        <AlertCircle class="w-4 h-4 shrink-0" />
                        <span>{{ err }}</span>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                            {{ $t('auth.name') }}
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                                <User class="w-4 h-4" />
                            </div>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                autofocus
                                class="w-full pl-10 pr-4 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all"
                                :placeholder="$t('auth.name_placeholder')"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                            {{ $t('auth.email') }}
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                                <Mail class="w-4 h-4" />
                            </div>
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                class="w-full pl-10 pr-4 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all"
                                :placeholder="$t('auth.email_placeholder')"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                            {{ $t('auth.password') }}
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
                                :placeholder="$t('auth.password_placeholder')"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                            {{ $t('auth.confirm_password_label') }}
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                                <Lock class="w-4 h-4" />
                            </div>
                            <input
                                v-model="form.password_confirmation"
                                type="password"
                                required
                                class="w-full pl-10 pr-4 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all"
                                :placeholder="$t('auth.password_placeholder')"
                            />
                        </div>
                    </div>

                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold text-sm shadow-lg shadow-indigo-600/25 flex items-center justify-center space-x-2 transition-all disabled:opacity-50"
                        >
                            <span>{{ $t('auth.create_account_button') }}</span>
                            <ArrowRight class="w-4 h-4" />
                        </button>
                    </div>
                </form>

                <div class="mt-6 text-center text-xs text-slate-400 border-t border-slate-800 pt-5">
                    {{ $t('auth.have_account') }}
                    <Link href="/login" class="text-indigo-400 hover:text-indigo-300 font-semibold ml-1">
                        {{ $t('auth.login_link') }}
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
