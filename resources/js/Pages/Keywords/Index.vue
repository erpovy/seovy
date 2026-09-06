<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    TrendingUp,
    Plus,
    Upload,
    ArrowLeft,
    RefreshCw,
    Search,
    AlertCircle,
    CheckCircle2,
    ArrowUp,
    ArrowDown,
    Minus,
    Settings,
    ShieldCheck,
    Globe2
} from 'lucide-vue-next';

const props = defineProps<{
    project: any;
    keywords: {
        data: Array<any>;
        links: Array<any>;
        total: number;
    };
    providerConfigured: boolean;
    providerName: string;
    serpIntegration?: {
        provider: string;
        is_active: boolean;
        has_credentials: boolean;
    };
    filters: {
        search?: string;
    };
}>();

const showAddModal = ref(false);
const showImportModal = ref(false);
const showSerpModal = ref(false);
const isChecking = ref(false);

const addForm = useForm({
    keyword: '',
    target_url: '',
});

const submitAdd = () => {
    addForm.post(`/projects/${props.project.id}/keywords`, {
        onSuccess: () => {
            showAddModal.value = false;
            addForm.reset();
        },
    });
};

const importForm = useForm({
    file: null as File | null,
});

const submitImport = () => {
    importForm.post(`/projects/${props.project.id}/keywords/import-csv`, {
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
        },
    });
};

const checkRankings = () => {
    if (isChecking.value) return;
    isChecking.value = true;
    router.post(`/projects/${props.project.id}/keywords/check`, {}, {
        preserveScroll: true,
        onFinish: () => {
            isChecking.value = false;
        },
    });
};

const serpForm = useForm({
    provider: props.serpIntegration?.provider || 'smart',
    dataforseo_login: '',
    dataforseo_password: '',
    serpapi_key: '',
});

const submitSerp = () => {
    serpForm.post(`/projects/${props.project.id}/keywords/serp-settings`, {
        onSuccess: () => {
            showSerpModal.value = false;
        },
    });
};

const providerDisplayName = computed(() => {
    if (props.providerName === 'SmartWebSerpProvider') {
        return 'Smart Web Engine (Dahili)';
    }
    if (props.providerName === 'DataForSeoProvider') {
        return 'DataForSEO Live';
    }
    if (props.providerName === 'SerpApiProvider') {
        return 'SerpApi Google';
    }
    if (props.providerName === 'MockSerpProvider') {
        return 'Demo (Mock)';
    }
    return props.providerName;
});
</script>

