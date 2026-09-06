<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Shield,
    Users,
    Briefcase,
    Globe,
    Activity,
    AlertOctagon,
    CheckCircle2,
    XCircle,
    Clock,
    Database,
    Server,
    Search,
    X,
    ExternalLink,
    Eye,
    Layers,
    Calendar,
    Mail,
    Check,
    Copy,
    Tag,
    BarChart3,
    CheckSquare,
    FileText,
    CreditCard,
    Filter,
    ArrowUpRight,
    Laptop
} from 'lucide-vue-next';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps<{
    stats: {
        total_users: number;
        total_workspaces: number;
        total_projects: number;
        total_crawls: number;
        failed_jobs: number;
        db_status: boolean;
        redis_status: boolean;
        php_version: string;
        laravel_version: string;
    };
    recentLogs: Array<any>;
    users: {
        data: Array<any>;
        links: Array<any>;
        total: number;
        current_page?: number;
        last_page?: number;
    };
    filters: {
        search?: string;
        filter?: string;
        tab?: 'users' | 'system' | 'logs';
    };
}>();

const activeTab = ref<'users' | 'system' | 'logs'>(props.filters.tab || 'users');
const searchQuery = ref(props.filters.search || '');
const currentFilter = ref(props.filters.filter || 'all');
const selectedUser = ref<any>(null);
const selectedLog = ref<any>(null);
const logCategoryFilter = ref<string>('all');
const copySuccess = ref(false);

let searchTimeout: any = null;

