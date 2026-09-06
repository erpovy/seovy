<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import {
    Activity,
    CheckCircle2,
    XCircle,
    Key,
    Shield,
    Server,
    ArrowRight,
    Lock,
    User,
    Mail,
    Globe,
    AlertCircle
} from 'lucide-vue-next';
import LanguageSelector from '@/Components/LanguageSelector.vue';

const props = defineProps<{
    requirements: Record<string, any>;
    keyFileExists: boolean;
    serverKeyPath: string;
}>();

const form = useForm({
    setup_key: '',
    app_url: window.location.origin,
    install_mode: 'self_hosted',
    admin_name: 'Platform Administrator',
    admin_email: 'admin@seovy.local',
    admin_password: '',
    admin_password_confirmation: '',
});

const submit = () => {
    form.post('/install');
};

const allRequirementsPassed = Object.values(props.requirements).every((req: any) => req.pass);
</script>

<template>
    <Head title="Installation Wizard - Seovy" />

    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8 relative">
        <div class="absolute top-6 right-6">
            <LanguageSelector placement="bottom" />
        </div>

        <div class="max-w-2xl mx-auto w-full space-y-8">
            <!-- Brand -->
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 shadow-xl shadow-indigo-600/20 mb-4">
                    <Activity class="w-8 h-8 text-white" />
                </div>
                <h1 class="text-3xl font-extrabold text-white tracking-tight">Seovy Installation Wizard</h1>
                <p class="text-sm text-slate-400 mt-2">
                    Configure your technical SEO platform and set up your master administrator account.
                </p>
            </div>

            <div class="bg-slate-900/60 backdrop-blur-xl border border-slate-800/80 p-6 sm:p-10 rounded-3xl shadow-2xl space-y-8">
                <!-- 1. Requirements Check -->
                <div class="space-y-3">
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center space-x-2">
                        <Server class="w-4 h-4 text-indigo-400" />
                        <span>1. Server & PHP Requirements</span>
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                        <div
                            v-for="(req, key) in requirements"
                            :key="key"
                            class="p-3 rounded-xl bg-slate-950/60 border flex items-center justify-between"
                            :class="req.pass ? 'border-emerald-500/20 text-emerald-300' : 'border-rose-500/20 text-rose-400'"
                        >
                            <span>{{ req.name }}</span>
                            <CheckCircle2 v-if="req.pass" class="w-4 h-4 text-emerald-400 shrink-0" />
                            <XCircle v-else class="w-4 h-4 text-rose-400 shrink-0" />
                        </div>
                    </div>
                </div>

                <!-- Error Messages -->
                <div v-if="Object.keys(form.errors).length > 0" class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs space-y-1">
                    <div v-for="(err, field) in form.errors" :key="field" class="flex items-center space-x-2">
                        <AlertCircle class="w-4 h-4 shrink-0" />
                        <span>{{ err }}</span>
                    </div>
                </div>

                <!-- 2. Setup Form -->
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Setup Security Key -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">
                            Server Installation Security Key
                        </label>
                        <p class="text-[11px] text-slate-400 mb-2">
                            To prevent unauthorized setups, provide the key from <code class="text-indigo-400 font-mono bg-slate-950 px-1.5 py-0.5 rounded">storage/install_key.txt</code> on your server.
                        </p>
                        <div class="relative">
                            <input
                                v-model="form.setup_key"
                                type="text"
                                required
                                class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white font-mono focus:ring-2 focus:ring-indigo-500/40"
                                placeholder="32-character installation security key"
                            />
                        </div>
                    </div>

                    <!-- App URL & Mode -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                                Application URL
                            </label>
                            <input
                                v-model="form.app_url"
                                type="url"
                                required
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white font-mono"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                                Deployment Mode
                            </label>
                            <select
                                v-model="form.install_mode"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white"
                            >
                                <option value="self_hosted">Self-Hosted / Single Tenant (Unlimited)</option>
                                <option value="saas">Multi-Tenant SaaS (Subscription & Limits Controlled)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Admin Account -->
                    <div class="pt-4 border-t border-slate-800 space-y-4">
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center space-x-2">
                            <Shield class="w-4 h-4 text-emerald-400" />
                            <span>2. Master Administrator Account</span>
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs text-slate-400 mb-1">Admin Full Name</label>
                                <input
                                    v-model="form.admin_name"
                                    type="text"
                                    required
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white"
                                />
                            </div>

                            <div>
                                <label class="block text-xs text-slate-400 mb-1">Admin Email Address</label>
                                <input
                                    v-model="form.admin_email"
                                    type="email"
                                    required
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs text-slate-400 mb-1">Password (Min 8 characters)</label>
                                <input
                                    v-model="form.admin_password"
                                    type="password"
                                    required
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white"
                                />
                            </div>

                            <div>
                                <label class="block text-xs text-slate-400 mb-1">Confirm Password</label>
                                <input
                                    v-model="form.admin_password_confirmation"
                                    type="password"
                                    required
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="pt-4">
                        <button
                            type="submit"
                            :disabled="form.processing || !allRequirementsPassed"
                            class="w-full py-3.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-xl shadow-indigo-600/30 flex items-center justify-center space-x-2 transition-all disabled:opacity-50"
                        >
                            <span>Complete Installation & Lock Setup</span>
                            <ArrowRight class="w-4 h-4" />
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
