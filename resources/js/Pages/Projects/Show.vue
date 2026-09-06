<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Globe,
    ShieldCheck,
    Play,
    Search,
    FileText,
    CheckSquare,
    TrendingUp,
    FileSpreadsheet,
    Zap,
    ExternalLink,
    Clock,
    ChevronRight,
    Settings,
    Download,
    Sparkles,
    Bot
} from 'lucide-vue-next';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps<{
    project: any;
    crawls: Array<any>;
    latestCrawl: any;
    findingsSummary: any;
    canManage: boolean;
}>();

const liveCrawl = ref(props.latestCrawl);
let livePollInterval: any = null;

const checkLiveStatus = async () => {
    if (!liveCrawl.value || (liveCrawl.value.status !== 'running' && liveCrawl.value.status !== 'pending')) {
        if (livePollInterval) clearInterval(livePollInterval);
        return;
    }

    try {
        const res = await fetch(`/projects/${props.project.id}/crawls/${liveCrawl.value.id}/status`);
        if (res.ok) {
            const data = await res.json();
            liveCrawl.value.status = data.status;
            liveCrawl.value.pages_crawled = data.pages_crawled;
            liveCrawl.value.health_score = data.health_score;

            if (data.status === 'completed' || data.status === 'failed') {
                clearInterval(livePollInterval);
                router.reload();
            }
        }
    } catch (e) {
        // Silently ignore network poll hiccup
    }
};

onMounted(() => {
    if (liveCrawl.value && (liveCrawl.value.status === 'running' || liveCrawl.value.status === 'pending')) {
        livePollInterval = setInterval(checkLiveStatus, 2500);
    }
});

onUnmounted(() => {
    if (livePollInterval) clearInterval(livePollInterval);
});

const crawlForm = useForm({
    max_pages: 250,
    max_depth: 3,
});

const startCrawl = () => {
    crawlForm.post(`/projects/${props.project.id}/crawls/start`);
};

const verifyForm = useForm({});
const verifyOwnership = () => {
    verifyForm.post(`/projects/${props.project.id}/verify`);
};
</script>

