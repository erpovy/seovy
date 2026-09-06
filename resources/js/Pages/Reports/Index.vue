<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    FileSpreadsheet,
    FileText,
    Download,
    Share2,
    ArrowLeft,
    Clock,
    Check,
    Copy
} from 'lucide-vue-next';

const props = defineProps<{
    project: any;
    reports: {
        data: Array<any>;
        links: Array<any>;
        total: number;
    };
}>();

const copiedToken = ref<string | null>(null);

const copyShareLink = (token: string) => {
    const link = `${window.location.origin}/reports/shared/${token}`;
    navigator.clipboard.writeText(link);
    copiedToken.value = token;
    setTimeout(() => {
        copiedToken.value = null;
    }, 2500);
};
</script>

<template>
    <AppLayout :title="`Raporlar - ${project.name}`">
        <Head :title="`Raporlar - ${project.name}`" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center space-x-3">
                <Link :href="`/projects/${project.id}`" class="p-2 rounded-xl bg-slate-900 border border-slate-800 text-slate-400 hover:text-white transition-colors">
                    <ArrowLeft class="w-4 h-4" />
                </Link>
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-white">Denetim Raporları</h1>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Üretilen PDF audit raporları, CSV dışa aktarımları ve güvenli müşteri paylaşım bağlantıları.
                    </p>
                </div>
            </div>

            <div v-if="reports.data.length === 0" class="p-12 rounded-3xl bg-slate-900/40 border border-slate-800 text-center text-xs text-slate-500">
                <FileText class="w-12 h-12 text-slate-600 mx-auto mb-3" />
                <h3 class="text-sm font-semibold text-white">Henüz üretilmiş bir rapor yok</h3>
                <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1 mb-4">
                    Herhangi bir tarama detay ekranından tek tıkla PDF veya CSV raporu oluşturabilirsiniz.
                </p>
                <Link
                    :href="`/projects/${project.id}`"
                    class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs inline-block"
                >
                    Taramalara Git
                </Link>
            </div>

            <div v-else class="rounded-3xl bg-slate-900/50 border border-slate-800/80 overflow-hidden">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="p-4">Rapor Başlığı</th>
                            <th class="p-4">Format</th>
                            <th class="p-4">Oluşturulma</th>
                            <th class="p-4">Paylaşım Bağlantısı</th>
                            <th class="p-4 text-right">İndir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <tr v-for="rep in reports.data" :key="rep.id" class="hover:bg-slate-800/30 transition-colors">
                            <td class="p-4 font-bold text-white flex items-center space-x-2">
                                <FileText class="w-4 h-4 text-indigo-400 shrink-0" />
                                <span>{{ rep.title }}</span>
                            </td>
                            <td class="p-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-800 text-slate-300">
                                    {{ rep.format }}
                                </span>
                            </td>
                            <td class="p-4 text-slate-400">{{ new Date(rep.created_at).toLocaleString('tr-TR') }}</td>
                            <td class="p-4">
                                <button
                                    v-if="rep.public_token"
                                    @click="copyShareLink(rep.public_token)"
                                    class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 flex items-center space-x-1.5 transition-colors"
                                >
                                    <Check v-if="copiedToken === rep.public_token" class="w-3 h-3 text-emerald-400" />
                                    <Copy v-else class="w-3 h-3 text-slate-400" />
                                    <span>{{ copiedToken === rep.public_token ? 'Kopyalandı!' : 'Linki Kopyala' }}</span>
                                </button>
                                <span v-else class="text-slate-500">Paylaşım yok</span>
                            </td>
                            <td class="p-4 text-right">
                                <a
                                    :href="`/projects/${project.id}/reports/${rep.id}/download`"
                                    class="p-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white inline-flex items-center space-x-1 transition-all"
                                >
                                    <Download class="w-3.5 h-3.5" />
                                    <span>İndir</span>
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
