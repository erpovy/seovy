<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
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
    Server
} from 'lucide-vue-next';

defineProps<{
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
}>();
</script>

<template>
    <AppLayout :title="$t('admin.page_title')">
        <Head :title="$t('admin.page_title')" />

        <div class="space-y-8">
            <div>
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-pink-500/10 text-pink-400 border border-pink-500/20 text-xs font-semibold mb-2">
                    <Shield class="w-3.5 h-3.5" />
                    <span>{{ $t('admin.badge') }}</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white">{{ $t('admin.title') }}</h1>
                <p class="text-sm text-slate-400 mt-1">
                    {{ $t('admin.subtitle') }}
                </p>
            </div>

            <!-- Health Status Indicators -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 flex items-center justify-between">
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

                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 flex items-center justify-between">
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

                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">{{ $t('admin.failed_jobs') }}</span>
                        <span class="text-sm font-bold text-white mt-1 block">{{ stats.failed_jobs }}</span>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center">
                        <AlertOctagon class="w-5 h-5" />
                    </div>
                </div>

                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">{{ $t('admin.crawls_total') }}</span>
                        <span class="text-sm font-bold text-white mt-1 block">{{ stats.total_crawls }}</span>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center">
                        <Activity class="w-5 h-5" />
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <div class="p-6 rounded-3xl bg-slate-900/50 border border-slate-800/80 space-y-2">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ $t('admin.users_total') }}</span>
                    <div class="text-3xl font-extrabold text-white">{{ stats.total_users }}</div>
                </div>

                <div class="p-6 rounded-3xl bg-slate-900/50 border border-slate-800/80 space-y-2">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ $t('admin.workspaces_total') }}</span>
                    <div class="text-3xl font-extrabold text-white">{{ stats.total_workspaces }}</div>
                </div>

                <div class="p-6 rounded-3xl bg-slate-900/50 border border-slate-800/80 space-y-2">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ $t('admin.projects_total') }}</span>
                    <div class="text-3xl font-extrabold text-white">{{ stats.total_projects }}</div>
                </div>
            </div>

            <!-- Audit Logs -->
            <div class="space-y-4">
                <h2 class="text-base font-bold text-white flex items-center space-x-2">
                    <Clock class="w-4 h-4 text-slate-400" />
                    <span>{{ $t('admin.recent_logs') }}</span>
                </h2>

                <div class="rounded-3xl bg-slate-900/50 border border-slate-800/80 overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                            <tr>
                                <th class="p-4">{{ $t('common.date') }}</th>
                                <th class="p-4">{{ $t('members.user_th') }}</th>
                                <th class="p-4">{{ $t('common.action') }}</th>
                                <th class="p-4">{{ $t('common.details') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <tr v-for="log in recentLogs" :key="log.id" class="hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 text-slate-400 font-mono">{{ new Date(log.created_at).toLocaleString() }}</td>
                                <td class="p-4 font-bold text-white">{{ log.user?.name || 'System' }}</td>
                                <td class="p-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-800 text-slate-300">
                                        {{ log.action }}
                                    </span>
                                </td>
                                <td class="p-4 text-slate-400 font-mono text-[11px] truncate max-w-xs">{{ JSON.stringify(log.meta) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