<template>
    <AppLayout :title="`${$t('keywords.title')} - ${project.name}`">
        <Head :title="`${$t('keywords.title')} - ${project.name}`" />

        <div class="space-y-6">
            <!-- Flash & Error Notification Banners -->
            <div
                v-if="($page.props as any).flash?.success"
                class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-xs text-emerald-300 flex items-center justify-between"
            >
                <div class="flex items-center space-x-2.5">
                    <CheckCircle2 class="w-4 h-4 text-emerald-400 shrink-0" />
                    <span class="font-medium">{{ ($page.props as any).flash.success }}</span>
                </div>
            </div>

            <div
                v-if="($page.props as any).errors?.error || ($page.props as any).flash?.error"
                class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-xs text-rose-300 flex items-center justify-between"
            >
                <div class="flex items-center space-x-2.5">
                    <AlertCircle class="w-4 h-4 text-rose-400 shrink-0" />
                    <span class="font-medium">{{ ($page.props as any).errors?.error || ($page.props as any).flash?.error }}</span>
                </div>
            </div>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center space-x-3">
                    <Link :href="`/projects/${project.id}`" class="p-2 rounded-xl bg-slate-900 border border-slate-800 text-slate-400 hover:text-white transition-colors">
                        <ArrowLeft class="w-4 h-4" />
                    </Link>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-white">{{ $t('keywords.title') }}</h1>
                        <p class="text-xs text-slate-400 mt-0.5">
                            {{ $t('keywords.subtitle', { total: keywords.total }) }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <button
                        @click="showImportModal = true"
                        class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-white text-xs font-semibold flex items-center space-x-1.5 transition-all"
                    >
                        <Upload class="w-3.5 h-3.5 text-indigo-400" />
                        <span>{{ $t('keywords.import_csv') }}</span>
                    </button>

                    <button
                        @click="showAddModal = true"
                        class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 flex items-center space-x-1.5 transition-all"
                    >
                        <Plus class="w-3.5 h-3.5" />
                        <span>{{ $t('keywords.add_keyword') }}</span>
                    </button>

                    <button
                        @click="checkRankings"
                        :disabled="isChecking"
                        class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold flex items-center space-x-1.5 transition-all disabled:opacity-60"
                    >
                        <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': isChecking }" />
                        <span>{{ isChecking ? ($t('keywords.checking_rankings') || 'Kontrol Ediliyor...') : $t('keywords.refresh_rankings') }}</span>
                    </button>
                </div>
            </div>

            <!-- Provider Status Banner -->
            <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                <div class="flex items-center space-x-2.5">
                    <span class="text-slate-400">{{ $t('keywords.active_provider') }}</span>
                    <span class="font-bold text-white flex items-center space-x-1.5">
                        <Globe2 class="w-3.5 h-3.5 text-indigo-400" />
                        <span>{{ providerDisplayName }}</span>
                    </span>
                    <span
                        class="px-2 py-0.5 rounded-full text-[10px] font-semibold"
                        :class="providerConfigured ? 'bg-emerald-500/10 text-emerald-400' : 'bg-amber-500/10 text-amber-400'"
                    >
                        {{ providerConfigured ? $t('keywords.external_api_active') : $t('keywords.external_api_none') }}
                    </span>
                </div>
                <div class="flex items-center space-x-3">
                    <span v-if="!providerConfigured" class="text-slate-500 hidden sm:inline">
                        {{ $t('keywords.no_api_desc') }}
                    </span>
                    <button
                        @click="showSerpModal = true"
                        class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-750 text-slate-200 border border-slate-700 hover:border-slate-600 text-xs font-semibold flex items-center space-x-1.5 transition-all"
                    >
                        <Settings class="w-3.5 h-3.5 text-indigo-400" />
                        <span>{{ $t('keywords.serp_settings') || 'SERP Ayarları' }}</span>
                    </button>
                </div>
            </div>

            <!-- Keywords Table -->
            <div v-if="keywords.data.length === 0" class="p-12 rounded-3xl bg-slate-900/40 border border-slate-800 text-center text-xs text-slate-500">
                <TrendingUp class="w-12 h-12 text-slate-600 mx-auto mb-3" />
                <h3 class="text-sm font-semibold text-white">{{ $t('keywords.no_keywords') }}</h3>
                <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1 mb-4">
                    {{ $t('keywords.no_keywords_desc') }}
                </p>
                <button
                    @click="showAddModal = true"
                    class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs"
                >
                    {{ $t('keywords.add_first_keyword') }}
                </button>
            </div>

            <div v-else class="rounded-3xl bg-slate-900/50 border border-slate-800/80 overflow-hidden">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="p-4">{{ $t('keywords.keyword') }}</th>
                            <th class="p-4">{{ $t('keywords.current_rank') }}</th>
                            <th class="p-4">{{ $t('keywords.change') }}</th>
                            <th class="p-4">{{ $t('keywords.search_volume') }}</th>
                            <th class="p-4">{{ $t('keywords.target_url') }}</th>
                            <th class="p-4 text-right">{{ $t('keywords.action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <tr v-for="kw in keywords.data" :key="kw.id" class="hover:bg-slate-800/30 transition-colors">
                            <td class="p-4 font-bold text-white">{{ kw.keyword }}</td>
                            <td class="p-4">
                                <span v-if="kw.current_position" class="font-extrabold text-sm text-indigo-400">
                                    #{{ kw.current_position }}
                                </span>
                                <span v-else class="text-slate-500">—</span>
                            </td>
                            <td class="p-4">
                                <span
                                    v-if="kw.previous_position && kw.current_position"
                                    class="flex items-center space-x-1 font-semibold"
                                    :class="kw.previous_position > kw.current_position ? 'text-emerald-400' : (kw.previous_position < kw.current_position ? 'text-rose-400' : 'text-slate-400')"
                                >
                                    <ArrowUp v-if="kw.previous_position > kw.current_position" class="w-3.5 h-3.5" />
                                    <ArrowDown v-else-if="kw.previous_position < kw.current_position" class="w-3.5 h-3.5" />
                                    <Minus v-else class="w-3.5 h-3.5" />
                                    <span>{{ Math.abs(kw.previous_position - kw.current_position) }}</span>
                                </span>
                                <span v-else class="text-slate-500">—</span>
                            </td>
                            <td class="p-4 text-slate-300">{{ kw.search_volume ? kw.search_volume.toLocaleString() : '—' }}</td>
                            <td class="p-4 text-slate-400 max-w-xs truncate font-mono text-[11px]">{{ kw.target_url || '—' }}</td>
                            <td class="p-4 text-right">
                                <Link
                                    :href="`/projects/${project.id}/keywords/${kw.id}`"
                                    method="delete"
                                    as="button"
                                    class="text-rose-400 hover:text-rose-300 font-semibold text-xs"
                                >
                                    {{ $t('keywords.delete') }}
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Add Keyword Modal -->
            <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
                <div class="w-full max-w-md bg-slate-900 border border-slate-800 p-6 rounded-3xl space-y-4 shadow-2xl">
                    <h2 class="text-base font-bold text-white">{{ $t('keywords.add_modal_title') }}</h2>
                    <form @submit.prevent="submitAdd" class="space-y-3 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">{{ $t('keywords.keyword_label') }}</label>
                            <input
                                v-model="addForm.keyword"
                                type="text"
                                required
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white"
                                :placeholder="$t('keywords.keyword_placeholder')"
                            />
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">{{ $t('keywords.target_url_label') }}</label>
                            <input
                                v-model="addForm.target_url"
                                type="url"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-mono"
                                :placeholder="$t('keywords.target_url_placeholder')"
                            />
                        </div>
                        <div class="pt-3 flex items-center justify-end space-x-2">
                            <button
                                type="button"
                                @click="showAddModal = false"
                                class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 hover:text-white"
                            >
                                {{ $t('common.cancel') }}
                            </button>
                            <button
                                type="submit"
                                :disabled="addForm.processing"
                                class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold"
                            >
                                {{ $t('common.save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Import CSV Modal -->
            <div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
                <div class="w-full max-w-md bg-slate-900 border border-slate-800 p-6 rounded-3xl space-y-4 shadow-2xl">
                    <h2 class="text-base font-bold text-white">{{ $t('keywords.import_modal_title') }}</h2>
                    <p class="text-xs text-slate-400">
                        {{ $t('keywords.import_modal_desc') }}
                    </p>
                    <form @submit.prevent="submitImport" class="space-y-4 text-xs">
                        <input
                            type="file"
                            accept=".csv,.txt"
                            required
                            @change="importForm.file = ($event.target as any).files[0]"
                            class="w-full text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-white hover:file:bg-slate-700"
                        />
                        <div class="pt-3 flex items-center justify-end space-x-2">
                            <button
                                type="button"
                                @click="showImportModal = false"
                                class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 hover:text-white"
                            >
                                {{ $t('common.cancel') }}
                            </button>
                            <button
                                type="submit"
                                :disabled="importForm.processing"
                                class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold"
                            >
                                {{ $t('keywords.import_button') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- SERP Configuration Modal -->
            <div v-if="showSerpModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
                <div class="w-full max-w-lg bg-slate-900 border border-slate-800 p-6 rounded-3xl space-y-4 shadow-2xl">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-bold text-white flex items-center space-x-2">
                            <Settings class="w-4 h-4 text-indigo-400" />
                            <span>{{ $t('keywords.serp_modal_title') || 'SERP Sağlayıcı ve Sıralama Ayarları' }}</span>
                        </h2>
                        <button @click="showSerpModal = false" class="text-slate-400 hover:text-white text-xs">✕</button>
                    </div>
                    <p class="text-xs text-slate-400">
                        {{ $t('keywords.serp_modal_desc') || 'Anahtar kelime sıralamalarınızın canlı olarak nasıl kontrol edileceğini yapılandırın.' }}
                    </p>

                    <form @submit.prevent="submitSerp" class="space-y-4 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1.5">{{ $t('keywords.provider_choice') || 'Arama Motoru Sağlayıcısı' }}</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <button
                                    type="button"
                                    @click="serpForm.provider = 'smart'"
                                    class="p-3 rounded-xl border text-left transition-all flex flex-col justify-between"
                                    :class="serpForm.provider === 'smart' ? 'bg-indigo-600/10 border-indigo-500 text-white' : 'bg-slate-950/60 border-slate-800 text-slate-400 hover:text-slate-200'"
                                >
                                    <span class="font-bold text-xs text-indigo-400">⚡ {{ $t('keywords.provider_smart') || 'Dahili Akıllı Motor' }}</span>
                                    <span class="text-[10px] text-slate-400 mt-1">{{ $t('keywords.provider_smart_desc') || 'Ücretsiz, API gerektirmez. Canlı web araması ve site alaka düzeyini kullanır.' }}</span>
                                </button>

                                <button
                                    type="button"
                                    @click="serpForm.provider = 'serpapi'"
                                    class="p-3 rounded-xl border text-left transition-all flex flex-col justify-between"
                                    :class="serpForm.provider === 'serpapi' ? 'bg-indigo-600/10 border-indigo-500 text-white' : 'bg-slate-950/60 border-slate-800 text-slate-400 hover:text-slate-200'"
                                >
                                    <span class="font-bold text-xs text-cyan-400">SerpApi (Google)</span>
                                    <span class="text-[10px] text-slate-400 mt-1">{{ $t('keywords.provider_serpapi_desc') || 'Google SERP API üzerinden anlık resmi Google arama sonuçları.' }}</span>
                                </button>

                                <button
                                    type="button"
                                    @click="serpForm.provider = 'dataforseo'"
                                    class="p-3 rounded-xl border text-left transition-all flex flex-col justify-between"
                                    :class="serpForm.provider === 'dataforseo' ? 'bg-indigo-600/10 border-indigo-500 text-white' : 'bg-slate-950/60 border-slate-800 text-slate-400 hover:text-slate-200'"
                                >
                                    <span class="font-bold text-xs text-amber-400">DataForSEO API</span>
                                    <span class="text-[10px] text-slate-400 mt-1">{{ $t('keywords.provider_dataforseo_desc') || 'Kurumsal DataForSEO hesabınız ile canlı sıralamalar.' }}</span>
                                </button>

                                <button
                                    type="button"
                                    @click="serpForm.provider = 'mock'"
                                    class="p-3 rounded-xl border text-left transition-all flex flex-col justify-between"
                                    :class="serpForm.provider === 'mock' ? 'bg-indigo-600/10 border-indigo-500 text-white' : 'bg-slate-950/60 border-slate-800 text-slate-400 hover:text-slate-200'"
                                >
                                    <span class="font-bold text-xs text-slate-300">Demo Modu</span>
                                    <span class="text-[10px] text-slate-400 mt-1">{{ $t('keywords.provider_mock_desc') || 'Geliştirme ve test amaçlı simüle veriler üretir.' }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- DataForSEO Inputs -->
                        <div v-if="serpForm.provider === 'dataforseo'" class="space-y-3 p-3.5 rounded-xl bg-slate-950 border border-slate-800">
                            <div>
                                <label class="block font-semibold text-slate-300 mb-1">DataForSEO Login</label>
                                <input
                                    v-model="serpForm.dataforseo_login"
                                    type="text"
                                    class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-white font-mono"
                                    placeholder="login@email.com"
                                />
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-300 mb-1">DataForSEO Password / API Key</label>
                                <input
                                    v-model="serpForm.dataforseo_password"
                                    type="password"
                                    class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-white font-mono"
                                    placeholder="••••••••••••"
                                />
                            </div>
                        </div>

                        <!-- SerpApi Input -->
                        <div v-if="serpForm.provider === 'serpapi'" class="space-y-3 p-3.5 rounded-xl bg-slate-950 border border-slate-800">
                            <div>
                                <label class="block font-semibold text-slate-300 mb-1">SerpApi Private API Key</label>
                                <input
                                    v-model="serpForm.serpapi_key"
                                    type="password"
                                    class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-white font-mono"
                                    placeholder="••••••••••••••••••••••••"
                                />
                            </div>
                        </div>

                        <div class="p-3 rounded-xl bg-indigo-500/5 border border-indigo-500/20 text-[11px] text-slate-400 flex items-center space-x-2">
                            <ShieldCheck class="w-4 h-4 text-indigo-400 shrink-0" />
                            <span>{{ $t('integrations.aes_notice') }}</span>
                        </div>

                        <div class="pt-2 flex items-center justify-end space-x-2">
                            <button
                                type="button"
                                @click="showSerpModal = false"
                                class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 hover:text-white"
                            >
                                {{ $t('common.cancel') }}
                            </button>
                            <button
                                type="submit"
                                :disabled="serpForm.processing"
                                class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold disabled:opacity-50"
                            >
                                {{ $t('common.save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
