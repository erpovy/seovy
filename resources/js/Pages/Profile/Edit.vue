<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    User,
    Lock,
    Shield,
    Monitor,
    LogOut,
    CheckCircle2,
    AlertCircle,
    Smartphone,
    Sun,
    Moon,
    Laptop
} from 'lucide-vue-next';
import { useTheme } from '@/composables/useTheme';

const { currentTheme, isDark, setTheme } = useTheme();

const props = defineProps<{
    user: any;
    sessions: Array<any>;
}>();

const profileForm = useForm({
    name: props.user.name,
    email: props.user.email,
});

const submitProfile = () => {
    profileForm.patch('/profile');
};

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const submitPassword = () => {
    passwordForm.put('/profile/password', {
        onSuccess: () => passwordForm.reset(),
    });
};

const twoFactorForm = useForm({});
const toggle2FA = () => {
    if (props.user.two_factor_enabled) {
        if (confirm('Are you sure you want to disable two-factor authentication?')) {
            router.delete('/profile/two-factor');
        }
    } else {
        router.post('/profile/two-factor');
    }
};

const logoutOtherForm = useForm({
    password: '',
});
const logoutOtherSessions = () => {
    logoutOtherForm.post('/profile/logout-other-sessions', {
        onSuccess: () => logoutOtherForm.reset(),
    });
};
</script>