const applyFilters = (newFilter?: string) => {
    const filterVal = newFilter !== undefined ? newFilter : currentFilter.value;
    router.get(
        '/admin',
        {
            tab: activeTab.value,
            search: searchQuery.value.trim() || undefined,
            filter: filterVal !== 'all' ? filterVal : undefined,
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

const setFilter = (filterName: string) => {
    currentFilter.value = filterName;
    applyFilters(filterName);
};

const switchTab = (tabName: 'users' | 'system' | 'logs') => {
    activeTab.value = tabName;
    router.get(
        '/admin',
        {
            tab: tabName,
            search: searchQuery.value.trim() || undefined,
            filter: currentFilter.value !== 'all' ? currentFilter.value : undefined,
        },
        {
            preserveState: true,
            replace: true,
        }
    );
};

const copyToClipboard = (text: string) => {
    if (!text) return;
    navigator.clipboard.writeText(text);
    copySuccess.value = true;
    setTimeout(() => {
        copySuccess.value = false;
    }, 2000);
};

// Collect all projects across all workspaces for a given user
const getUserProjects = (user: any) => {
    const projects: any[] = [];
    const seen = new Set<number>();
    if (user.workspaces) {
        for (const ws of user.workspaces) {
            if (ws.projects) {
                for (const p of ws.projects) {
                    if (!seen.has(p.id)) {
                        seen.add(p.id);
                        projects.push({
                            ...p,
                            workspaceName: ws.name,
                            isActiveWorkspace: user.current_workspace_id === ws.id,
                        });
                    }
                }
            }
        }
    }
    return projects;
};

// Human-readable mapping for Audit Log Actions
const getActionMeta = (action: string) => {
    const fullKey = `admin.action_${action.replace(/\./g, '_')}`;
    const translated = (t as any)(fullKey);
    const hasTranslation = translated && !translated.startsWith('admin.');

    if (action.startsWith('auth.') || action.startsWith('profile.')) {
        const isDestructive = action.includes('deleted') || action.includes('terminated') || action.includes('disabled');
        return {
            label: hasTranslation ? translated : action,
            color: isDestructive ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
            badgeText: t('admin.filter_auth_logs'),
            icon: Shield,
        };
    }
    if (action.startsWith('project.')) {
        return {
            label: hasTranslation ? translated : action,
            color: action.includes('deleted') ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : 'bg-cyan-500/10 text-cyan-400 border border-cyan-500/20',
            badgeText: t('admin.filter_project_logs'),
            icon: Globe,
        };
    }
    if (action.startsWith('crawl.')) {
        return {
            label: hasTranslation ? translated : action,
            color: action.includes('cancelled') ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20',
            badgeText: t('admin.filter_crawl_logs'),
            icon: Activity,
        };
    }
    if (action.startsWith('workspace.')) {
        return {
            label: hasTranslation ? translated : action,
            color: action.includes('deleted') || action.includes('removed') ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : 'bg-violet-500/10 text-violet-400 border border-violet-500/20',
            badgeText: t('admin.filter_workspace_logs'),
            icon: Briefcase,
        };
    }
    if (action.startsWith('task.')) {
        return {
            label: hasTranslation ? translated : action,
            color: action.includes('deleted') ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20',
            badgeText: 'SEO Görevi',
            icon: CheckSquare,
        };
    }
    if (action.startsWith('keyword.') || action.startsWith('keywords.')) {
        return {
            label: hasTranslation ? translated : action,
            color: 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
            badgeText: 'Sıra Takibi',
            icon: Tag,
        };
    }
    if (action.startsWith('report.')) {
        return {
            label: hasTranslation ? translated : action,
            color: 'bg-slate-800 text-slate-300 border border-slate-700',
            badgeText: 'Rapor',
            icon: FileText,
        };
    }
    if (action.startsWith('billing.')) {
        return {
            label: hasTranslation ? translated : action,
            color: 'bg-amber-500/10 text-amber-400 border border-amber-500/20',
            badgeText: 'Abonelik',
            icon: CreditCard,
        };
    }

    return {
        label: hasTranslation ? translated : action,
        color: 'bg-slate-800 text-slate-300 border border-slate-700',
        badgeText: 'Sistem',
        icon: Activity,
    };
};

// Formatted details summary
const formatLogDetails = (log: any) => {
    const d = log.details || log.meta || {};
    const items: Array<{ label: string; value: string; isLink?: boolean }> = [];

    if (d.domain) items.push({ label: 'Domain', value: d.domain, isLink: true });
    if (d.start_url) items.push({ label: 'Başlangıç URL', value: d.start_url, isLink: true });
    if (d.url && !d.start_url) items.push({ label: 'URL', value: d.url, isLink: true });
    if (d.name) items.push({ label: 'Ad', value: d.name });
    if (d.title) items.push({ label: 'Başlık', value: d.title });
    if (d.keyword) items.push({ label: 'Kelime', value: d.keyword });
    if (d.email) items.push({ label: 'E-Posta', value: d.email });
    if (d.new_plan) items.push({ label: 'Plan', value: d.new_plan });
    if (d.role) items.push({ label: 'Rol', value: d.role });
    if (d.pages_crawled !== undefined) items.push({ label: 'Sayfa', value: `${d.pages_crawled} sayfa` });
    if (d.health_score !== undefined && d.health_score !== null) items.push({ label: 'Sağlık', value: `%${d.health_score}` });
    if (d.count !== undefined) items.push({ label: 'Adet', value: `${d.count}` });
    if (d.method) items.push({ label: 'Yöntem', value: d.method });
    if (d.provider) items.push({ label: 'Sağlayıcı', value: d.provider });

    return items;
};

// Filtered logs by category
const filteredAuditLogs = computed(() => {
    if (!props.recentLogs) return [];
    if (logCategoryFilter.value === 'all') return props.recentLogs;

    return props.recentLogs.filter((log: any) => {
        if (logCategoryFilter.value === 'auth') return log.action?.startsWith('auth.') || log.action?.startsWith('profile.');
        if (logCategoryFilter.value === 'project') return log.action?.startsWith('project.');
        if (logCategoryFilter.value === 'crawl') return log.action?.startsWith('crawl.');
        if (logCategoryFilter.value === 'workspace') return log.action?.startsWith('workspace.');
        if (logCategoryFilter.value === 'task') return log.action?.startsWith('task.') || log.action?.startsWith('keyword');
        return true;
    });
});
</script>

<template>
    <AppLayout :title="$t('admin.page_title')">
        <Head :title="$t('admin.page_title')" />

        <div class="space-y-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-pink-500/10 text-pink-400 border border-pink-500/20 text-xs font-semibold mb-2">
                        <Shield class="w-3.5 h-3.5" />
                        <span>{{ $t('admin.badge') }}</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white">{{ $t('admin.title') }}</h1>
                    <p class="text-sm text-slate-400 mt-1">
                        {{ $t('admin.users_and_sites_desc') }}
                    </p>
                </div>
            </div>

            <!-- Stats Highlight Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="p-5 rounded-3xl bg-slate-900/50 border border-slate-800/80 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ $t('admin.users_total') }}</span>
                        <Users class="w-4 h-4 text-indigo-400" />
                    </div>
                    <div class="text-2xl sm:text-3xl font-extrabold text-white">{{ stats.total_users }}</div>
                </div>

                <div class="p-5 rounded-3xl bg-slate-900/50 border border-slate-800/80 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ $t('admin.workspaces_total') }}</span>
                        <Briefcase class="w-4 h-4 text-emerald-400" />
                    </div>
                    <div class="text-2xl sm:text-3xl font-extrabold text-white">{{ stats.total_workspaces }}</div>
                </div>

                <div class="p-5 rounded-3xl bg-slate-900/50 border border-slate-800/80 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ $t('admin.projects_total') }}</span>
                        <Globe class="w-4 h-4 text-cyan-400" />
                    </div>
                    <div class="text-2xl sm:text-3xl font-extrabold text-white">{{ stats.total_projects }}</div>
                </div>

                <div class="p-5 rounded-3xl bg-slate-900/50 border border-slate-800/80 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ $t('admin.crawls_total') }}</span>
                        <Activity class="w-4 h-4 text-pink-400" />
                    </div>
                    <div class="text-2xl sm:text-3xl font-extrabold text-white">{{ stats.total_crawls }}</div>
                </div>
            </div>

            <!-- View Switcher Tabs -->
            <div class="flex items-center space-x-2 border-b border-slate-800 pb-3">
                <button
                    @click="switchTab('users')"
                    class="px-4 py-2 rounded-xl text-xs font-semibold transition-all flex items-center space-x-2"
                    :class="activeTab === 'users' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white bg-slate-900/60'"
                >
                    <Users class="w-3.5 h-3.5" />
                    <span>{{ $t('admin.tab_users_sites') }} ({{ users.total }})</span>
                </button>
                <button
                    @click="switchTab('system')"
                    class="px-4 py-2 rounded-xl text-xs font-semibold transition-all flex items-center space-x-2"
                    :class="activeTab === 'system' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white bg-slate-900/60'"
                >
                    <Server class="w-3.5 h-3.5" />
                    <span>{{ $t('admin.tab_system_health') }}</span>
                </button>
                <button
                    @click="switchTab('logs')"
                    class="px-4 py-2 rounded-xl text-xs font-semibold transition-all flex items-center space-x-2"
                    :class="activeTab === 'logs' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white bg-slate-900/60'"
                >
                    <Clock class="w-3.5 h-3.5" />
                    <span>{{ $t('admin.tab_audit_logs') }} ({{ recentLogs.length }})</span>
                </button>
            </div>

            <!-- Tab 1: Users & Connected Sites -->
            <div v-if="activeTab === 'users'" class="space-y-4">
                <!-- Search & Filters Bar -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-900/40 p-3.5 rounded-2xl border border-slate-800/80">
                    <!-- Filter Pills -->
                    <div class="flex items-center flex-wrap gap-1.5 text-xs">
                        <button
                            @click="setFilter('all')"
                            class="px-3 py-1.5 rounded-lg border transition-all"
                            :class="currentFilter === 'all' ? 'bg-indigo-600 text-white border-indigo-500 font-semibold shadow-sm' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-white'"
                        >
                            {{ $t('admin.filter_all_users') }} ({{ stats.total_users }})
                        </button>
                        <button
                            @click="setFilter('has_sites')"
                            class="px-3 py-1.5 rounded-lg border transition-all flex items-center space-x-1.5"
                            :class="currentFilter === 'has_sites' ? 'bg-cyan-500/20 text-cyan-300 border-cyan-500/40 font-semibold' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-cyan-400'"
                        >
                            <Globe class="w-3 h-3 text-cyan-400" />
                            <span>{{ $t('admin.filter_with_sites') }}</span>
                        </button>
                        <button
                            @click="setFilter('no_sites')"
                            class="px-3 py-1.5 rounded-lg border transition-all"
                            :class="currentFilter === 'no_sites' ? 'bg-slate-800 text-slate-200 border-slate-700 font-semibold' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-white'"
                        >
                            {{ $t('admin.filter_no_sites') }}
                        </button>
                        <button
                            @click="setFilter('admin')"
                            class="px-3 py-1.5 rounded-lg border transition-all flex items-center space-x-1.5"
                            :class="currentFilter === 'admin' ? 'bg-pink-500/20 text-pink-300 border-pink-500/40 font-semibold' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-pink-400'"
                        >
                            <Shield class="w-3 h-3 text-pink-400" />
                            <span>{{ $t('admin.filter_admins') }}</span>
                        </button>
                    </div>

                    <!-- Search Box -->
                    <div class="relative min-w-[240px] sm:w-80">
                        <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                        <input
                            type="text"
                            v-model="searchQuery"
                            @input="onSearchInput"
                            :placeholder="$t('admin.search_users_placeholder')"
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

                <!-- Users Table -->
                <div class="rounded-3xl bg-slate-900/50 border border-slate-800/80 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-950/70 text-slate-400 border-b border-slate-800 font-semibold uppercase tracking-wider text-[11px]">
                                <tr>
                                    <th class="p-4 w-64">{{ $t('admin.col_user') }}</th>
                                    <th class="p-4 w-52">{{ $t('admin.col_active_workspace') }}</th>
                                    <th class="p-4 w-52">{{ $t('admin.col_all_workspaces') }}</th>
                                    <th class="p-4 min-w-[280px]">{{ $t('admin.col_monitored_sites') }}</th>
                                    <th class="p-4 text-right w-28">{{ $t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                <tr v-if="users.data.length === 0">
                                    <td colspan="5" class="p-12 text-center text-slate-500">
                                        <Users class="w-8 h-8 mx-auto mb-2 text-slate-600" />
                                        <div class="text-sm font-medium text-slate-400">{{ $t('admin.no_users_found') }}</div>
                                    </td>
                                </tr>
                                <tr
                                    v-for="user in users.data"
                                    :key="user.id"
                                    class="hover:bg-slate-800/30 transition-colors group"
                                >
                                    <!-- User Column -->
                                    <td class="p-4 align-top">
                                        <div class="flex items-start space-x-3">
                                            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-600 flex items-center justify-center text-xs font-bold text-white shrink-0 mt-0.5 shadow-md shadow-indigo-600/20">
                                                {{ user.name ? user.name.charAt(0).toUpperCase() : 'U' }}
                                            </div>
                                            <div class="min-w-0">
                                                <div class="flex items-center space-x-2">
                                                    <span class="font-bold text-white text-sm truncate group-hover:text-indigo-300 transition-colors">
                                                        {{ user.name }}
                                                    </span>
                                                    <span
                                                        v-if="user.is_platform_admin"
                                                        class="px-2 py-0.2 rounded-full text-[9px] font-bold uppercase tracking-wider bg-pink-500/10 text-pink-400 border border-pink-500/20"
                                                    >
                                                        Admin
                                                    </span>
                                                </div>
                                                <div class="text-[11px] text-slate-400 truncate mt-0.5 flex items-center space-x-1">
                                                    <Mail class="w-3 h-3 text-slate-500 flex-shrink-0" />
                                                    <span class="truncate">{{ user.email }}</span>
                                                </div>
                                                <div class="text-[10px] text-slate-500 mt-1 flex items-center space-x-1">
                                                    <Calendar class="w-3 h-3 text-slate-600 flex-shrink-0" />
                                                    <span>{{ $t('admin.col_registered') }}: {{ new Date(user.created_at).toLocaleDateString() }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Active Workspace Column -->
                                    <td class="p-4 align-top">
                                        <div v-if="user.current_workspace" class="space-y-1">
                                            <div class="font-semibold text-slate-200 text-xs flex items-center space-x-1.5">
                                                <Briefcase class="w-3.5 h-3.5 text-emerald-400 flex-shrink-0" />
                                                <span class="truncate">{{ user.current_workspace.name }}</span>
                                            </div>
                                            <div class="flex items-center space-x-1.5">
                                                <span
                                                    class="px-2 py-0.5 rounded text-[10px] font-medium"
                                                    :class="user.current_workspace.owner_id === user.id ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-800 text-slate-300'"
                                                >
                                                    {{ user.current_workspace.owner_id === user.id ? $t('admin.owner_badge') : $t('admin.member_badge') }}
                                                </span>
                                                <span
                                                    v-if="user.current_workspace.is_active"
                                                    class="w-1.5 h-1.5 rounded-full bg-emerald-400"
                                                    title="Aktif"
                                                ></span>
                                            </div>
                                        </div>
                                        <span v-else class="text-xs text-slate-500 italic bg-slate-950 px-2 py-1 rounded-lg border border-slate-800">
                                            {{ $t('admin.no_active_workspace') }}
                                        </span>
                                    </td>

                                    <!-- All Connected Workspaces Column -->
                                    <td class="p-4 align-top">
                                        <div v-if="user.workspaces && user.workspaces.length > 0" class="space-y-1.5">
                                            <div class="text-[11px] font-semibold text-slate-400">
                                                {{ user.workspaces.length }} {{ $t('workspaces.page_title') }}
                                            </div>
                                            <div class="flex flex-wrap gap-1 max-w-[200px]">
                                                <span
                                                    v-for="ws in user.workspaces"
                                                    :key="ws.id"
                                                    class="px-2 py-0.5 rounded-md text-[10px] bg-slate-800/90 text-slate-300 border border-slate-700/60 truncate max-w-[190px]"
                                                    :title="ws.name"
                                                >
                                                    {{ ws.name }}
                                                </span>
                                            </div>
                                        </div>
                                        <span v-else class="text-xs text-slate-500 italic">—</span>
                                    </td>

                                    <!-- Monitored Sites & Domains Column -->
                                    <td class="p-4 align-top">
                                        <div v-if="getUserProjects(user).length > 0" class="space-y-2">
                                            <div
                                                v-for="project in getUserProjects(user)"
                                                :key="project.id"
                                                class="p-2.5 rounded-xl bg-slate-950/70 border border-slate-800/80 hover:border-slate-700 transition-colors flex items-center justify-between gap-3"
                                            >
                                                <div class="min-w-0 space-y-0.5">
                                                    <div class="flex items-center space-x-1.5">
                                                        <a
                                                            :href="project.start_url || `https://${project.domain}`"
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            class="font-mono text-xs font-semibold text-indigo-400 hover:text-indigo-300 hover:underline flex items-center space-x-1 truncate max-w-[190px]"
                                                            :title="project.domain"
                                                        >
                                                            <span class="truncate">{{ project.domain }}</span>
                                                            <ExternalLink class="w-3 h-3 flex-shrink-0 text-slate-500" />
                                                        </a>
                                                        <span
                                                            v-if="project.target_country"
                                                            class="text-[9px] px-1.5 py-0.2 rounded bg-slate-800 text-slate-400 font-mono uppercase"
                                                        >
                                                            {{ project.target_country }}
                                                        </span>
                                                    </div>
                                                    <div class="text-[11px] text-slate-400 truncate max-w-[200px]">
                                                        {{ project.name }}
                                                        <span class="text-slate-600">&bull;</span>
                                                        <span class="text-slate-500 text-[10px]">{{ project.workspaceName }}</span>
                                                    </div>
                                                </div>

                                                <div class="flex items-center space-x-2 flex-shrink-0 text-right">
                                                    <!-- Health Score -->
                                                    <div v-if="project.latest_crawl?.health_score !== undefined && project.latest_crawl?.health_score !== null">
                                                        <span
                                                            class="px-2 py-0.5 rounded-full text-[10px] font-bold font-mono"
                                                            :class="project.latest_crawl.health_score >= 80 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : (project.latest_crawl.health_score >= 60 ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20')"
                                                        >
                                                            %{{ project.latest_crawl.health_score }}
                                                        </span>
                                                    </div>

                                                    <!-- Crawls Count -->
                                                    <span class="text-[10px] text-slate-500 font-mono">
                                                        {{ $t('admin.crawls_count', { count: project.crawls_count || 0 }) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else class="text-xs text-slate-500 italic flex items-center space-x-1.5 py-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-600"></span>
                                            <span>{{ $t('admin.no_sites_tracked') }}</span>
                                        </div>
                                    </td>

                                    <!-- Actions Column -->
                                    <td class="p-4 align-top text-right">
                                        <button
                                            @click="selectedUser = user"
                                            class="px-2.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700/80 font-medium text-xs flex items-center space-x-1 transition-all ml-auto"
                                        >
                                            <Eye class="w-3.5 h-3.5 text-indigo-400" />
                                            <span>{{ $t('admin.inspect_user') }}</span>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Users Pagination -->
                    <div
                        v-if="users.links && users.links.length > 3"
                        class="flex items-center justify-center space-x-1 p-4 border-t border-slate-800 bg-slate-950/40"
                    >
                        <Component
                            :is="link.url ? Link : 'span'"
                            v-for="(link, idx) in users.links"
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

            <!-- Tab 2: System Infrastructure -->
            <div v-else-if="activeTab === 'system'" class="space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="p-5 rounded-3xl bg-slate-900/60 border border-slate-800/80 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">{{ $t('admin.database') }}</span>
                            <span class="text-sm font-bold text-white mt-1 block">
                                {{ stats.db_status ? $t('admin.status_ok') : $t('admin.status_err') }}
                            </span>
                        </div>
                        <div
                            class="w-10 h-10 rounded-xl flex items-center justify-center"
                            :class="stats.db_status ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400'"
                        >
                            <CheckCircle2 v-if="stats.db_status" class="w-5 h-5" />
                            <XCircle v-else class="w-5 h-5" />
                        </div>
                    </div>

                    <div class="p-5 rounded-3xl bg-slate-900/60 border border-slate-800/80 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">{{ $t('admin.redis') }}</span>
                            <span class="text-sm font-bold text-white mt-1 block">
                                {{ stats.redis_status ? $t('admin.status_ok') : $t('admin.status_err') }}
                            </span>
                        </div>
                        <div
                            class="w-10 h-10 rounded-xl flex items-center justify-center"
                            :class="stats.redis_status ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400'"
                        >
                            <CheckCircle2 v-if="stats.redis_status" class="w-5 h-5" />
                            <XCircle v-else class="w-5 h-5" />
                        </div>
                    </div>

                    <div class="p-5 rounded-3xl bg-slate-900/60 border border-slate-800/80 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">{{ $t('admin.failed_jobs') }}</span>
                            <span class="text-sm font-bold text-white mt-1 block">{{ stats.failed_jobs }}</span>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center">
                            <AlertOctagon class="w-5 h-5" />
                        </div>
                    </div>

                    <div class="p-5 rounded-3xl bg-slate-900/60 border border-slate-800/80 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">PHP / Laravel</span>
                            <span class="text-sm font-bold text-white mt-1 block font-mono">PHP {{ stats.php_version }}</span>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center">
                            <Server class="w-5 h-5" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Audit Logs (Human Readable with Categories & Details) -->
            <div v-else-if="activeTab === 'logs'" class="space-y-4">
                <!-- Log Category Filter Pills -->
                <div class="flex items-center flex-wrap gap-1.5 text-xs bg-slate-900/40 p-3 rounded-2xl border border-slate-800/80">
                    <button
                        @click="logCategoryFilter = 'all'"
                        class="px-3 py-1.5 rounded-lg border transition-all"
                        :class="logCategoryFilter === 'all' ? 'bg-indigo-600 text-white border-indigo-500 font-semibold shadow-sm' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-white'"
                    >
                        {{ $t('admin.filter_all_logs') }} ({{ recentLogs.length }})
                    </button>
                    <button
                        @click="logCategoryFilter = 'auth'"
                        class="px-3 py-1.5 rounded-lg border transition-all flex items-center space-x-1.5"
                        :class="logCategoryFilter === 'auth' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40 font-semibold' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-emerald-400'"
                    >
                        <Shield class="w-3.5 h-3.5" />
                        <span>{{ $t('admin.filter_auth_logs') }}</span>
                    </button>
                    <button
                        @click="logCategoryFilter = 'project'"
                        class="px-3 py-1.5 rounded-lg border transition-all flex items-center space-x-1.5"
                        :class="logCategoryFilter === 'project' ? 'bg-cyan-500/20 text-cyan-300 border-cyan-500/40 font-semibold' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-cyan-400'"
                    >
                        <Globe class="w-3.5 h-3.5" />
                        <span>{{ $t('admin.filter_project_logs') }}</span>
                    </button>
                    <button
                        @click="logCategoryFilter = 'crawl'"
                        class="px-3 py-1.5 rounded-lg border transition-all flex items-center space-x-1.5"
                        :class="logCategoryFilter === 'crawl' ? 'bg-indigo-500/20 text-indigo-300 border-indigo-500/40 font-semibold' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-indigo-400'"
                    >
                        <Activity class="w-3.5 h-3.5" />
                        <span>{{ $t('admin.filter_crawl_logs') }}</span>
                    </button>
                    <button
                        @click="logCategoryFilter = 'workspace'"
                        class="px-3 py-1.5 rounded-lg border transition-all flex items-center space-x-1.5"
                        :class="logCategoryFilter === 'workspace' ? 'bg-violet-500/20 text-violet-300 border-violet-500/40 font-semibold' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-violet-400'"
                    >
                        <Briefcase class="w-3.5 h-3.5" />
                        <span>{{ $t('admin.filter_workspace_logs') }}</span>
                    </button>
                </div>

                <!-- Logs Table -->
                <div class="rounded-3xl bg-slate-900/50 border border-slate-800/80 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-950/70 text-slate-400 border-b border-slate-800 font-semibold uppercase tracking-wider text-[11px]">
                                <tr>
                                    <th class="p-4 w-44">{{ $t('common.date') }}</th>
                                    <th class="p-4 w-48">{{ $t('members.user_th') }}</th>
                                    <th class="p-4 w-72">{{ $t('common.action') }}</th>
                                    <th class="p-4 w-40">{{ $t('admin.col_workspace') }}</th>
                                    <th class="p-4 min-w-[240px]">{{ $t('common.details') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                <tr v-if="filteredAuditLogs.length === 0">
                                    <td colspan="5" class="p-12 text-center text-slate-500">
                                        <Clock class="w-8 h-8 mx-auto mb-2 text-slate-600" />
                                        <div>{{ $t('admin.no_logs_found') }}</div>
                                    </td>
                                </tr>
                                <tr
                                    v-for="log in filteredAuditLogs"
                                    :key="log.id"
                                    class="hover:bg-slate-800/30 transition-colors group"
                                >
                                    <!-- Date -->
                                    <td class="p-4 text-slate-400 font-mono align-top whitespace-nowrap">
                                        <div class="text-white font-medium">{{ new Date(log.created_at).toLocaleDateString() }}</div>
                                        <div class="text-[10px] text-slate-500">{{ new Date(log.created_at).toLocaleTimeString() }}</div>
                                    </td>

                                    <!-- User -->
                                    <td class="p-4 align-top">
                                        <div class="font-bold text-white text-xs flex items-center space-x-1.5">
                                            <div class="w-5 h-5 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center text-[10px] font-bold text-indigo-400 shrink-0">
                                                {{ log.user?.name ? log.user.name.charAt(0).toUpperCase() : 'S' }}
                                            </div>
                                            <span class="truncate">{{ log.user?.name || 'Sistem / Otomatik' }}</span>
                                        </div>
                                        <div v-if="log.user?.email" class="text-[10px] text-slate-500 truncate mt-0.5 ml-6.5">
                                            {{ log.user.email }}
                                        </div>
                                        <div v-if="log.ip_address" class="text-[9px] font-mono text-slate-600 mt-1 ml-6.5">
                                            IP: {{ log.ip_address }}
                                        </div>
                                    </td>

                                    <!-- Action (Human-Readable + Badge + Icon) -->
                                    <td class="p-4 align-top">
                                        <div class="space-y-1">
                                            <div class="flex items-center space-x-2">
                                                <!-- Category Badge -->
                                                <span
                                                    class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-md text-[10px] font-bold"
                                                    :class="getActionMeta(log.action).color"
                                                >
                                                    <component :is="getActionMeta(log.action).icon" class="w-3 h-3 flex-shrink-0" />
                                                    <span>{{ getActionMeta(log.action).badgeText }}</span>
                                                </span>
                                            </div>

                                            <!-- Clear Descriptive Title -->
                                            <div class="font-semibold text-white text-xs leading-snug">
                                                {{ getActionMeta(log.action).label }}
                                            </div>

                                            <!-- Technical Slug -->
                                            <div class="text-[10px] font-mono text-slate-500 truncate">
                                                {{ log.action }}
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Workspace -->
                                    <td class="p-4 align-top text-xs text-slate-300">
                                        <div v-if="log.workspace" class="flex items-center space-x-1 font-medium truncate" :title="log.workspace.name">
                                            <Briefcase class="w-3 h-3 text-emerald-400 flex-shrink-0" />
                                            <span class="truncate">{{ log.workspace.name }}</span>
                                        </div>
                                        <span v-else class="text-slate-500 italic text-[11px]">Genel Sistem</span>
                                    </td>

                                    <!-- Details (Formatted Badges & Values) -->
                                    <td class="p-4 align-top">
                                        <div v-if="formatLogDetails(log).length > 0" class="flex flex-wrap gap-1.5 items-center">
                                            <div
                                                v-for="(item, idx) in formatLogDetails(log)"
                                                :key="idx"
                                                class="inline-flex items-center space-x-1 px-2 py-1 rounded-lg bg-slate-950 border border-slate-800 text-[11px]"
                                            >
                                                <span class="text-slate-500 font-medium">{{ item.label }}:</span>
                                                <span class="text-slate-200 font-mono font-semibold truncate max-w-[200px]" :title="item.value">
                                                    {{ item.value }}
                                                </span>
                                            </div>

                                            <button
                                                v-if="log.details || log.meta"
                                                @click="selectedLog = log"
                                                class="px-2 py-0.5 rounded text-[10px] text-slate-500 hover:text-white bg-slate-800 hover:bg-slate-700 transition-colors"
                                                title="Ham Veri"
                                            >
                                                JSON
                                            </button>
                                        </div>

                                        <div v-else-if="log.resource_type" class="text-[11px] text-slate-400 font-mono">
                                            {{ log.resource_type }} #{{ log.resource_id }}
                                        </div>

                                        <span v-else class="text-slate-600 text-xs italic">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Raw Log Details Modal -->
        <div
            v-if="selectedLog"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm transition-all"
            @click.self="selectedLog = null"
        >
            <div class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl">
                <div class="p-5 border-b border-slate-800 flex items-center justify-between">
                    <div>
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Denetim Günlüğü Ham Verisi</div>
                        <h4 class="font-bold text-white text-sm mt-0.5">{{ getActionMeta(selectedLog.action).label }}</h4>
                    </div>
                    <button @click="selectedLog = null" class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white">
                        <X class="w-4 h-4" />
                    </button>
                </div>
                <div class="p-5 space-y-4 max-h-[60vh] overflow-y-auto">
                    <pre class="text-xs font-mono text-slate-300 bg-slate-950 p-4 rounded-xl border border-slate-800 overflow-x-auto whitespace-pre-wrap">{{ JSON.stringify(selectedLog.details || selectedLog.meta || {}, null, 2) }}</pre>
                </div>
                <div class="p-4 border-t border-slate-800 bg-slate-950/40 flex justify-end">
                    <button @click="selectedLog = null" class="px-4 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold">
                        {{ $t('common.close') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- User & Sites Inspection Modal -->
        <div
            v-if="selectedUser"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm transition-all"
            @click.self="selectedUser = null"
        >
            <div class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-2xl overflow-hidden shadow-2xl animate-in fade-in zoom-in-95 duration-200">
                <!-- Modal Header -->
                <div class="p-6 border-b border-slate-800 flex items-start justify-between gap-4">
                    <div class="flex items-center space-x-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-600 flex items-center justify-center text-lg font-bold text-white shrink-0 shadow-lg shadow-indigo-600/30">
                            {{ selectedUser.name ? selectedUser.name.charAt(0).toUpperCase() : 'U' }}
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <h3 class="text-lg font-bold text-white">{{ selectedUser.name }}</h3>
                                <span
                                    v-if="selectedUser.is_platform_admin"
                                    class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-pink-500/10 text-pink-400 border border-pink-500/20"
                                >
                                    Platform Admin
                                </span>
                            </div>
                            <div class="text-xs text-slate-400 flex items-center space-x-2 mt-0.5">
                                <span>{{ selectedUser.email }}</span>
                                <span>&bull;</span>
                                <span>ID: #{{ selectedUser.id }}</span>
                                <span>&bull;</span>
                                <span>{{ new Date(selectedUser.created_at).toLocaleDateString() }}</span>
                            </div>
                        </div>
                    </div>
                    <button
                        @click="selectedUser = null"
                        class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white transition-colors"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
                    <!-- Active Workspace Card -->
                    <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800/80 space-y-2">
                        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">
                            {{ $t('admin.col_active_workspace') }}
                        </span>
                        <div v-if="selectedUser.current_workspace" class="flex items-center justify-between">
                            <div>
                                <div class="font-bold text-white text-sm">{{ selectedUser.current_workspace.name }}</div>
                                <div class="text-xs text-slate-500 font-mono mt-0.5">slug: {{ selectedUser.current_workspace.slug }}</div>
                            </div>
                            <span
                                class="px-2.5 py-1 rounded-lg text-xs font-semibold"
                                :class="selectedUser.current_workspace.owner_id === selectedUser.id ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-800 text-slate-300'"
                            >
                                {{ selectedUser.current_workspace.owner_id === selectedUser.id ? $t('admin.owner_badge') : $t('admin.member_badge') }}
                            </span>
                        </div>
                        <div v-else class="text-xs text-slate-500 italic">
                            {{ $t('admin.no_active_workspace') }}
                        </div>
                    </div>

                    <!-- Workspaces Breakdown -->
                    <div class="space-y-2">
                        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">
                            {{ $t('admin.col_all_workspaces') }} ({{ selectedUser.workspaces?.length || 0 }})
                        </span>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div
                                v-for="ws in selectedUser.workspaces"
                                :key="ws.id"
                                class="p-3 rounded-xl bg-slate-950/60 border border-slate-800 flex items-center justify-between"
                            >
                                <div class="min-w-0">
                                    <div class="font-semibold text-xs text-white truncate">{{ ws.name }}</div>
                                    <div class="text-[10px] text-slate-500 capitalize">{{ ws.pivot?.role || 'member' }}</div>
                                </div>
                                <span class="text-[10px] font-mono text-slate-400">
                                    {{ ws.projects?.length || 0 }} {{ $t('projects.title') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Monitored Websites Breakdown -->
                    <div class="space-y-2">
                        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">
                            {{ $t('admin.col_monitored_sites') }} ({{ getUserProjects(selectedUser).length }})
                        </span>

                        <div v-if="getUserProjects(selectedUser).length > 0" class="space-y-2.5">
                            <div
                                v-for="proj in getUserProjects(selectedUser)"
                                :key="proj.id"
                                class="p-4 rounded-2xl bg-slate-950 border border-slate-800/90 space-y-2"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <a
                                            :href="proj.start_url || `https://${proj.domain}`"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="font-mono text-sm font-bold text-indigo-400 hover:text-indigo-300 hover:underline flex items-center space-x-1.5"
                                        >
                                            <span>{{ proj.domain }}</span>
                                            <ExternalLink class="w-3.5 h-3.5 text-slate-500" />
                                        </a>
                                        <div class="text-xs text-slate-300 font-medium mt-0.5">{{ proj.name }}</div>
                                    </div>

                                    <div class="text-right flex flex-col items-end space-y-1">
                                        <span
                                            v-if="proj.latest_crawl?.health_score !== undefined && proj.latest_crawl?.health_score !== null"
                                            class="px-2.5 py-0.5 rounded-full text-xs font-bold font-mono"
                                            :class="proj.latest_crawl.health_score >= 80 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : (proj.latest_crawl.health_score >= 60 ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20')"
                                        >
                                            {{ $t('admin.health') }}: %{{ proj.latest_crawl.health_score }}
                                        </span>
                                        <span class="text-[11px] text-slate-500 font-mono">
                                            {{ $t('admin.crawls_count', { count: proj.crawls_count || 0 }) }}
                                        </span>
                                    </div>
                                </div>

                                <div class="pt-2 border-t border-slate-900 flex items-center justify-between text-[11px] text-slate-400">
                                    <span class="truncate">{{ proj.workspaceName }}</span>
                                    <span class="font-mono text-slate-500">
                                        {{ proj.target_country }} &bull; {{ proj.target_language }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="p-6 text-center text-slate-500 text-xs italic bg-slate-950/40 rounded-2xl border border-slate-800/60">
                            {{ $t('admin.no_sites_tracked') }}
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-4 sm:p-6 border-t border-slate-800 bg-slate-950/40 flex items-center justify-end">
                    <button
                        type="button"
                        @click="selectedUser = null"
                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition-colors"
                    >
                        {{ $t('common.close') }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
