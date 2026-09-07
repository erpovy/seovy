<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { 
    Search, 
    ShieldCheck, 
    Activity, 
    Zap, 
    Layers, 
    BarChart3, 
    ArrowRight 
} from 'lucide-vue-next';
import LanguageSelector from '@/Components/LanguageSelector.vue';

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
    laravelVersion?: string;
    phpVersion?: string;
}>();

const page = usePage();
const systemSettings = computed(() => ((page.props as any)?.system_settings) || {});
const logoDark = computed(() => systemSettings.value.logo_dark || systemSettings.value.logo || null);
const brandName = computed(() => systemSettings.value.brand_name || 'Seovy');
const logoFailed = ref(false);
</script>

<template>
    <Head :title="$t('welcome.page_title')" />

    <div class="min-h-screen bg-slate-950 text-slate-100 selection:bg-indigo-500 selection:text-white flex flex-col justify-between">
        <!-- Top Navigation -->
        <header class="border-b border-slate-800/80 bg-slate-900/40 backdrop-blur-md sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 min-h-[76px] py-2 flex items-center justify-between">
                <Link href="/" class="flex items-center space-x-3 group py-1">
                    <template v-if="logoDark && !logoFailed">
                        <img
                            :key="logoDark"
                            :src="logoDark"
                            :alt="brandName"
                            class="h-[72px] max-w-[340px] object-contain object-left rounded-lg shadow-sm"
                            @error="logoFailed = true"
                            @load="logoFailed = false"
                        />
                    </template>
                    <template v-else>
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center shadow-lg shadow-indigo-500/20 group-hover:scale-105 transition-transform">
                            <Activity class="w-5 h-5 text-white" />
                        </div>
                        <span class="text-xl font-bold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-white via-slate-200 to-slate-400">
                            {{ brandName }}
                        </span>
                    </template>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 font-medium">
                        v1.0
                    </span>
                </Link>

                <nav class="flex items-center space-x-3 sm:space-x-4">
                    <LanguageSelector placement="bottom" />

                    <Link
                        v-if="$page.props.auth?.user"
                        href="/dashboard"
                        class="px-4 py-2 text-sm font-medium rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white transition-all shadow-md shadow-indigo-600/30 flex items-center space-x-1.5"
                    >
                        <span>{{ $t('welcome.dashboard_button') }}</span>
                        <ArrowRight class="w-4 h-4" />
                    </Link>
                    <template v-else>
                        <Link
                            href="/login"
                            class="px-3 sm:px-4 py-2 text-sm font-medium text-slate-300 hover:text-white transition-colors"
                        >
                            {{ $t('welcome.login') }}
                        </Link>
                        <Link
                            href="/register"
                            class="px-3 sm:px-4 py-2 text-sm font-medium rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white transition-all shadow-md shadow-indigo-600/30"
                        >
                            {{ $t('welcome.register') }}
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Hero Section -->
        <main class="flex-1 flex items-center">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center">
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-semibold mb-6">
                    <Zap class="w-3.5 h-3.5" />
                    <span>{{ $t('welcome.badge') }}</span>
                </div>

                <h1 class="text-4xl sm:text-6xl font-extrabold tracking-tight max-w-4xl mx-auto leading-tight sm:leading-tight">
                    {{ $t('welcome.headline_1') }} <br class="hidden sm:inline" />
                    <span class="bg-clip-text text-transparent bg-gradient-to-r from-indigo-400 via-violet-400 to-pink-400">
                        {{ $t('welcome.headline_gradient') }}
                    </span>
                </h1>

                <p class="mt-6 text-lg sm:text-xl text-slate-400 max-w-2xl mx-auto font-normal">
                    {{ $t('welcome.description') }}
                </p>

                <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <Link
                        href="/register"
                        class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold shadow-lg shadow-indigo-600/25 flex items-center justify-center space-x-2 transition-all transform hover:-translate-y-0.5"
                    >
                        <span>{{ $t('welcome.cta_start') }}</span>
                        <ArrowRight class="w-5 h-5" />
                    </Link>
                    <a
                        href="#features"
                        class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-slate-900/80 hover:bg-slate-800 text-slate-300 hover:text-white font-medium border border-slate-800 transition-all"
                    >
                        {{ $t('welcome.cta_features') }}
                    </a>
                </div>

                <!-- Feature Badges -->
                <div id="features" class="mt-24 grid grid-cols-1 md:grid-cols-3 gap-6 text-left max-w-5xl mx-auto">
                    <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800/80 hover:border-slate-700 transition-all">
                        <div class="w-12 h-12 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center mb-4">
                            <Search class="w-6 h-6" />
                        </div>
                        <h3 class="text-lg font-bold text-white mb-2">{{ $t('welcome.feature1_title') }}</h3>
                        <p class="text-sm text-slate-400 leading-relaxed">
                            {{ $t('welcome.feature1_desc') }}
                        </p>
                    </div>

                    <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800/80 hover:border-slate-700 transition-all">
                        <div class="w-12 h-12 rounded-xl bg-violet-500/10 text-violet-400 flex items-center justify-center mb-4">
                            <ShieldCheck class="w-6 h-6" />
                        </div>
                        <h3 class="text-lg font-bold text-white mb-2">{{ $t('welcome.feature2_title') }}</h3>
                        <p class="text-sm text-slate-400 leading-relaxed">
                            {{ $t('welcome.feature2_desc') }}
                        </p>
                    </div>

                    <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800/80 hover:border-slate-700 transition-all">
                        <div class="w-12 h-12 rounded-xl bg-cyan-500/10 text-cyan-400 flex items-center justify-center mb-4">
                            <Layers class="w-6 h-6" />
                        </div>
                        <h3 class="text-lg font-bold text-white mb-2">{{ $t('welcome.feature3_title') }}</h3>
                        <p class="text-sm text-slate-400 leading-relaxed">
                            {{ $t('welcome.feature3_desc') }}
                        </p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-900 bg-slate-950/60 py-6 text-center text-xs text-slate-500">
            <p>Laravel v{{ laravelVersion || '12.x' }} &bull; PHP v{{ phpVersion || '8.2' }} &bull; {{ brandName }} Platform</p>
        </footer>
    </div>
</template>