<template>
    <AppLayout :title="project.name">
        <Head :title="project.name" />

        <div class="space-y-8">
            <!-- Project Top Bar -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 p-6 rounded-3xl bg-slate-900/60 border border-slate-800/80">
                <div class="flex items-center space-x-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center font-bold text-xl text-white shadow-lg shadow-indigo-600/20">
                        {{ project.name.substring(0, 2).toUpperCase() }}
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h1 class="text-2xl font-bold text-white">{{ project.name }}</h1>
                            <span v-if="project.ownership_verified_at" class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] font-semibold">
                                <ShieldCheck class="w-3 h-3" />
                                <span>{{ t('common.verified') }}</span>
                            </span>
                        </div>
                        <div class="flex items-center space-x-3 text-xs text-slate-400 mt-1">
                            <a :href="project.start_url" target="_blank" class="hover:text-indigo-400 flex items-center space-x-1">
                                <span>{{ project.domain }}</span>
                                <ExternalLink class="w-3 h-3" />
                            </a>
                            <span>&bull;</span>
                            <span>{{ project.target_country }} / {{ project.target_language }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <form @submit.prevent="startCrawl" class="flex items-center space-x-2">
                        <select
                            v-model="crawlForm.max_pages"
                            class="px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                        >
                            <option :value="100">100 {{ t('common.pages') }}</option>
                            <option :value="250">250 {{ t('common.pages') }}</option>
                            <option :value="500">500 {{ t('common.pages') }}</option>
                            <option :value="1000">1,000 {{ t('common.pages') }}</option>
                            <option :value="2500">2,500 {{ t('common.pages') }}</option>
                            <option :value="5000">5,000 {{ t('common.pages') }}</option>
                        </select>

                        <button
                            type="submit"
                            :disabled="crawlForm.processing"
                            class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-lg shadow-indigo-600/30 flex items-center space-x-2 transition-all disabled:opacity-50 shrink-0"
                        >
                            <Play class="w-4 h-4 fill-white" />
                            <span>{{ t('projects.start_crawl') }}</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Ownership Verification Box (Optional Feature) -->
            <div v-if="!project.ownership_verified_at" class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center space-x-2">
                        <span class="px-2 py-0.5 rounded-full bg-slate-800 text-slate-400 text-[10px] font-semibold border border-slate-700">{{ t('projects.verification_badge') }}</span>
                        <strong class="text-slate-200 font-semibold text-xs">{{ t('projects.verification_title') }}</strong>
                    </div>
                    <p class="text-slate-400 text-[11px] leading-relaxed">
                        {{ t('projects.verification_desc') }}
                    </p>
                    <div class="pt-0.5">
                        <code class="bg-slate-950 px-2 py-0.5 rounded text-amber-300/90 select-all border border-slate-800 inline-block font-mono text-[11px]">&lt;meta name="seovy-verification" content="{{ project.verification_token }}"&gt;</code>
                    </div>
                </div>
                <div class="flex items-center space-x-2 shrink-0">
                    <a
                        :href="`/projects/${project.id}/wordpress-plugin`"
                        class="px-3 py-2 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-300 font-medium text-xs transition-all flex items-center space-x-1.5 border border-slate-700"
                        :title="t('projects.wp_plugin')"
                    >
                        <Download class="w-3.5 h-3.5 text-indigo-400" />
                        <span>{{ t('projects.wp_plugin') }}</span>
                    </a>
                    <button
                        @click="verifyOwnership"
                        :disabled="verifyForm.processing"
                        class="px-3.5 py-2 rounded-xl bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 font-semibold text-xs transition-all flex items-center space-x-2 disabled:opacity-50"
                    >
                        <span v-if="verifyForm.processing">{{ t('projects.checking') }}</span>
                        <span v-else>{{ t('projects.verification_title') }}</span>
                    </button>
                </div>
            </div>

            <!-- Module Navigation Bar -->
            <div class="grid grid-cols-2 sm:grid-cols-6 gap-3">
                <Link
                    v-if="latestCrawl"
                    :href="`/projects/${project.id}/crawls/${latestCrawl.id}`"
                    class="p-4 rounded-2xl bg-slate-900/40 border border-slate-800/80 hover:border-slate-700 text-center transition-all group"
                >
                    <Search class="w-5 h-5 text-violet-400 mx-auto mb-1.5 group-hover:scale-110 transition-transform" />
                    <span class="text-xs font-semibold text-white block">{{ t('projects.technical_tab') }}</span>
                    <span class="text-[10px] text-slate-500">{{ t('projects.pages_unit', { count: latestCrawl.pages_crawled }) }}</span>
                </Link>

                <Link
                    :href="`/projects/${project.id}/ai-seo`"
                    class="p-4 rounded-2xl bg-gradient-to-b from-fuchsia-950/30 to-slate-900/40 border border-fuchsia-500/30 hover:border-fuchsia-500/60 text-center transition-all group shadow-lg shadow-fuchsia-500/5"
                >
                    <Sparkles class="w-5 h-5 text-fuchsia-400 mx-auto mb-1.5 group-hover:scale-110 transition-transform" />
                    <span class="text-xs font-bold text-white block">{{ t('projects.ai_readiness_tab') }}</span>
                    <span class="text-[10px] text-fuchsia-400">GEO & LLM</span>
                </Link>

                <Link
                    :href="`/projects/${project.id}/on-page`"
                    class="p-4 rounded-2xl bg-slate-900/40 border border-slate-800/80 hover:border-slate-700 text-center transition-all group"
                >
                    <FileText class="w-5 h-5 text-cyan-400 mx-auto mb-1.5 group-hover:scale-110 transition-transform" />
                    <span class="text-xs font-semibold text-white block">{{ t('nav.onpage_serp') }}</span>
                    <span class="text-[10px] text-slate-500">SERP</span>
                </Link>

                <Link
                    :href="`/projects/${project.id}/tasks`"
                    class="p-4 rounded-2xl bg-slate-900/40 border border-slate-800/80 hover:border-slate-700 text-center transition-all group"
                >
                    <CheckSquare class="w-5 h-5 text-amber-400 mx-auto mb-1.5 group-hover:scale-110 transition-transform" />
                    <span class="text-xs font-semibold text-white block">{{ t('tasks.title') }}</span>
                    <span class="text-[10px] text-slate-500">{{ t('common.active') }}</span>
                </Link>

                <Link
                    :href="`/projects/${project.id}/keywords`"
                    class="p-4 rounded-2xl bg-slate-900/40 border border-slate-800/80 hover:border-slate-700 text-center transition-all group"
                >
                    <TrendingUp class="w-5 h-5 text-emerald-400 mx-auto mb-1.5 group-hover:scale-110 transition-transform" />
                    <span class="text-xs font-semibold text-white block">{{ t('nav.rank_keywords') }}</span>
                    <span class="text-[10px] text-slate-500">{{ t('keywords.title') }}</span>
                </Link>

                <Link
                    :href="`/projects/${project.id}/reports`"
                    class="p-4 rounded-2xl bg-slate-900/40 border border-slate-800/80 hover:border-slate-700 text-center transition-all group"
                >
                    <FileSpreadsheet class="w-5 h-5 text-pink-400 mx-auto mb-1.5 group-hover:scale-110 transition-transform" />
                    <span class="text-xs font-semibold text-white block">{{ t('reports.title') }}</span>
                    <span class="text-[10px] text-slate-500">PDF / CSV</span>
                </Link>
            </div>

            <!-- Latest Crawl Deep Summary -->
            <div v-if="latestCrawl" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Health Score Dial Card -->
                    <div class="p-6 rounded-3xl bg-slate-900/50 border border-slate-800/80 flex flex-col justify-between">
                        <div>
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('projects.site_health') }}</span>
                            <div class="mt-4 flex items-center space-x-4">
                                <div
                                    class="w-20 h-20 rounded-2xl flex items-center justify-center font-extrabold text-3xl shadow-xl"
                                    :class="latestCrawl.health_score >= 80 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : (latestCrawl.health_score >= 50 ? 'bg-amber-500/10 text-amber-400 border border-amber-500/30' : 'bg-rose-500/10 text-rose-400 border border-rose-500/30')"
                                >
                                    {{ latestCrawl.health_score }}%
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-white">
                                        {{ latestCrawl.health_score >= 80 ? t('common.completed') : t('common.critical') }}
                                    </div>
                                    <div class="text-xs text-slate-400 mt-0.5">
                                        {{ t('projects.pages_analyzed', { count: latestCrawl.pages_crawled }) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t border-slate-800 text-xs text-slate-500">
                            {{ t('projects.health_score_calc') }}
                        </div>
                    </div>

                    <!-- Issues Breakdown (Severity) -->
                    <div class="p-6 rounded-3xl bg-slate-900/50 border border-slate-800/80 lg:col-span-2">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4 block">{{ t('projects.issues_distribution') }}</span>

                        <div class="grid grid-cols-3 gap-4">
                            <div class="p-4 rounded-2xl bg-rose-500/5 border border-rose-500/20 text-center">
                                <span class="text-2xl font-extrabold text-rose-400">{{ findingsSummary.critical ?? 0 }}</span>
                                <span class="block text-xs font-semibold text-rose-300 mt-1">{{ t('projects.critical_errors') }}</span>
                            </div>

                            <div class="p-4 rounded-2xl bg-amber-500/5 border border-amber-500/20 text-center">
                                <span class="text-2xl font-extrabold text-amber-400">{{ findingsSummary.warning ?? 0 }}</span>
                                <span class="block text-xs font-semibold text-amber-300 mt-1">{{ t('projects.warnings') }}</span>
                            </div>

                            <div class="p-4 rounded-2xl bg-indigo-500/5 border border-indigo-500/20 text-center">
                                <span class="text-2xl font-extrabold text-indigo-400">{{ findingsSummary.notice ?? 0 }}</span>
                                <span class="block text-xs font-semibold text-indigo-300 mt-1">{{ t('projects.recommendations') }}</span>
                            </div>
                        </div>

                        <!-- Categories Grid -->
                        <div v-if="findingsSummary.categories" class="mt-6 pt-4 border-t border-slate-800 grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                            <div v-for="(count, cat) in findingsSummary.categories" :key="cat" class="p-2 rounded-xl bg-slate-950/60 border border-slate-850 flex items-center justify-between">
                                <span class="text-slate-400 capitalize">{{ cat }}</span>
                                <span class="font-bold text-white">{{ count }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Live Active Crawl Progress Banner -->
            <div
                v-if="liveCrawl && (liveCrawl.status === 'running' || liveCrawl.status === 'pending')"
                class="p-6 rounded-3xl bg-indigo-950/40 border border-indigo-500/30 shadow-2xl relative overflow-hidden"
            >
                <div class="absolute inset-0 bg-gradient-to-r from-indigo-500/10 via-violet-500/10 to-transparent pointer-events-none"></div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="space-y-2">
                        <div class="flex items-center space-x-3">
                            <div class="w-3 h-3 rounded-full bg-indigo-500 animate-ping"></div>
                            <span class="text-xs font-bold uppercase tracking-wider text-indigo-400">
                                {{ liveCrawl.status === 'running' ? t('projects.live_crawl_title') : t('projects.live_crawl_starting') }}
                            </span>
                            <span class="px-2 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 text-[11px] font-mono">
                                #{{ liveCrawl.id }}
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-white">
                            {{ project.domain }}
                        </h3>
                        <p class="text-xs text-slate-400">
                            {{ t('projects.live_crawl_desc', { domain: project.domain }) }}
                        </p>
                    </div>

                    <div class="flex items-center space-x-6 bg-slate-900/80 px-5 py-3 rounded-2xl border border-slate-800 shrink-0">
                        <div class="text-center">
                            <span class="text-[10px] text-slate-500 uppercase font-semibold block">{{ t('projects.pages_crawled') }}</span>
                            <span class="text-xl font-black text-white font-mono">{{ liveCrawl.pages_crawled }}</span>
                        </div>
                        <div class="w-px h-8 bg-slate-800"></div>
                        <div class="text-center">
                            <span class="text-[10px] text-slate-500 uppercase font-semibold block">{{ t('projects.target_limit') }}</span>
                            <span class="text-xl font-black text-slate-400 font-mono">{{ liveCrawl.max_pages }}</span>
                        </div>
                        <div class="w-px h-8 bg-slate-800"></div>
                        <Link
                            :href="`/projects/${project.id}/crawls/${liveCrawl.id}`"
                            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs transition-all flex items-center space-x-1.5 shadow-lg shadow-indigo-600/30"
                        >
                            <span>{{ t('projects.watch_live') }}</span>
                            <ChevronRight class="w-4 h-4" />
                        </Link>
                    </div>
                </div>

                <!-- Animated Progress Bar -->
                <div class="w-full bg-slate-900 rounded-full h-2 mt-5 overflow-hidden border border-slate-800">
                    <div
                        class="bg-gradient-to-r from-indigo-500 to-violet-500 h-2 rounded-full transition-all duration-500"
                        :style="{ width: `${Math.min(100, Math.max(5, (liveCrawl.pages_crawled / liveCrawl.max_pages) * 100))}%` }"
                    ></div>
                </div>
            </div>

            <!-- Crawl History Table -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-white flex items-center space-x-2">
                        <Clock class="w-5 h-5 text-slate-400" />
                        <span>{{ t('projects.history_title') }}</span>
                    </h2>
                    <span v-if="crawls.length > 0" class="text-xs text-slate-500">
                        {{ t('projects.history_total', { count: crawls.length }) }}
                    </span>
                </div>

                <div v-if="crawls.length === 0" class="p-8 rounded-3xl bg-slate-900/40 border border-slate-800 text-center text-xs text-slate-500">
                    {{ t('projects.no_crawls_yet') }}
                </div>

                <div v-else class="rounded-3xl bg-slate-900/50 border border-slate-800/80 overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                            <tr>
                                <th class="p-4">{{ t('projects.crawl_id') }}</th>
                                <th class="p-4">{{ t('common.status') }}</th>
                                <th class="p-4">{{ t('projects.pages_crawled') }}</th>
                                <th class="p-4">{{ t('projects.health_score') }}</th>
                                <th class="p-4">{{ t('common.duration') }}</th>
                                <th class="p-4">{{ t('common.date') }}</th>
                                <th class="p-4 text-right">{{ t('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <tr v-for="crawl in crawls" :key="crawl.id" class="hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 font-mono font-bold text-white">#{{ crawl.id }}</td>
                                <td class="p-4">
                                    <span
                                        class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                        :class="crawl.status === 'completed' ? 'bg-emerald-500/10 text-emerald-400' : (crawl.status === 'running' ? 'bg-indigo-500/10 text-indigo-400 animate-pulse' : 'bg-slate-800 text-slate-400')"
                                    >
                                        {{ crawl.status }}
                                    </span>
                                </td>
                                <td class="p-4 font-medium text-white">{{ crawl.pages_crawled }} / {{ crawl.max_pages }}</td>
                                <td class="p-4">
                                    <span v-if="crawl.health_score !== null" class="font-bold text-slate-200">
                                        {{ crawl.health_score }}%
                                    </span>
                                    <span v-else class="text-slate-500">—</span>
                                </td>
                                <td class="p-4 text-slate-400">{{ crawl.duration_seconds }} {{ t('common.seconds') }}</td>
                                <td class="p-4 text-slate-400">{{ new Date(crawl.created_at).toLocaleDateString() }}</td>
                                <td class="p-4 text-right">
                                    <Link
                                        :href="`/projects/${project.id}/crawls/${crawl.id}`"
                                        class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-indigo-400 font-semibold text-xs transition-colors"
                                    >
                                        {{ t('common.details') }}
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
