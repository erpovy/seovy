<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Globe,
    Play,
    Activity,
    ShieldCheck,
    AlertCircle,
    Search,
    FileText,
    TrendingUp,
    CheckSquare,
    FileSpreadsheet,
    Zap,
    ExternalLink,
    Clock,
    ChevronRight,
    Settings,
    Download
} from 'lucide-vue-next';

const props = defineProps<{
    project: any;
    crawls: Array<any>;
    latestCrawl: any;
    findingsSummary: any;
    canManage: boolean;
}>();

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
                                <span>Doğrulandı</span>
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
                    <form @submit.prevent="startCrawl">
                        <button
                            type="submit"
                            :disabled="crawlForm.processing"
                            class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-lg shadow-indigo-600/30 flex items-center space-x-2 transition-all disabled:opacity-50"
                        >
                            <Play class="w-4 h-4 fill-white" />
                            <span>Tarama Başlat</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Ownership Verification Box (if not verified) -->
            <div v-if="!project.ownership_verified_at" class="p-5 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-2">
                    <strong class="text-amber-300 font-semibold text-sm block">Site Sahipliği Henüz Doğrulanmadı</strong>
                    <div class="text-slate-400">
                        <span>WordPress kullanıyorsanız hazır eklentiyi indirip sitenize yükleyin veya sitenizin &lt;head&gt; bölümüne meta etiketini ekleyin:</span>
                        <div class="mt-1.5">
                            <code class="bg-slate-950 px-2 py-1 rounded text-amber-300 select-all border border-slate-800 inline-block">&lt;meta name="seovy-verification" content="{{ project.verification_token }}"&gt;</code>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-2 shrink-0">
                    <a
                        :href="`/projects/${project.id}/wordpress-plugin`"
                        class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs transition-all flex items-center space-x-1.5 border border-slate-700"
                    >
                        <Download class="w-3.5 h-3.5 text-indigo-400" />
                        <span>WordPress Eklentisini İndir (.zip)</span>
                    </a>
                    <button
                        @click="verifyOwnership"
                        :disabled="verifyForm.processing"
                        class="px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs transition-all flex items-center space-x-2 disabled:opacity-50"
                    >
                        <span v-if="verifyForm.processing">Doğrulanıyor...</span>
                        <span v-else>Şimdi Doğrula</span>
                    </button>
                </div>
            </div>

            <!-- Module Navigation Bar -->
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                <Link
                    v-if="latestCrawl"
                    :href="`/projects/${project.id}/crawls/${latestCrawl.id}`"
                    class="p-4 rounded-2xl bg-slate-900/40 border border-slate-800/80 hover:border-slate-700 text-center transition-all group"
                >
                    <Search class="w-5 h-5 text-violet-400 mx-auto mb-1.5 group-hover:scale-110 transition-transform" />
                    <span class="text-xs font-semibold text-white block">Teknik Tarama</span>
                    <span class="text-[10px] text-slate-500">{{ latestCrawl.pages_crawled }} sayfa</span>
                </Link>

                <Link
                    :href="`/projects/${project.id}/on-page`"
                    class="p-4 rounded-2xl bg-slate-900/40 border border-slate-800/80 hover:border-slate-700 text-center transition-all group"
                >
                    <FileText class="w-5 h-5 text-cyan-400 mx-auto mb-1.5 group-hover:scale-110 transition-transform" />
                    <span class="text-xs font-semibold text-white block">Sayfa İçi & SERP</span>
                    <span class="text-[10px] text-slate-500">Önizleme & Kelime</span>
                </Link>

                <Link
                    :href="`/projects/${project.id}/tasks`"
                    class="p-4 rounded-2xl bg-slate-900/40 border border-slate-800/80 hover:border-slate-700 text-center transition-all group"
                >
                    <CheckSquare class="w-5 h-5 text-amber-400 mx-auto mb-1.5 group-hover:scale-110 transition-transform" />
                    <span class="text-xs font-semibold text-white block">SEO Görevleri</span>
                    <span class="text-[10px] text-slate-500">Eylem Planı</span>
                </Link>

                <Link
                    :href="`/projects/${project.id}/keywords`"
                    class="p-4 rounded-2xl bg-slate-900/40 border border-slate-800/80 hover:border-slate-700 text-center transition-all group"
                >
                    <TrendingUp class="w-5 h-5 text-emerald-400 mx-auto mb-1.5 group-hover:scale-110 transition-transform" />
                    <span class="text-xs font-semibold text-white block">Sıra & Kelimeler</span>
                    <span class="text-[10px] text-slate-500">Pozisyon Takibi</span>
                </Link>

                <Link
                    :href="`/projects/${project.id}/reports`"
                    class="p-4 rounded-2xl bg-slate-900/40 border border-slate-800/80 hover:border-slate-700 text-center transition-all group"
                >
                    <FileSpreadsheet class="w-5 h-5 text-pink-400 mx-auto mb-1.5 group-hover:scale-110 transition-transform" />
                    <span class="text-xs font-semibold text-white block">Raporlar & Dışa Aktar</span>
                    <span class="text-[10px] text-slate-500">PDF / CSV</span>
                </Link>
            </div>

            <!-- Latest Crawl Deep Summary -->
            <div v-if="latestCrawl" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Health Score Dial Card -->
                    <div class="p-6 rounded-3xl bg-slate-900/50 border border-slate-800/80 flex flex-col justify-between">
                        <div>
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Site Sağlık Skoru</span>
                            <div class="mt-4 flex items-center space-x-4">
                                <div
                                    class="w-20 h-20 rounded-2xl flex items-center justify-center font-extrabold text-3xl shadow-xl"
                                    :class="latestCrawl.health_score >= 80 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : (latestCrawl.health_score >= 50 ? 'bg-amber-500/10 text-amber-400 border border-amber-500/30' : 'bg-rose-500/10 text-rose-400 border border-rose-500/30')"
                                >
                                    {{ latestCrawl.health_score }}%
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-white">
                                        {{ latestCrawl.health_score >= 80 ? 'Harika Performans' : 'Kritik Düzeltmeler Gerekli' }}
                                    </div>
                                    <div class="text-xs text-slate-400 mt-0.5">
                                        {{ latestCrawl.pages_crawled }} sayfa analiz edildi.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t border-slate-800 text-xs text-slate-500">
                            Sağlık skoru 25+ teknik SEO kuralı ve ceza puanı formülüyle hesaplanmıştır.
                        </div>
                    </div>

                    <!-- Issues Breakdown (Severity) -->
                    <div class="p-6 rounded-3xl bg-slate-900/50 border border-slate-800/80 lg:col-span-2">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4 block">Tespit Edilen Sorun Dağılımı</span>

                        <div class="grid grid-cols-3 gap-4">
                            <div class="p-4 rounded-2xl bg-rose-500/5 border border-rose-500/20 text-center">
                                <span class="text-2xl font-extrabold text-rose-400">{{ findingsSummary.critical ?? 0 }}</span>
                                <span class="block text-xs font-semibold text-rose-300 mt-1">Kritik Hatalar</span>
                            </div>

                            <div class="p-4 rounded-2xl bg-amber-500/5 border border-amber-500/20 text-center">
                                <span class="text-2xl font-extrabold text-amber-400">{{ findingsSummary.warning ?? 0 }}</span>
                                <span class="block text-xs font-semibold text-amber-300 mt-1">Uyarılar</span>
                            </div>

                            <div class="p-4 rounded-2xl bg-indigo-500/5 border border-indigo-500/20 text-center">
                                <span class="text-2xl font-extrabold text-indigo-400">{{ findingsSummary.notice ?? 0 }}</span>
                                <span class="block text-xs font-semibold text-indigo-300 mt-1">Öneriler</span>
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

            <!-- Crawl History Table -->
            <div class="space-y-4">
                <h2 class="text-lg font-bold text-white flex items-center space-x-2">
                    <Clock class="w-5 h-5 text-slate-400" />
                    <span>Geçmiş Taramalar</span>
                </h2>

                <div v-if="crawls.length === 0" class="p-8 rounded-3xl bg-slate-900/40 border border-slate-800 text-center text-xs text-slate-500">
                    Henüz hiçbir tarama yapılmadı. Yukarıdaki butondan ilk taramayı başlatabilirsiniz.
                </div>

                <div v-else class="rounded-3xl bg-slate-900/50 border border-slate-800/80 overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                            <tr>
                                <th class="p-4">Tarama ID</th>
                                <th class="p-4">Durum</th>
                                <th class="p-4">Taranan Sayfa</th>
                                <th class="p-4">Sağlık Skoru</th>
                                <th class="p-4">Süre</th>
                                <th class="p-4">Tarih</th>
                                <th class="p-4 text-right">Eylem</th>
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
                                <td class="p-4 text-slate-400">{{ crawl.duration_seconds }} sn</td>
                                <td class="p-4 text-slate-400">{{ new Date(crawl.created_at).toLocaleString('tr-TR') }}</td>
                                <td class="p-4 text-right">
                                    <Link
                                        :href="`/projects/${project.id}/crawls/${crawl.id}`"
                                        class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-indigo-400 font-semibold text-xs transition-colors"
                                    >
                                        Detay
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
