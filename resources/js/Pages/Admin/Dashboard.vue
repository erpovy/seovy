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
    <AppLayout title="Platform Yönetimi">
        <Head title="Platform Yönetimi" />

        <div class="space-y-8">
            <div>
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-pink-500/10 text-pink-400 border border-pink-500/20 text-xs font-semibold mb-2">
                    <Shield class="w-3.5 h-3.5" />
                    <span>Sistem Yöneticisi (Platform Admin)</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white">Platform Genel Durumu</h1>
                <p class="text-sm text-slate-400 mt-1">
                    Sistem altyapı sağlığı, sunucu bileşenleri ve kullanıcı denetim günlüğü.
                </p>
            </div>

            <!-- Health Status Indicators -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Veritabanı</span>
                        <span class="text-sm font-bold text-white mt-1 block">
                            {{ stats.db_status ? 'Bağlantı Başarılı' : 'Bağlantı Hatası' }}
                        </span>
                    </div>
                    <div
                        class="w-10 h-10 rounded-xl flex items-center justify-center"
                        :class="stats.db_status ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400'"
                    >
                        <Database class="w-5 h-5" />
                    </div>
                </div>

                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Redis / Önbellek</span>
                        <span class="text-sm font-bold text-white mt-1 block">
                            {{ stats.redis_status ? 'Çalışıyor' : 'Bağlantı Yok' }}
                        </span>
                    </div>
                    <div
                        class="w-10 h-10 rounded-xl flex items-center justify-center"
                        :class="stats.redis_status ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400'"
                    >
                        <Server class="w-5 h-5" />
                    </div>
                </div>

                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Kuyruk Hataları</span>
                        <span class="text-sm font-bold mt-1 block" :class="stats.failed_jobs > 0 ? 'text-rose-400' : 'text-emerald-400'">
                            {{ stats.failed_jobs }} Başarısız İş
                        </span>
                    </div>
                    <div
                        class="w-10 h-10 rounded-xl flex items-center justify-center"
                        :class="stats.failed_jobs > 0 ? 'bg-rose-500/10 text-rose-400' : 'bg-emerald-500/10 text-emerald-400'"
                    >
                        <AlertOctagon class="w-5 h-5" />
                    </div>
                </div>

                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Çekirdek Sürümleri</span>
                        <span class="text-xs font-mono text-slate-300 mt-1 block">
                            PHP {{ stats.php_version }} / L{{ stats.laravel_version }}
                        </span>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center">
                        <Activity class="w-5 h-5" />
                    </div>
                </div>
            </div>

            <!-- Platform Metrics Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="p-5 rounded-2xl bg-slate-900/40 border border-slate-800">
                    <span class="text-xs text-slate-400">Toplam Kullanıcı</span>
                    <span class="text-2xl font-extrabold text-white block mt-1">{{ stats.total_users }}</span>
                </div>
                <div class="p-5 rounded-2xl bg-slate-900/40 border border-slate-800">
                    <span class="text-xs text-slate-400">Toplam Çalışma Alanı</span>
                    <span class="text-2xl font-extrabold text-white block mt-1">{{ stats.total_workspaces }}</span>
                </div>
                <div class="p-5 rounded-2xl bg-slate-900/40 border border-slate-800">
                    <span class="text-xs text-slate-400">Toplam Proje</span>
                    <span class="text-2xl font-extrabold text-white block mt-1">{{ stats.total_projects }}</span>
                </div>
                <div class="p-5 rounded-2xl bg-slate-900/40 border border-slate-800">
                    <span class="text-xs text-slate-400">Toplam Tarama</span>
                    <span class="text-2xl font-extrabold text-white block mt-1">{{ stats.total_crawls }}</span>
                </div>
            </div>

            <!-- Recent Audit Logs Table -->
            <div class="space-y-4">
                <h2 class="text-lg font-bold text-white flex items-center space-x-2">
                    <Clock class="w-5 h-5 text-slate-400" />
                    <span>Güvenlik & Denetim Günlüğü (Audit Trail)</span>
                </h2>

                <div class="rounded-3xl bg-slate-900/50 border border-slate-800/80 overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                            <tr>
                                <th class="p-4">Olay (Action)</th>
                                <th class="p-4">Kullanıcı</th>
                                <th class="p-4">IP Adresi</th>
                                <th class="p-4">Tarih</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <tr v-for="log in recentLogs" :key="log.id" class="hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 font-mono font-bold text-indigo-400">{{ log.action }}</td>
                                <td class="p-4 text-white font-medium">{{ log.user?.name ?? 'Sistem' }}</td>
                                <td class="p-4 font-mono text-slate-400">{{ log.ip_address || '—' }}</td>
                                <td class="p-4 text-slate-400">{{ new Date(log.created_at).toLocaleString('tr-TR') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
