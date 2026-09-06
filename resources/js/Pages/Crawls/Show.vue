<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Activity,
    AlertCircle,
    CheckCircle2,
    AlertTriangle,
    Pause,
    XCircle,
    Download,
    FileSpreadsheet,
    FileText,
    ArrowLeft,
    CheckSquare,
    Search,
    ChevronDown,
    ExternalLink,
    Clock,
    RefreshCw
} from 'lucide-vue-next';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps<{
    project: any;
    crawl: any;
    findings: {
        data: Array<any>;
        links: Array<any>;
        total: number;
    };
    pages: {
        data: Array<any>;
        links: Array<any>;
        total: number;
    };
    severityCounts: {
        critical: number;
        warning: number;
        notice: number;
    };
    filters: {
        severity?: string;
        category?: string;
        search?: string;
        status_code?: number;
    };
}>();

const activeTab = ref<'findings' | 'pages'>('findings');
const currentStatus = ref(props.crawl.status);
const pagesCrawled = ref(props.crawl.pages_crawled);
const healthScore = ref(props.crawl.health_score);
let pollInterval: any = null;

const checkLiveStatus = async () => {
    if (currentStatus.value !== 'running' && currentStatus.value !== 'pending') {
        if (pollInterval) clearInterval(pollInterval);
        return;
    }

    try {
        const res = await fetch(`/projects/${props.project.id}/crawls/${props.crawl.id}/status`);
        if (res.ok) {
            const data = await res.json();
            currentStatus.value = data.status;
            pagesCrawled.value = data.pages_crawled;
            healthScore.value = data.health_score;

            if (data.status === 'completed' || data.status === 'failed') {
                clearInterval(pollInterval);
                router.reload();
            }
        }
    } catch (e) {
        // Polling error silently handled
    }
};

onMounted(() => {
    if (currentStatus.value === 'running' || currentStatus.value === 'pending') {
        pollInterval = setInterval(checkLiveStatus, 2500);
    }
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});

const pauseCrawl = () => {
    router.post(`/projects/${props.project.id}/crawls/${props.crawl.id}/pause`);
};

const cancelCrawl = () => {
    router.post(`/projects/${props.project.id}/crawls/${props.crawl.id}/cancel`);
};

const convertToTask = (findingId: number) => {
    router.post(`/projects/${props.project.id}/findings/${findingId}/convert-task`, {}, {
        preserveScroll: true,
    });
};

const filterSeverity = (sev?: string) => {
    router.get(`/projects/${props.project.id}/crawls/${props.crawl.id}`, {
        ...props.filters,
        severity: sev || undefined,
    }, { preserveState: true });
};
</script>

