<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
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
    RefreshCw,
    Eye,
    Copy,
    Check,
    X,
    Info,
    Lightbulb,
    FileCode,
    Globe
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
        current_page?: number;
        last_page?: number;
    };
    pages: {
        data: Array<any>;
        links: Array<any>;
        total: number;
        current_page?: number;
        last_page?: number;
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

// Search & Filter State
const searchQuery = ref(props.filters.search || '');
let searchTimeout: any = null;

// Modal & Task State
const selectedFinding = ref<any>(null);
const createdTaskFindings = ref<Set<number>>(new Set());
const copySuccess = ref(false);

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

const isTaskCreated = (finding: any) => {
    return (finding.tasks && finding.tasks.length > 0) || createdTaskFindings.value.has(finding.id);
};

const convertToTask = (findingId: number) => {
    router.post(`/projects/${props.project.id}/findings/${findingId}/convert-task`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            createdTaskFindings.value.add(findingId);
        }
    });
};

const applyFilters = (newSeverity?: string) => {
    const severityVal = newSeverity !== undefined ? newSeverity : props.filters.severity;
    router.get(
        `/projects/${props.project.id}/crawls/${props.crawl.id}`,
        {
            severity: severityVal || undefined,
            category: props.filters.category || undefined,
            search: searchQuery.value.trim() || undefined,
        },
        {
            preserveState: true,
            replace: true,
        }
    );
};

const onSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 450);
};

const clearSearch = () => {
    searchQuery.value = '';
    applyFilters();
};

const filterSeverity = (sev?: string) => {
    applyFilters(sev);
};

const formatUrlPath = (url?: string) => {
    if (!url) return '';
    try {
        const u = new URL(url);
        return u.pathname + (u.search || '') || '/';
    } catch (e) {
        return url;
    }
};

const copyToClipboard = (text: string) => {
    if (!text) return;
    navigator.clipboard.writeText(text);
    copySuccess.value = true;
    setTimeout(() => {
        copySuccess.value = false;
    }, 2000);
};

