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
    <AppLayout :title="`Tarama #${crawl.id} - ${project.name}`">
        <Head :title="`Tarama #${crawl.id} - ${project.name}`" />

        <div class="space-y-6">
            <!-- Back & Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center space-x-3">
                    <Link :href="`/projects/${project.id}`" class="p-2 rounded-xl bg-slate-900 border border-slate-800 text-slate-400 hover:text-white transition-colors">
                        <ArrowLeft class="w-4 h-4" />
                    </Link>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h1 class="text-xl sm:text-2xl font-bold text-white">Tarama #{{ crawl.id }} Raporu</h1>
                            <span
                                class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                :class="currentStatus === 'completed' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : (currentStatus === 'running' ? 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 animate-pulse' : 'bg-slate-800 text-slate-400')"
                            >
                                {{ currentStatus }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5 font-mono">
                            {{ project.domain }} &bull; Maks {{ crawl.max_pages }} sayfa
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
                            <span>Duraklat</span>
                        </button>
                        <button
                            @click="cancelCrawl"
                            class="px-3.5 py-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 text-xs font-semibold border border-rose-500/20 flex items-center space-x-1.5 transition-all"
                        >
                            <XCircle class="w-3.5 h-3.5" />
                            <span>İptal Et</span>
                        </button>
                    </template>

                    <template v-else>
                        <a
                            :href="`/projects/${project.id}/crawls/${crawl.id}/export-csv`"
                            class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white text-xs font-semibold border border-slate-800 flex items-center space-x-1.5 transition-all"
                        >
                            <FileSpreadsheet class="w-3.5 h-3.5 text-emerald-400" />
                            <span>CSV İndir</span>
                        </a>

                        <Link
                            :href="`/projects/${project.id}/crawls/${crawl.id}/generate-pdf`"
                            method="post"
                            as="button"
                            class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 flex items-center space-x-1.5 transition-all"
                        >
                            <FileText class="w-3.5 h-3.5" />
                            <span>PDF Raporu Üret</span>
                        </Link>
                    </template>
                </div>
            </div>

            <!-- Live Progress Bar (if running) -->
            <div v-if="currentStatus === 'running'" class="p-4 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 space-y-2">
                <div class="flex items-center justify-between text-xs font-semibold text-indigo-300">
                    <span class="flex items-center space-x-2">
                        <RefreshCw class="w-3.5 h-3.5 animate-spin text-indigo-400" />
                        <span>Canlı Tarama İlerliyor...</span>
                    </span>
                    <span>{{ pagesCrawled }} / {{ crawl.max_pages }} Sayfa (%{{ Math.round((pagesCrawled / crawl.max_pages) * 100) }})</span>
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
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Sağlık Skoru</span>
                    <span
                        class="text-3xl font-extrabold block mt-2"
                        :class="healthScore >= 80 ? 'text-emerald-400' : (healthScore >= 50 ? 'text-amber-400' : 'text-rose-400')"
                    >
                        {{ healthScore !== null ? healthScore + '%' : '—' }}
                    </span>
                    <span class="text-[11px] text-slate-500 mt-1 block">Şeffaf ceza puanı formülüyle</span>
                </div>

                <div
                    @click="filterSeverity('critical')"
                    class="p-5 rounded-2xl bg-slate-900/50 border border-slate-800/80 hover:border-rose-500/40 cursor-pointer transition-all"
                >
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Kritik Hatalar</span>
                    <span class="text-3xl font-extrabold text-rose-400 block mt-2">{{ severityCounts.critical }}</span>
                    <span class="text-[11px] text-rose-400/80 mt-1 block">Acil müdahale gerekir</span>
                </div>

                <div
                    @click="filterSeverity('warning')"
                    class="p-5 rounded-2xl bg-slate-900/50 border border-slate-800/80 hover:border-amber-500/40 cursor-pointer transition-all"
                >
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Uyarılar</span>
                    <span class="text-3xl font-extrabold text-amber-400 block mt-2">{{ severityCounts.warning }}</span>
                    <span class="text-[11px] text-amber-400/80 mt-1 block">Performansı etkileyen unsurlar</span>
                </div>

                <div
                    @click="filterSeverity('notice')"
                    class="p-5 rounded-2xl bg-slate-900/50 border border-slate-800/80 hover:border-indigo-500/40 cursor-pointer transition-all"
                >
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Öneriler</span>
                    <span class="text-3xl font-extrabold text-indigo-400 block mt-2">{{ severityCounts.notice }}</span>
                    <span class="text-[11px] text-indigo-400/80 mt-1 block">İnce ayar optimizasyonları</span>
                </div>
            </div>

            <!-- Tabs: Findings vs Pages -->
            <div class="border-b border-slate-800 flex space-x-6 text-sm font-semibold">
                <button
                    @click="activeTab = 'findings'"
                    class="pb-3 transition-colors border-b-2"
                    :class="activeTab === 'findings' ? 'border-indigo-500 text-indigo-400' : 'border-transparent text-slate-400 hover:text-white'"
                >
                    SEO Bulguları ({{ findings.total }})
                </button>
                <button
                    @click="activeTab = 'pages'"
                    class="pb-3 transition-colors border-b-2"
                    :class="activeTab === 'pages' ? 'border-indigo-500 text-indigo-400' : 'border-transparent text-slate-400 hover:text-white'"
                >
                    Taranan Sayfalar ({{ pages.total }})
                </button>
            </div>

            <!-- Findings Tab Content -->
            <div v-if="activeTab === 'findings'" class="space-y-4">
                <!-- Severity Filter Bar -->
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        @click="filterSeverity()"
                        class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors"
                        :class="!filters.severity ? 'bg-indigo-600 text-white' : 'bg-slate-900 text-slate-400 hover:text-white'"
                    >
                        Tümü ({{ findings.total }})
                    </button>
                    <button
                        @click="filterSeverity('critical')"
                        class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors"
                        :class="filters.severity === 'critical' ? 'bg-rose-600 text-white' : 'bg-slate-900 text-rose-400 hover:bg-rose-500/10'"
                    >
                        Kritik ({{ severityCounts.critical }})
                    </button>
                    <button
                        @click="filterSeverity('warning')"
                        class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors"
                        :class="filters.severity === 'warning' ? 'bg-amber-600 text-white' : 'bg-slate-900 text-amber-400 hover:bg-amber-500/10'"
                    >
                        Uyarı ({{ severityCounts.warning }})
                    </button>
                    <button
                        @click="filterSeverity('notice')"
                        class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors"
                        :class="filters.severity === 'notice' ? 'bg-indigo-600 text-white' : 'bg-slate-900 text-indigo-400 hover:bg-indigo-500/10'"
                    >
                        Öneri ({{ severityCounts.notice }})
                    </button>
                </div>

                <!-- Findings Cards -->
                <div v-if="findings.data.length === 0" class="p-8 rounded-3xl bg-slate-900/40 border border-slate-800 text-center text-xs text-slate-500">
                    Seçili filtrelere uygun SEO bulgusu bulunamadı.
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="finding in findings.data"
                        :key="finding.id"
                        class="p-5 rounded-2xl bg-slate-900/50 border border-slate-800/80 hover:border-slate-700/80 transition-all space-y-3"
                    >
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div class="flex items-center space-x-2.5">
                                <span
                                    class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider"
                                    :class="finding.severity === 'critical' ? 'bg-rose-500/15 text-rose-400 border border-rose-500/30' : (finding.severity === 'warning' ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'bg-indigo-500/15 text-indigo-400 border border-indigo-500/30')"
                                >
                                    {{ finding.severity }}
                                </span>
                                <span class="text-xs font-mono text-slate-500">{{ finding.rule_code }}</span>
                                <span class="text-xs text-slate-400">&bull;</span>
                                <span class="text-xs text-slate-400 capitalize">{{ finding.category }}</span>
                            </div>

                            <div>
                                <button
                                    @click="convertToTask(finding.id)"
                                    class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-medium flex items-center space-x-1.5 transition-colors"
                                >
                                    <CheckSquare class="w-3.5 h-3.5 text-indigo-400" />
                                    <span>Göreve Dönüştür</span>
                                </button>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-sm font-bold text-white">{{ finding.title }}</h3>
                            <p class="text-xs text-slate-400 mt-1 leading-relaxed">{{ finding.description }}</p>
                        </div>

                        <!-- Affected URL -->
                        <div v-if="finding.page" class="p-2.5 rounded-xl bg-slate-950/60 border border-slate-850 flex items-center justify-between text-xs">
                            <div class="flex items-center space-x-2 truncate">
                                <span class="text-slate-500 shrink-0">Etkilenen Sayfa:</span>
                                <a :href="finding.page.url" target="_blank" class="text-indigo-400 hover:underline truncate">
                                    {{ finding.page.url }}
                                </a>
                            </div>
                            <a :href="finding.page.url" target="_blank" class="text-slate-500 hover:text-white shrink-0 ml-2">
                                <ExternalLink class="w-3.5 h-3.5" />
                            </a>
                        </div>

                        <!-- Recommendation box -->
                        <div class="p-3 rounded-xl bg-indigo-500/5 border border-indigo-500/20 text-xs">
                            <strong class="text-indigo-300 font-semibold block mb-0.5">Çözüm Önerisi:</strong>
                            <span class="text-slate-300 leading-relaxed">{{ finding.recommendation }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pages Tab Content -->
            <div v-if="activeTab === 'pages'" class="space-y-4">
                <div class="rounded-3xl bg-slate-900/50 border border-slate-800/80 overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                            <tr>
                                <th class="p-4">URL</th>
                                <th class="p-4">Durum</th>
                                <th class="p-4">Başlık (Title)</th>
                                <th class="p-4">Kelime</th>
                                <th class="p-4">İndekslenebilir</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <tr v-for="page in pages.data" :key="page.id" class="hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 max-w-xs truncate font-mono text-slate-300">
                                    <a :href="page.url" target="_blank" class="hover:text-indigo-400 flex items-center space-x-1">
                                        <span class="truncate">{{ page.url }}</span>
                                        <ExternalLink class="w-3 h-3 shrink-0" />
                                    </a>
                                </td>
                                <td class="p-4">
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="page.status_code === 200 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400'"
                                    >
                                        {{ page.status_code }}
                                    </span>
                                </td>
                                <td class="p-4 text-white font-medium max-w-xs truncate">{{ page.title || '—' }}</td>
                                <td class="p-4 text-slate-400">{{ page.word_count }}</td>
                                <td class="p-4">
                                    <span v-if="page.is_indexable" class="text-emerald-400 font-semibold">Evet</span>
                                    <span v-else class="text-rose-400 font-semibold">Noindex</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
