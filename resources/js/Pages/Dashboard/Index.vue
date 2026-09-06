<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Globe,
    Layers,
    AlertTriangle,
    CheckCircle,
    Activity,
    ArrowUpRight,
    Plus,
    Clock,
    Search,
    ChevronRight,
    ShieldAlert
} from 'lucide-vue-next';
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps<{
    stats: {
        total_projects: number;
        total_pages_crawled: number;
        critical_issues: number;
        warning_issues: number;
        open_tasks: number;
        avg_health_score: number | null;
    };
    projects: Array<any>;
    recentCrawls: Array<any>;
    criticalFindings: Array<any>;
}>();
</script>

<template>
    <AppLayout :title="t('nav.dashboard')">
        <Head :title="t('nav.dashboard')" />

        <div class="space-y-8">
            <!-- Header Greeting -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white">{{ t('dashboard.title') }}</h1>
                    <p class="text-sm text-slate-400 mt-1">
                        {{ t('dashboard.subtitle') }}
                    </p>
                </div>
                <div>
                    <Link
                        href="/projects/create"
                        class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold shadow-lg shadow-indigo-600/30 transition-all"
                    >
                        <Plus class="w-4 h-4" />
                        <span>{{ t('dashboard.add_website') }}</span>
                    </Link>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Average Health Score -->
                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('dashboard.avg_health_score') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-400 flex items-center justify-center">
                            <Activity class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline space-x-2">
                        <span class="text-3xl font-extrabold text-white">
                            {{ stats.avg_health_score !== null ? stats.avg_health_score + '%' : '—' }}
                        </span>
                        <span class="text-xs text-slate-500">avg</span>
                    </div>
                    <div class="mt-2 text-xs text-slate-400">
                        {{ stats.avg_health_score ? (stats.avg_health_score >= 80 ? t('common.completed') : t('common.pending')) : t('dashboard.no_audits') }}
                    </div>
                </div>

                <!-- Total Projects -->
                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('dashboard.total_websites') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 flex items-center justify-center">
                            <Globe class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="text-3xl font-extrabold text-white">{{ stats.total_projects }}</span>
                    </div>
                    <div class="mt-2 text-xs text-slate-400">{{ t('common.active') }}</div>
                </div>

                <!-- Pages Crawled -->
                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('projects.pages_crawled') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center">
                            <Layers class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="text-3xl font-extrabold text-white">{{ stats.total_pages_crawled }}</span>
                    </div>
                    <div class="mt-2 text-xs text-slate-400">{{ t('common.all') }}</div>
                </div>

                <!-- Critical Issues -->
                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('dashboard.active_issues') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-rose-500/10 text-rose-400 flex items-center justify-center">
                            <ShieldAlert class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="text-3xl font-extrabold text-rose-400">{{ stats.critical_issues }}</span>
                    </div>
                    <div class="mt-2 text-xs text-slate-400">{{ t('common.failed') }}</div>
                </div>

                <!-- Open Tasks -->
                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ t('nav.technical_crawler') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center">
                            <CheckCircle class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="text-3xl font-extrabold text-white">{{ stats.open_tasks }}</span>
                    </div>
                    <div class="mt-2 text-xs text-slate-400">{{ t('common.running') }}</div>
                </div>
            </div>

            <!-- Two Columns Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Projects Overview (Left 2 cols) -->
                <div class="lg:col-span-2 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-bold text-white flex items-center space-x-2">
                            <Globe class="w-5 h-5 text-indigo-400" />
                            <span>{{ t('projects.title') }}</span>
                        </h2>
                        <Link href="/projects" class="text-xs text-indigo-400 hover:text-indigo-300 font-medium">
                            {{ t('dashboard.view_all') }} ({{ stats.total_projects }}) &rarr;
                        </Link>
                    </div>

                    <div v-if="projects.length === 0" class="p-8 rounded-2xl bg-slate-900/40 border border-slate-800/80 text-center">
                        <Globe class="w-12 h-12 text-slate-600 mx-auto mb-3" />
                        <h3 class="text-base font-semibold text-white">{{ t('dashboard.no_audits') }}</h3>
                        <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1 mb-4">
                            {{ t('projects.subtitle') }}
                        </p>
                        <Link
                            href="/projects/create"
                            class="inline-flex items-center space-x-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30"
                        >
                            <Plus class="w-3.5 h-3.5" />
                            <span>{{ t('dashboard.add_website') }}</span>
                        </Link>
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="project in projects"
                            :key="project.id"
                            class="p-4 rounded-2xl bg-slate-900/50 border border-slate-800/80 hover:border-slate-700/80 transition-all flex items-center justify-between"
                        >
                            <div class="flex items-center space-x-4 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-slate-800 flex items-center justify-center text-indigo-400 shrink-0 font-bold text-sm">
                                    {{ project.name.substring(0, 2).toUpperCase() }}
                                </div>
                                <div class="truncate">
                                    <Link :href="`/projects/${project.id}`" class="text-sm font-semibold text-white hover:text-indigo-400 transition-colors truncate block">
                                        {{ project.name }}
                                    </Link>
                                    <div class="text-xs text-slate-500 truncate flex items-center space-x-2">
                                        <span>{{ project.domain }}</span>
                                        <span>&bull;</span>
                                        <span>{{ project.target_country }} ({{ project.target_language }})</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-4 shrink-0">
                                <!-- Health score badge -->
                                <div v-if="project.latest_crawl?.health_score !== undefined" class="text-right">
                                    <div
                                        class="text-xs px-2.5 py-1 rounded-full font-bold inline-block"
                                        :class="project.latest_crawl.health_score >= 80 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : (project.latest_crawl.health_score >= 50 ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20')"
                                    >
                                        {{ project.latest_crawl.health_score }}% {{ t('projects.health_score') }}
                                    </div>
                                </div>
                                <span v-else class="text-xs text-slate-500">{{ t('common.pending') }}</span>

                                <Link
                                    :href="`/projects/${project.id}`"
                                    class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors"
                                >
                                    <ChevronRight class="w-4 h-4" />
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Crawls (Right 1 col) -->
                <div class="space-y-4">
                    <h2 class="text-lg font-bold text-white flex items-center space-x-2">
                        <Clock class="w-5 h-5 text-indigo-400" />
                        <span>{{ t('dashboard.recent_crawls') }}</span>
                    </h2>

                    <div v-if="recentCrawls.length === 0" class="p-6 rounded-2xl bg-slate-900/40 border border-slate-800/80 text-center text-xs text-slate-500">
                        {{ t('dashboard.no_audits') }}
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="crawl in recentCrawls"
                            :key="crawl.id"
                            class="p-4 rounded-2xl bg-slate-900/50 border border-slate-800/80 space-y-2 text-xs"
                        >
                            <div class="flex items-center justify-between">
                                <Link :href="`/projects/${crawl.project_id}`" class="font-semibold text-white hover:text-indigo-400 transition-colors truncate">
                                    {{ crawl.project?.name || 'Project' }}
                                </Link>
                                <span
                                    class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                    :class="crawl.status === 'completed' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-amber-500/10 text-amber-400'"
                                >
                                    {{ crawl.status }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between text-slate-400 text-[11px]">
                                <span>{{ crawl.pages_crawled }} {{ t('projects.pages_crawled') }}</span>
                                <span class="text-indigo-400 font-bold">{{ crawl.health_score !== null ? crawl.health_score + '%' : '' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