const formatEvidenceSummary = (finding: any) => {
    if (!finding.evidence) return null;
    const ev = finding.evidence;

    if (finding.rule_code === 'TITLE_TOO_SHORT') {
        return `${t('crawls.evidence_current')}: "${ev.title || ''}" (${ev.length} ${t('crawls.chars')}) • ${t('crawls.recommended_title_len')}`;
    }
    if (finding.rule_code === 'TITLE_TOO_LONG') {
        return `${t('crawls.evidence_current')}: "${ev.title || ''}" (${ev.length} ${t('crawls.chars')}) • ${t('crawls.recommended_title_len')}`;
    }
    if (finding.rule_code === 'TITLE_MISSING') {
        return t('crawls.evidence_title_missing');
    }
    if (finding.rule_code === 'META_DESCRIPTION_TOO_SHORT' || finding.rule_code === 'META_DESCRIPTION_TOO_LONG') {
        return `${t('crawls.evidence_current')}: ${ev.length} ${t('crawls.chars')} • ${t('crawls.recommended_meta_len')}`;
    }
    if (finding.rule_code === 'H1_MISSING') {
        return t('crawls.evidence_h1_missing');
    }
    if (finding.rule_code === 'H1_MULTIPLE') {
        const count = ev.h1_tags ? ev.h1_tags.length : (ev.count || 2);
        return `${count} ${t('crawls.evidence_h1_found')}`;
    }
    if (finding.rule_code === 'IMAGES_MISSING_ALT') {
        return `${ev.missing_alt_count} / ${ev.total_images || '?'} ${t('crawls.evidence_missing_alt')}`;
    }
    if (finding.rule_code === 'CONTENT_THIN') {
        return `${ev.word_count} ${t('crawls.words')} • ${t('crawls.min_words')}`;
    }
    if (finding.rule_code === 'STATUS_4XX_CLIENT_ERROR' || finding.rule_code === 'STATUS_5XX_SERVER_ERROR') {
        return `HTTP ${ev.status_code}`;
    }
    if (ev.url) {
        return ev.url;
    }
    return null;
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
                <!-- Search and Filters Bar -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-900/40 p-3 rounded-2xl border border-slate-800/80">
                    <!-- Severity Filter Pills -->
                    <div class="flex items-center flex-wrap gap-1.5 text-xs">
                        <button
                            @click="filterSeverity()"
                            class="px-3 py-1.5 rounded-lg border transition-all"
                            :class="!filters.severity ? 'bg-indigo-600 text-white border-indigo-500 font-semibold shadow-sm' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-white'"
                        >
                            {{ t('crawls.filter_all') }} ({{ findings.total }})
                        </button>
                        <button
                            @click="filterSeverity('critical')"
                            class="px-3 py-1.5 rounded-lg border transition-all flex items-center space-x-1.5"
                            :class="filters.severity === 'critical' ? 'bg-rose-500/20 text-rose-300 border-rose-500/40 font-semibold' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-rose-400'"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                            <span>{{ t('common.critical') }} ({{ severityCounts.critical }})</span>
                        </button>
                        <button
                            @click="filterSeverity('warning')"
                            class="px-3 py-1.5 rounded-lg border transition-all flex items-center space-x-1.5"
                            :class="filters.severity === 'warning' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 font-semibold' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-amber-400'"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            <span>{{ t('common.warning') }} ({{ severityCounts.warning }})</span>
                        </button>
                        <button
                            @click="filterSeverity('notice')"
                            class="px-3 py-1.5 rounded-lg border transition-all flex items-center space-x-1.5"
                            :class="filters.severity === 'notice' ? 'bg-indigo-500/20 text-indigo-300 border-indigo-500/40 font-semibold' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-indigo-400'"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                            <span>{{ t('common.notice') }} ({{ severityCounts.notice }})</span>
                        </button>
                    </div>

                    <!-- Search Box -->
                    <div class="relative min-w-[240px] sm:w-72">
                        <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                        <input
                            type="text"
                            v-model="searchQuery"
                            @input="onSearchInput"
                            :placeholder="t('crawls.search_findings_placeholder')"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-9 pr-8 py-1.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                        />
                        <button
                            v-if="searchQuery"
                            @click="clearSearch"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-white"
                        >
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>

                <!-- Findings Table -->
                <div class="rounded-3xl bg-slate-900/50 border border-slate-800/80 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-950/70 text-slate-400 border-b border-slate-800 font-semibold uppercase tracking-wider text-[11px]">
                                <tr>
                                    <th class="p-4 w-72">{{ t('crawls.col_issue') }}</th>
                                    <th class="p-4 w-80">{{ t('crawls.col_affected_page') }}</th>
                                    <th class="p-4 min-w-[240px]">{{ t('crawls.col_evidence') }}</th>
                                    <th class="p-4 w-28">{{ t('crawls.col_category') }}</th>
                                    <th class="p-4 text-right w-44">{{ t('crawls.col_action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                <tr v-if="findings.data.length === 0">
                                    <td colspan="5" class="p-12 text-center text-slate-500">
                                        <AlertCircle class="w-8 h-8 mx-auto mb-2 text-slate-600" />
                                        <div class="text-sm font-medium text-slate-400">{{ t('crawls.no_issues') }}</div>
                                    </td>
                                </tr>
                                <tr
                                    v-for="finding in findings.data"
                                    :key="finding.id"
                                    class="hover:bg-slate-800/30 transition-colors group"
                                >
                                    <!-- Issue / Problem Column -->
                                    <td class="p-4 align-top">
                                        <div class="flex items-center space-x-2 mb-1.5">
                                            <span
                                                class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                                :class="finding.severity === 'critical' ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : (finding.severity === 'warning' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20')"
                                            >
                                                {{ finding.severity }}
                                            </span>
                                            <span class="text-[10px] font-mono text-slate-500 uppercase">{{ finding.rule_code }}</span>
                                        </div>
                                        <div class="font-semibold text-white text-sm leading-snug group-hover:text-indigo-300 transition-colors">
                                            {{ finding.title }}
                                        </div>
                                    </td>

                                    <!-- Affected Page Column -->
                                    <td class="p-4 align-top">
                                        <div class="space-y-1.5">
                                            <!-- Target URL with External Link -->
                                            <div class="flex items-center space-x-2">
                                                <a
                                                    v-if="finding.page?.url || finding.url"
                                                    :href="finding.page?.url || finding.url"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="inline-flex items-center space-x-1 font-mono text-xs text-indigo-400 hover:text-indigo-300 hover:underline max-w-[240px] truncate"
                                                    :title="finding.page?.url || finding.url"
                                                >
                                                    <span class="truncate">{{ formatUrlPath(finding.page?.url || finding.url) }}</span>
                                                    <ExternalLink class="w-3 h-3 flex-shrink-0 text-slate-500 group-hover:text-indigo-400" />
                                                </a>
                                                <span v-else class="text-xs text-slate-500 italic">{{ t('crawls.entire_site') }}</span>

                                                <span
                                                    v-if="finding.page?.status_code"
                                                    class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold flex-shrink-0"
                                                    :class="finding.page.status_code === 200 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'"
                                                >
                                                    {{ finding.page.status_code }}
                                                </span>
                                            </div>

                                            <!-- Page Title if present -->
                                            <div
                                                v-if="finding.page?.title"
                                                class="text-[11px] text-slate-400 flex items-start space-x-1.5 max-w-[260px]"
                                                :title="finding.page.title"
                                            >
                                                <FileCode class="w-3 h-3 text-slate-500 mt-0.5 flex-shrink-0" />
                                                <span class="truncate font-medium text-slate-300">"{{ finding.page.title }}"</span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Evidence & Diagnostic Details Column -->
                                    <td class="p-4 align-top">
                                        <div class="space-y-1.5">
                                            <!-- Highlighted Evidence Badge -->
                                            <div
                                                v-if="formatEvidenceSummary(finding)"
                                                class="inline-block px-2.5 py-1 rounded-lg bg-slate-950 border border-slate-800 text-[11px] font-mono text-slate-300"
                                            >
                                                {{ formatEvidenceSummary(finding) }}
                                            </div>

                                            <!-- Recommendation Callout -->
                                            <div v-if="finding.recommendation" class="text-[11px] text-slate-400 line-clamp-2 leading-relaxed flex items-start space-x-1">
                                                <Lightbulb class="w-3 h-3 text-amber-400/80 mt-0.5 flex-shrink-0" />
                                                <span>{{ finding.recommendation }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Category Column -->
                                    <td class="p-4 align-top">
                                        <span class="capitalize px-2.5 py-1 rounded-lg bg-slate-800/80 text-slate-300 text-[11px] font-medium border border-slate-700/50">
                                            {{ finding.category }}
                                        </span>
                                    </td>

                                    <!-- Action Column -->
                                    <td class="p-4 align-top text-right">
                                        <div class="flex items-center justify-end space-x-2">
                                            <!-- Inspect Button -->
                                            <button
                                                @click="selectedFinding = finding"
                                                class="px-2.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700/80 font-medium text-xs flex items-center space-x-1 transition-all"
                                                :title="t('crawls.inspect')"
                                            >
                                                <Eye class="w-3.5 h-3.5 text-indigo-400" />
                                                <span>{{ t('crawls.inspect') }}</span>
                                            </button>

                                            <!-- Convert to Task Button -->
                                            <button
                                                v-if="!isTaskCreated(finding)"
                                                @click="convertToTask(finding.id)"
                                                class="px-2.5 py-1.5 rounded-xl bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-300 border border-indigo-500/20 font-semibold text-xs transition-colors whitespace-nowrap"
                                            >
                                                {{ t('crawls.convert_task') }}
                                            </button>
                                            <span v-else class="text-[11px] text-emerald-400 font-semibold flex items-center space-x-1 px-2 py-1 bg-emerald-500/10 border border-emerald-500/20 rounded-xl">
                                                <CheckCircle2 class="w-3.5 h-3.5" />
                                                <span>{{ t('crawls.task_created') }}</span>
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Findings Pagination -->
                    <div
                        v-if="findings.links && findings.links.length > 3"
                        class="flex items-center justify-center space-x-1 p-4 border-t border-slate-800 bg-slate-950/40"
                    >
                        <Component
                            :is="link.url ? Link : 'span'"
                            v-for="(link, idx) in findings.links"
                            :key="idx"
                            :href="link.url || '#'"
                            v-html="link.label"
                            class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all"
                            :class="[
                                link.active
                                    ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30'
                                    : (link.url
                                        ? 'bg-slate-900 text-slate-300 hover:text-white hover:bg-slate-800 border border-slate-800'
                                        : 'text-slate-600 cursor-not-allowed')
                            ]"
                            preserve-scroll
                        />
                    </div>
                </div>
            </div>

            <!-- Pages View Tab -->
            <div v-else-if="activeTab === 'pages'" class="rounded-3xl bg-slate-900/50 border border-slate-800/80 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-950/70 text-slate-400 border-b border-slate-800 font-semibold uppercase tracking-wider text-[11px]">
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
                                    <a
                                        :href="page.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-[11px] text-indigo-400 hover:underline font-mono truncate max-w-md flex items-center space-x-1 mt-0.5"
                                    >
                                        <span class="truncate">{{ page.url }}</span>
                                        <ExternalLink class="w-3 h-3 flex-shrink-0" />
                                    </a>
                                </td>
                                <td class="p-4">
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold"
                                        :class="page.status_code === 200 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20'"
                                    >
                                        {{ page.status_code }}
                                    </span>
                                </td>
                                <td class="p-4 text-slate-400 font-mono">{{ page.response_time_ms }} ms</td>
                                <td class="p-4 text-slate-400">{{ page.word_count }}</td>
                                <td class="p-4 text-slate-400 font-mono">{{ page.depth }}</td>
                                <td class="p-4 text-right">
                                    <span :class="page.is_indexable ? 'text-emerald-400 font-semibold' : 'text-slate-500'">
                                        {{ page.is_indexable ? t('common.yes') : t('common.no') }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pages Pagination -->
                <div
                    v-if="pages.links && pages.links.length > 3"
                    class="flex items-center justify-center space-x-1 p-4 border-t border-slate-800 bg-slate-950/40"
                >
                    <Component
                        :is="link.url ? Link : 'span'"
                        v-for="(link, idx) in pages.links"
                        :key="idx"
                        :href="link.url || '#'"
                        v-html="link.label"
                        class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all"
                        :class="[
                            link.active
                                ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30'
                                : (link.url
                                    ? 'bg-slate-900 text-slate-300 hover:text-white hover:bg-slate-800 border border-slate-800'
                                    : 'text-slate-600 cursor-not-allowed')
                        ]"
                        preserve-scroll
                    />
                </div>
            </div>
        </div>

        <!-- Finding Inspect Detail Modal -->
        <div
            v-if="selectedFinding"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm transition-all"
            @click.self="selectedFinding = null"
        >
            <div class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-2xl overflow-hidden shadow-2xl animate-in fade-in zoom-in-95 duration-200">
                <!-- Modal Header -->
                <div class="p-6 border-b border-slate-800 flex items-start justify-between gap-4">
                    <div class="space-y-1.5">
                        <div class="flex items-center space-x-2">
                            <span
                                class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                :class="selectedFinding.severity === 'critical' ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : (selectedFinding.severity === 'warning' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20')"
                            >
                                {{ selectedFinding.severity }}
                            </span>
                            <span class="capitalize px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-800 text-slate-300 border border-slate-700">
                                {{ selectedFinding.category }}
                            </span>
                            <span class="text-[10px] font-mono text-slate-500">{{ selectedFinding.rule_code }}</span>
                        </div>
                        <h3 class="text-lg font-bold text-white">{{ selectedFinding.title }}</h3>
                    </div>
                    <button
                        @click="selectedFinding = null"
                        class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white transition-colors"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                    <!-- Affected Page Card -->
                    <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800/80 space-y-2.5">
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider flex items-center space-x-1.5">
                            <Globe class="w-3.5 h-3.5 text-indigo-400" />
                            <span>{{ t('crawls.affected_url') }}</span>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div class="font-mono text-xs text-indigo-300 break-all select-all">
                                {{ selectedFinding.page?.url || selectedFinding.url || t('crawls.entire_site') }}
                            </div>

                            <div v-if="selectedFinding.page?.url || selectedFinding.url" class="flex items-center space-x-2 flex-shrink-0">
                                <button
                                    @click="copyToClipboard(selectedFinding.page?.url || selectedFinding.url)"
                                    class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-700 text-xs flex items-center space-x-1 transition-colors"
                                >
                                    <component :is="copySuccess ? Check : Copy" class="w-3 h-3 text-indigo-400" />
                                    <span>{{ copySuccess ? t('crawls.copied') : t('crawls.copy_url') }}</span>
                                </button>
                                <a
                                    :href="selectedFinding.page?.url || selectedFinding.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="px-2.5 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs flex items-center space-x-1 font-medium transition-colors"
                                >
                                    <span>{{ t('crawls.open_in_new_tab') }}</span>
                                    <ExternalLink class="w-3 h-3" />
                                </a>
                            </div>
                        </div>

                        <!-- Page Title & Status -->
                        <div v-if="selectedFinding.page?.title" class="pt-2 border-t border-slate-900 flex items-center justify-between text-xs">
                            <div class="flex items-center space-x-2 text-slate-400 truncate">
                                <span class="font-medium text-slate-300">{{ t('crawls.page_title') }}:</span>
                                <span class="text-white truncate">"{{ selectedFinding.page.title }}"</span>
                            </div>
                            <span
                                v-if="selectedFinding.page?.status_code"
                                class="px-2 py-0.5 rounded text-[10px] font-mono font-bold"
                                :class="selectedFinding.page.status_code === 200 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'"
                            >
                                HTTP {{ selectedFinding.page.status_code }}
                            </span>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="space-y-1.5">
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider flex items-center space-x-1.5">
                            <Info class="w-3.5 h-3.5 text-slate-400" />
                            <span>{{ t('crawls.issue_description') }}</span>
                        </div>
                        <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800 text-xs text-slate-300 leading-relaxed">
                            {{ selectedFinding.description }}
                        </div>
                    </div>

                    <!-- Technical Evidence -->
                    <div v-if="selectedFinding.evidence" class="space-y-1.5">
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider flex items-center space-x-1.5">
                            <FileCode class="w-3.5 h-3.5 text-amber-400" />
                            <span>{{ t('crawls.technical_evidence') }}</span>
                        </div>
                        <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800 text-xs space-y-2">
                            <!-- Title Length Evidence Details -->
                            <div v-if="selectedFinding.rule_code === 'TITLE_TOO_SHORT' || selectedFinding.rule_code === 'TITLE_TOO_LONG'" class="space-y-2">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-400">{{ t('crawls.evidence_current') }}:</span>
                                    <span class="font-mono font-bold text-amber-400">{{ selectedFinding.evidence.length }} {{ t('crawls.chars') }}</span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-400">{{ t('crawls.recommended_title_len') }}</span>
                                    <span class="font-mono text-emerald-400">50-60 {{ t('crawls.chars') }}</span>
                                </div>
                                <div v-if="selectedFinding.evidence.title" class="pt-2 border-t border-slate-900 font-mono text-[11px] text-slate-300 break-all bg-slate-900/60 p-2 rounded-lg">
                                    "{{ selectedFinding.evidence.title }}"
                                </div>
                            </div>

                            <!-- H1 Multiple Evidence Details -->
                            <div v-else-if="selectedFinding.rule_code === 'H1_MULTIPLE'" class="space-y-2">
                                <div class="text-slate-400">{{ selectedFinding.evidence.h1_tags?.length || 2 }} {{ t('crawls.evidence_h1_found') }}:</div>
                                <ul class="list-disc list-inside space-y-1 font-mono text-[11px] text-slate-300">
                                    <li v-for="(h, idx) in selectedFinding.evidence.h1_tags" :key="idx" class="truncate">
                                        "{{ h }}"
                                    </li>
                                </ul>
                            </div>

                            <!-- General JSON Evidence Viewer -->
                            <pre v-else class="text-[11px] font-mono text-slate-300 overflow-x-auto p-2 bg-slate-900/60 rounded-lg whitespace-pre-wrap">{{ JSON.stringify(selectedFinding.evidence, null, 2) }}</pre>
                        </div>
                    </div>

                    <!-- SEO Recommendation & Solution -->
                    <div class="space-y-1.5">
                        <div class="text-xs font-semibold text-emerald-400 uppercase tracking-wider flex items-center space-x-1.5">
                            <Lightbulb class="w-3.5 h-3.5 text-amber-400" />
                            <span>{{ t('crawls.solution_recommendation') }}</span>
                        </div>
                        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-xs text-emerald-200 leading-relaxed">
                            {{ selectedFinding.recommendation }}
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-4 sm:p-6 border-t border-slate-800 bg-slate-950/40 flex items-center justify-between">
                    <button
                        type="button"
                        @click="selectedFinding = null"
                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition-colors"
                    >
                        {{ t('crawls.close') }}
                    </button>

                    <button
                        v-if="!isTaskCreated(selectedFinding)"
                        type="button"
                        @click="convertToTask(selectedFinding.id)"
                        class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 flex items-center space-x-1.5 transition-all"
                    >
                        <CheckSquare class="w-3.5 h-3.5" />
                        <span>{{ t('crawls.convert_task') }}</span>
                    </button>
                    <span v-else class="text-xs text-emerald-400 font-semibold flex items-center space-x-1.5 px-3 py-1.5 bg-emerald-500/10 border border-emerald-500/20 rounded-xl">
                        <CheckCircle2 class="w-4 h-4" />
                        <span>{{ t('crawls.task_created') }}</span>
                    </span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