<template>
    <AppLayout :title="$t('profile.page_title')">
        <Head :title="$t('profile.page_title')" />

        <div class="max-w-4xl mx-auto space-y-8">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-white">{{ $t('profile.title') }}</h1>
                <p class="text-sm text-slate-400 mt-1">
                    {{ $t('profile.subtitle') }}
                </p>
            </div>

            <!-- Profile Info Form -->
            <form @submit.prevent="submitProfile" class="p-6 sm:p-8 rounded-3xl bg-slate-900/50 border border-slate-800/80 space-y-4">
                <h2 class="text-base font-bold text-white flex items-center space-x-2">
                    <User class="w-4 h-4 text-indigo-400" />
                    <span>{{ $t('profile.personal_info') }}</span>
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">{{ $t('profile.name_label') }}</label>
                        <input
                            v-model="profileForm.name"
                            type="text"
                            required
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white"
                        />
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">{{ $t('profile.email_label') }}</label>
                        <input
                            v-model="profileForm.email"
                            type="email"
                            required
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white"
                        />
                    </div>
                </div>

                <div class="pt-2 flex justify-end">
                    <button
                        type="submit"
                        :disabled="profileForm.processing"
                        class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs transition-all disabled:opacity-50"
                    >
                        {{ $t('profile.update_info') }}
                    </button>
                </div>
            </form>

            <!-- Password Update Form -->
            <form @submit.prevent="submitPassword" class="p-6 sm:p-8 rounded-3xl bg-slate-900/50 border border-slate-800/80 space-y-4">
                <h2 class="text-base font-bold text-white flex items-center space-x-2">
                    <Lock class="w-4 h-4 text-violet-400" />
                    <span>{{ $t('profile.change_password') }}</span>
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">{{ $t('profile.current_password') }}</label>
                        <input
                            v-model="passwordForm.current_password"
                            type="password"
                            required
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white"
                        />
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">{{ $t('profile.new_password') }}</label>
                        <input
                            v-model="passwordForm.password"
                            type="password"
                            required
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white"
                        />
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">{{ $t('profile.confirm_password') }}</label>
                        <input
                            v-model="passwordForm.password_confirmation"
                            type="password"
                            required
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white"
                        />
                    </div>
                </div>

                <div class="pt-2 flex justify-end">
                    <button
                        type="submit"
                        :disabled="passwordForm.processing"
                        class="px-4 py-2 rounded-xl bg-violet-600 hover:bg-violet-500 text-white font-semibold text-xs transition-all disabled:opacity-50"
                    >
                        {{ $t('profile.update_password') }}
                    </button>
                </div>
            </form>

            <!-- Two Factor Authentication -->
            <div class="p-6 sm:p-8 rounded-3xl bg-slate-900/50 border border-slate-800/80 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-white flex items-center space-x-2">
                        <Shield class="w-4 h-4 text-emerald-400" />
                        <span>{{ $t('profile.two_factor_title') }}</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-1 max-w-md">
                        {{ $t('profile.two_factor_desc') }}
                    </p>
                </div>

                <button
                    @click="toggle2FA"
                    class="px-4 py-2 rounded-xl text-xs font-semibold transition-all"
                    :class="user.two_factor_enabled ? 'bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20' : 'bg-emerald-600 hover:bg-emerald-500 text-white'"
                >
                    {{ user.two_factor_enabled ? $t('profile.disable_2fa') : $t('profile.enable_2fa') }}
                </button>
            </div>

            <!-- Interface & Theme Preference Card -->
            <div class="p-6 sm:p-8 rounded-3xl bg-slate-900/50 border border-slate-800/80 space-y-4">
                <div>
                    <h2 class="text-base font-bold text-white flex items-center space-x-2">
                        <Sun v-if="!isDark" class="w-4 h-4 text-amber-400" />
                        <Moon v-else class="w-4 h-4 text-indigo-400" />
                        <span>{{ $t('profile.theme_title') }}</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-1 max-w-md">
                        {{ $t('profile.theme_desc') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                    <button
                        type="button"
                        @click="setTheme('light')"
                        class="p-4 rounded-2xl border flex items-center space-x-3 transition-all text-left"
                        :class="currentTheme === 'light'
                            ? 'bg-indigo-600/15 border-indigo-500 text-white font-semibold ring-1 ring-indigo-500'
                            : 'bg-slate-950/60 hover:bg-slate-950 border-slate-800 text-slate-300'"
                    >
                        <div class="w-9 h-9 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center shrink-0">
                            <Sun class="w-5 h-5" />
                        </div>
                        <div>
                            <div class="text-xs font-bold text-white">{{ $t('profile.theme_light') }}</div>
                            <div class="text-[10px] text-slate-400 mt-0.5">{{ $t('profile.theme_light_hint') }}</div>
                        </div>
                    </button>

                    <button
                        type="button"
                        @click="setTheme('dark')"
                        class="p-4 rounded-2xl border flex items-center space-x-3 transition-all text-left"
                        :class="currentTheme === 'dark'
                            ? 'bg-indigo-600/15 border-indigo-500 text-white font-semibold ring-1 ring-indigo-500'
                            : 'bg-slate-950/60 hover:bg-slate-950 border-slate-800 text-slate-300'"
                    >
                        <div class="w-9 h-9 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center shrink-0">
                            <Moon class="w-5 h-5" />
                        </div>
                        <div>
                            <div class="text-xs font-bold text-white">{{ $t('profile.theme_dark') }}</div>
                            <div class="text-[10px] text-slate-400 mt-0.5">{{ $t('profile.theme_dark_hint') }}</div>
                        </div>
                    </button>

                    <button
                        type="button"
                        @click="setTheme('system')"
                        class="p-4 rounded-2xl border flex items-center space-x-3 transition-all text-left"
                        :class="currentTheme === 'system'
                            ? 'bg-indigo-600/15 border-indigo-500 text-white font-semibold ring-1 ring-indigo-500'
                            : 'bg-slate-950/60 hover:bg-slate-950 border-slate-800 text-slate-300'"
                    >
                        <div class="w-9 h-9 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center shrink-0">
                            <Laptop class="w-5 h-5" />
                        </div>
                        <div>
                            <div class="text-xs font-bold text-white">{{ $t('profile.theme_system') }}</div>
                            <div class="text-[10px] text-slate-400 mt-0.5">{{ $t('profile.theme_system_hint') }}</div>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Active Sessions Section -->
            <div v-if="sessions && sessions.length > 0" class="p-6 sm:p-8 rounded-3xl bg-slate-900/50 border border-slate-800/80 space-y-4">
                <h2 class="text-base font-bold text-white flex items-center space-x-2">
                    <Monitor class="w-4 h-4 text-cyan-400" />
                    <span>{{ $t('profile.active_sessions') }}</span>
                </h2>

                <div class="space-y-3 text-xs">
                    <div
                        v-for="s in sessions"
                        :key="s.id"
                        class="p-3.5 rounded-2xl bg-slate-950/60 border border-slate-850 flex items-center justify-between"
                    >
                        <div class="flex items-center space-x-3">
                            <Monitor class="w-4 h-4 text-slate-500" />
                            <div>
                                <div class="font-bold text-white flex items-center space-x-2">
                                    <span>IP: {{ s.ip_address }}</span>
                                    <span v-if="s.is_current_device" class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400">
                                        {{ $t('profile.current_device') }}
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-500">{{ s.last_active }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-2">
                    <form @submit.prevent="logoutOtherSessions" class="flex items-center space-x-2">
                        <input
                            v-model="logoutOtherForm.password"
                            type="password"
                            required
                            :placeholder="$t('profile.password_placeholder')"
                            class="px-3 py-1.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white"
                        />
                        <button
                            type="submit"
                            :disabled="logoutOtherForm.processing"
                            class="px-4 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-rose-400 font-semibold text-xs transition-all"
                        >
                            {{ $t('profile.logout_others_button') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