<template>
    <AppLayout :title="`${t('crawls.report_title', { id: crawl.id })} - ${project.name}`">
        <Head :title="`${t('crawls.report_title', { id: crawl.id })} - ${project.name}`" />

        <div class="space-y-6">
            <!-- Back & Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center space-x-3">
                    <Link :href="`/projects/${project.id}`" class="p-2 rounded-xl bg-slate-900 border border-slate-800 text-slate-400 hover:text-white transition-colors">
                        <ArrowLeft class="w-4 h-4" />
                    </Link>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h1 class="text-xl sm:text-2xl font-bold text-white">{{ t('crawls.report_title', { id: crawl.id }) }}</h1>
                            <span
                                class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                :class="currentStatus === 'completed' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : (currentStatus === 'running' ? 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 animate-pulse' : 'bg-slate-800 text-slate-400')"
                            >
                                {{ currentStatus }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5 font-mono">
                            {{ project.domain }} &bull; {{ t('projects.target_limit') }}: {{ crawl.max_pages }}
                        </p>
                    </div>
                </div>

                <!-- Action Controls (Export, Pause, Cancel) -->
                <div class="flex items-center space-x-2">
                    <template v-if="currentStatus === 'running'">
                        <button
                            @click="pauseCrawl"
                            class="px-3.5 py-2 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 text-xs font-semibold border border-amber-500/20 flex items-center space-x-1.5 transition-all"
                        >
                            <Pause class="w-3.5 h-3.5" />
                            <span>{{ t('crawls.pause') }}</span>
                        </button>
                        <button
                            @click="cancelCrawl"
                            class="px-3.5 py-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 text-xs font-semibold border border-rose-500/20 flex items-center space-x-1.5 transition-all"
                        >
                            <XCircle class="w-3.5 h-3.5" />
                            <span>{{ t('crawls.cancel') }}</span>
                        </button>
                    </template>

                    <template v-else>
                        <a
                            :href="`/projects/${project.id}/crawls/${crawl.id}/export-csv`"
                            class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white text-xs font-semibold border border-slate-800 flex items-center space-x-1.5 transition-all"
                        >
                            <FileSpreadsheet class="w-3.5 h-3.5 text-emerald-400" />
                            <span>{{ t('crawls.export_csv') }}</span>
                        </a>

                        <Link
                            :href="`/projects/${project.id}/crawls/${crawl.id}/generate-pdf`"
                            method="post"
                            as="button"
                            class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 flex items-center space-x-1.5 transition-all"
                        >
                            <FileText class="w-3.5 h-3.5" />
                            <span>{{ t('crawls.generate_pdf') }}</span>
                        </Link>
                    </template>
                </div>
            </div>

            <!-- Live Progress Bar (if running) -->
            <div v-if="currentStatus === 'running'" class="p-4 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 space-y-2">
                <div class="flex items-center justify-between text-xs font-semibold text-indigo-300">
                    <span class="flex items-center space-x-2">
                        <RefreshCw class="w-3.5 h-3.5 animate-spin text-indigo-400" />
                        <span>{{ t('crawls.live_progress') }}</span>
                    </span>
                    <span>{{ pagesCrawled }} / {{ crawl.max_pages }} (%{{ Math.round((pagesCrawled / crawl.max_pages) * 100) }})</span>
                </div>
                <div class="w-full bg-slate-950 rounded-full h-2 overflow-hidden">
                    <div
                        class="bg-indigo-500 h-2 rounded-full transition-all duration-500"
                        :style="{ width: `${Math.min(100, Math.round((pagesCrawled / crawl.max_pages) * 100))}%` }"
                    ></div>
                </div>
            </div>

            <!-- Metrics Highlight Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div class="p-5 rounded-2xl bg-slate-900/50 border border-slate-800/80">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">{{ t('projects.health_score') }}</span>
                    <div class="mt-2 text-2xl font-bold text-white">
                        {{ healthScore !== null ? healthScore + '%' : '—' }}
                    </div>
                </div>

                <div class="p-5 rounded-2xl bg-slate-900/50 border border-slate-800/80">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">{{ t('projects.pages_crawled') }}</span>
                    <div class="mt-2 text-2xl font-bold text-white">{{ pagesCrawled }}</div>
                </div>

                <div class="p-5 rounded-2xl bg-slate-900/50 border border-slate-800/80">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">{{ t('crawls.avg_response_time') }}</span>
                    <div class="mt-2 text-2xl font-bold text-white">{{ crawl.avg_response_time_ms ? crawl.avg_response_time_ms + ' ms' : '—' }}</div>
                </div>

                <div class="p-5 rounded-2xl bg-slate-900/50 border border-slate-800/80">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">{{ t('projects.critical_errors') }}</span>
                    <div class="mt-2 text-2xl font-bold text-rose-400">{{ severityCounts.critical }}</div>
                </div>
            </div>

            <!-- View Mode Switcher Tabs (Findings vs Pages) -->
            <div class="flex items-center space-x-3 border-b border-slate-800 pb-3">
                <button
                    @click="activeTab = 'findings'"
                    class="px-4 py-2 rounded-xl text-xs font-semibold transition-all"
                    :class="activeTab === 'findings' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white bg-slate-900/60'"
                >
                    {{ t('crawls.tab_findings') }} ({{ findings.total }})
                </button>
                <button
                    @click="activeTab = 'pages'"
                    class="px-4 py-2 rounded-xl text-xs font-semibold transition-all"
                    :class="activeTab === 'pages' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white bg-slate-900/60'"
                >
                    {{ t('crawls.tab_pages') }} ({{ pages.total }})
                </button>
            </div>

            <!-- Findings View Tab -->
            <div v-if="activeTab === 'findings'" class="space-y-4">
                <!-- Severity Filter Pills -->
                <div class="flex items-center space-x-2 text-xs">
                    <button
                        @click="filterSeverity()"
                        class="px-3 py-1.5 rounded-lg border transition-all"
                        :class="!filters.severity ? 'bg-indigo-600 text-white border-indigo-500 font-semibold' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-white'"
                    >
                        {{ t('crawls.filter_all') }} ({{ findings.total }})
                    </button>
                    <button
                        @click="filterSeverity('critical')"
                        class="px-3 py-1.5 rounded-lg border transition-all"
                        :class="filters.severity === 'critical' ? 'bg-rose-500/20 text-rose-300 border-rose-500/40 font-semibold' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-rose-400'"
                    >
                        {{ t('common.critical') }} ({{ severityCounts.critical }})
                    </button>
                    <button
                        @click="filterSeverity('warning')"
                        class="px-3 py-1.5 rounded-lg border transition-all"
                        :class="filters.severity === 'warning' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 font-semibold' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-amber-400'"
                    >
                        {{ t('common.warning') }} ({{ severityCounts.warning }})
                    </button>
                    <button
                        @click="filterSeverity('notice')"
                        class="px-3 py-1.5 rounded-lg border transition-all"
                        :class="filters.severity === 'notice' ? 'bg-indigo-500/20 text-indigo-300 border-indigo-500/40 font-semibold' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-indigo-400'"
                    >
                        {{ t('common.notice') }} ({{ severityCounts.notice }})
                    </button>
                </div>

                <!-- Findings Table -->
                <div class="rounded-3xl bg-slate-900/50 border border-slate-800/80 overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                            <tr>
                                <th class="p-4">{{ t('crawls.col_issue') }}</th>
                                <th class="p-4">{{ t('crawls.col_category') }}</th>
                                <th class="p-4">{{ t('crawls.col_severity') }}</th>
                                <th class="p-4 text-right">{{ t('crawls.col_action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <tr v-if="findings.data.length === 0">
                                <td colspan="4" class="p-8 text-center text-slate-500">{{ t('crawls.no_issues') }}</td>
                            </tr>
                            <tr v-for="finding in findings.data" :key="finding.id" class="hover:bg-slate-800/20">
                                <td class="p-4">
                                    <div class="font-semibold text-white">{{ finding.title }}</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5 truncate max-w-lg font-mono">{{ finding.url }}</div>
                                </td>
                                <td class="p-4 capitalize text-slate-400">{{ finding.category }}</td>
                                <td class="p-4">
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                        :class="finding.severity === 'critical' ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : (finding.severity === 'warning' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20')"
                                    >
                                        {{ finding.severity }}
                                    </span>
                                </td>
                                <td class="p-4 text-right">
                                    <button
                                        v-if="!finding.converted_to_task_at"
                                        @click="convertToTask(finding.id)"
                                        class="px-3 py-1.5 rounded-lg bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-300 border border-indigo-500/20 font-semibold text-xs transition-colors"
                                    >
                                        {{ t('crawls.convert_task') }}
                                    </button>
                                    <span v-else class="text-[11px] text-emerald-400 flex items-center justify-end space-x-1">
                                        <CheckCircle2 class="w-3.5 h-3.5" />
                                        <span>{{ t('crawls.task_created') }}</span>
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pages View Tab -->
            <div v-else-if="activeTab === 'pages'" class="rounded-3xl bg-slate-900/50 border border-slate-800/80 overflow-hidden">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="p-4">{{ t('crawls.col_url') }}</th>
                            <th class="p-4">{{ t('crawls.col_status') }}</th>
                            <th class="p-4">{{ t('crawls.col_load_time') }}</th>
                            <th class="p-4">{{ t('crawls.col_words') }}</th>
                            <th class="p-4">{{ t('crawls.col_depth') }}</th>
                            <th class="p-4 text-right">{{ t('crawls.col_indexable') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <tr v-for="page in pages.data" :key="page.id" class="hover:bg-slate-800/20">
                            <td class="p-4">
                                <div class="font-medium text-white truncate max-w-md">{{ page.title || page.path }}</div>
                                <div class="text-[11px] text-slate-500 font-mono truncate max-w-md">{{ page.url }}</div>
                            </td>
                            <td class="p-4">
                                <span
                                    class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold"
                                    :class="page.status_code === 200 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-amber-500/10 text-amber-400'"
                                >
                                    {{ page.status_code }}
                                </span>
                            </td>
                            <td class="p-4 text-slate-400 font-mono">{{ page.response_time_ms }} ms</td>
                            <td class="p-4 text-slate-400">{{ page.word_count }}</td>
                            <td class="p-4 text-slate-400 font-mono">{{ page.depth }}</td>
                            <td class="p-4 text-right">
                                <span :class="page.is_indexable ? 'text-emerald-400' : 'text-slate-500'">
                                    {{ page.is_indexable ? t('common.yes') : t('common.no') }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
