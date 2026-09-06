<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Sparkles,
    Bot,
    CheckCircle2,
    XCircle,
    AlertTriangle,
    FileCode,
    FileText,
    Copy,
    ArrowLeft,
    Check,
    Cpu,
    Network,
    ExternalLink,
    Download,
    ShieldCheck,
    Layers,
    Plus
} from 'lucide-vue-next';

const props = defineProps<{
    project: any;
    aiScore: number;
    aiBots: Record<string, { name: string; company: string; status: string }>;
    llmsTxtFound: boolean;
    llmsFullTxtFound: boolean;
    llmsContent: string;
    schemaTypesFound: Record<string, number>;
    hasFaqSchema: boolean;
    hasOrgSchema: boolean;
    totalPagesSampled: number;
    pagesWithGoodWordCount: number;
    sampleLlmsTxt: string;
    robotsFound?: boolean;
    originalRobotsTxt?: string;
    aiRobotsBlock?: string;
    mergedRobotsTxt?: string;
}>();

const copied = ref(false);
const copiedMerged = ref(false);
const copiedAiBlock = ref(false);
const activeRobotsTab = ref<'merged' | 'block' | 'original'>('merged');

const copyToClipboard = () => {
    navigator.clipboard.writeText(props.sampleLlmsTxt);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
};

const copyMergedRobots = () => {
    navigator.clipboard.writeText(props.mergedRobotsTxt || '');
    copiedMerged.value = true;
    setTimeout(() => {
        copiedMerged.value = false;
    }, 2000);
};

const copyAiBlock = () => {
    navigator.clipboard.writeText(props.aiRobotsBlock || '');
    copiedAiBlock.value = true;
    setTimeout(() => {
        copiedAiBlock.value = false;
    }, 2000);
};

const downloadRobotsTxt = () => {
    const textToDownload = activeRobotsTab.value === 'block' 
        ? (props.aiRobotsBlock || '') 
        : (props.mergedRobotsTxt || '');
    const filename = activeRobotsTab.value === 'block' ? 'robots-ai-block.txt' : 'robots.txt';
    const blob = new Blob([textToDownload], { type: 'text/plain;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
};
</script>

<template>
    <AppLayout :title="`Yapay Zeka Bulunurluğu (GEO) - ${project.name}`">
        <Head :title="`Yapay Zeka Bulunurluğu (GEO) - ${project.name}`" />

        <div class="space-y-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-6 rounded-3xl bg-slate-900/60 border border-slate-800/80">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="`/projects/${project.id}`"
                        class="p-2.5 rounded-2xl bg-slate-800/60 border border-slate-700/60 text-slate-400 hover:text-white transition-colors"
                    >
                        <ArrowLeft class="w-4 h-4" />
                    </Link>
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="px-2.5 py-0.5 rounded-full bg-fuchsia-500/10 text-fuchsia-400 border border-fuchsia-500/20 text-[10px] font-bold uppercase tracking-wider">
                                GEO & LLMO
                            </span>
                            <h1 class="text-2xl font-bold text-white">Yapay Zeka Bulunurluğu</h1>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">
                            Sitenizin ChatGPT, Gemini, Perplexity ve Claude tarafından taranma, anlaşılma ve kaynak gösterilme potansiyeli.
                        </p>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <a
                        :href="project.start_url + '/robots.txt'"
                        target="_blank"
                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold flex items-center space-x-1.5 transition-all"
                    >
                        <span>Robots.txt İncele</span>
                        <ExternalLink class="w-3 h-3" />
                    </a>
                </div>
            </div>

            <!-- Score Cards Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- AI Score Dial -->
                <div class="p-6 rounded-3xl bg-slate-900/50 border border-slate-800/80 flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">AI Readiness Score (GEO)</span>
                        <div class="mt-4 flex items-center space-x-4">
                            <div
                                class="w-24 h-24 rounded-2xl flex items-center justify-center font-black text-3xl shadow-xl border"
                                :class="aiScore >= 80 ? 'bg-fuchsia-500/10 text-fuchsia-400 border-fuchsia-500/30' : (aiScore >= 60 ? 'bg-indigo-500/10 text-indigo-400 border-indigo-500/30' : 'bg-amber-500/10 text-amber-400 border-amber-500/30')"
                            >
                                {{ aiScore }}%
                            </div>
                            <div>
                                <div class="text-sm font-bold text-white">
                                    {{ aiScore >= 80 ? 'Yapay Zekaya Hazır' : (aiScore >= 60 ? 'İyi Seviyede' : 'Geliştirilmeli') }}
                                </div>
                                <div class="text-xs text-slate-400 mt-1">
                                    {{ aiScore >= 80 ? 'LLM botları sitenizi kolayca özetleyebilir ve kaynak gösterebilir.' : 'Bot erişimleri veya llms.txt standardı eksik.' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-800/80 text-[11px] text-slate-500 space-y-1">
                        <div>&bull; <strong>Bot İzinleri:</strong> GPTBot, Gemini, Perplexity, Claude</div>
                        <div>&bull; <strong>Standartlar:</strong> llms.txt & Knowledge Graph JSON-LD</div>
                    </div>
                </div>

                <!-- llms.txt Status Card -->
                <div class="p-6 rounded-3xl bg-slate-900/50 border border-slate-800/80 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">llms.txt Standardı</span>
                            <span
                                class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                :class="llmsTxtFound ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'"
                            >
                                {{ llmsTxtFound ? 'Mevcut' : 'Bulunamadı' }}
                            </span>
                        </div>

                        <p class="text-xs text-slate-400 mt-3">
                            <strong>llms.txt</strong>, web sitenizin yapay zeka modelleri tarafından okunabilen en güncel markdown formatındaki özet haritasıdır.
                        </p>

                        <div class="mt-4 p-3 rounded-xl bg-slate-950/80 border border-slate-800/80 font-mono text-[11px] text-slate-300">
                            {{ project.domain }}/llms.txt
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-800/80 text-[11px] text-slate-500">
                        {{ llmsTxtFound ? 'Siteniz yapay zekalara doğrudan özet içerik sunabiliyor.' : 'Aşağıdaki hazır şablonu kopyalayıp sitenize ekleyebilirsiniz.' }}
                    </div>
                </div>

                <!-- Structured Data Card -->
                <div class="p-6 rounded-3xl bg-slate-900/50 border border-slate-800/80 flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Semantic Knowledge Graph</span>
                        <div class="mt-3 space-y-2 text-xs">
                            <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-950/60 border border-slate-800/80">
                                <span class="text-slate-300">Organization Şeması:</span>
                                <span :class="hasOrgSchema ? 'text-emerald-400 font-bold' : 'text-slate-500'">
                                    {{ hasOrgSchema ? 'Tespit Edildi' : 'Yok' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-950/60 border border-slate-800/80">
                                <span class="text-slate-300">FAQ / Soru-Cevap Şeması:</span>
                                <span :class="hasFaqSchema ? 'text-emerald-400 font-bold' : 'text-slate-500'">
                                    {{ hasFaqSchema ? 'Tespit Edildi' : 'Yok' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-950/60 border border-slate-800/80">
                                <span class="text-slate-300">Zengin İçerikli Sayfa Oranı:</span>
                                <span class="text-indigo-400 font-bold">
                                    {{ totalPagesSampled > 0 ? Math.round((pagesWithGoodWordCount / totalPagesSampled) * 100) : 0 }}%
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-800/80 text-[11px] text-slate-500">
                        LLM'ler yapılandırılmış JSON-LD verilerini doğrudan bilgi kaynağı olarak benimser.
                    </div>
                </div>
            </div>

            <!-- AI Bot Permission Table -->
            <div class="rounded-3xl bg-slate-900/50 border border-slate-800/80 p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-white flex items-center space-x-2">
                            <Bot class="w-5 h-5 text-indigo-400" />
                            <span>Yapay Zeka Botları Erişim İzinleri (Robots.txt)</span>
                        </h2>
                        <p class="text-xs text-slate-400 mt-1">
                            Sitenizin robots.txt dosyasında popüler yapay zeka tarayıcılarına koyulan kurallar.
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                            <tr>
                                <th class="p-3.5">Yapay Zeka Botu (User-Agent)</th>
                                <th class="p-3.5">Sağlayıcı / Model</th>
                                <th class="p-3.5">Durum</th>
                                <th class="p-3.5 text-right">Etki</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <tr v-for="(bot, key) in aiBots" :key="key" class="hover:bg-slate-800/20">
                                <td class="p-3.5 font-mono font-bold text-white">{{ key }}</td>
                                <td class="p-3.5 text-slate-300">{{ bot.name }} ({{ bot.company }})</td>
                                <td class="p-3.5">
                                    <span
                                        v-if="bot.status === 'allowed'"
                                        class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-semibold text-[11px]"
                                    >
                                        <CheckCircle2 class="w-3 h-3" />
                                        <span>Erişime Açık</span>
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/20 font-semibold text-[11px]"
                                    >
                                        <XCircle class="w-3 h-3" />
                                        <span>Engelli (Disallowed)</span>
                                    </span>
                                </td>
                                <td class="p-3.5 text-right text-slate-400">
                                    {{ bot.status === 'allowed' ? 'Model yanıtlarında sitenizi kaynak gösterebilir' : 'Model bu siteden doğrudan veri alamaz' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Smart robots.txt AI Merger & Enhancer Section -->
            <div class="rounded-3xl bg-slate-900/50 border border-slate-800/80 p-6 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center space-x-2.5">
                            <ShieldCheck class="w-5 h-5 text-emerald-400" />
                            <h2 class="text-base font-bold text-white">Akıllı robots.txt Düzenleyici & AI Bot Yetkilendirme</h2>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">
                            Sitenizdeki orijinal dosya çekildi; mevcut kurallarınızdan hiçbir şey silinmeden yapay zeka arama botları için gerekli izinler eklendi.
                        </p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center flex-wrap gap-2">
                        <button
                            v-if="activeRobotsTab === 'block'"
                            @click="copyAiBlock"
                            class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs transition-all flex items-center space-x-1.5 shadow-lg shadow-indigo-600/20"
                        >
                            <Check v-if="copiedAiBlock" class="w-3.5 h-3.5 text-emerald-300" />
                            <Copy v-else class="w-3.5 h-3.5" />
                            <span>{{ copiedAiBlock ? 'Kopyalandı!' : 'AI Bloğunu Kopyala' }}</span>
                        </button>
                        <button
                            v-else
                            @click="copyMergedRobots"
                            class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs transition-all flex items-center space-x-1.5 shadow-lg shadow-indigo-600/20"
                        >
                            <Check v-if="copiedMerged" class="w-3.5 h-3.5 text-emerald-300" />
                            <Copy v-else class="w-3.5 h-3.5" />
                            <span>{{ copiedMerged ? 'Kopyalandı!' : 'Tüm robots.txt\'yi Kopyala' }}</span>
                        </button>

                        <button
                            @click="downloadRobotsTxt"
                            class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-semibold text-xs transition-all flex items-center space-x-1.5"
                        >
                            <Download class="w-3.5 h-3.5 text-indigo-400" />
                            <span>Dosyayı İndir (.txt)</span>
                        </button>
                    </div>
                </div>

                <!-- Status Banner -->
                <div
                    class="p-4 rounded-2xl border text-xs flex items-start space-x-3"
                    :class="robotsFound ? 'bg-emerald-500/5 border-emerald-500/20 text-emerald-300' : 'bg-amber-500/5 border-amber-500/20 text-amber-300'"
                >
                    <CheckCircle2 v-if="robotsFound" class="w-4 h-4 text-emerald-400 flex-shrink-0 mt-0.5" />
                    <AlertTriangle v-else class="w-4 h-4 text-amber-400 flex-shrink-0 mt-0.5" />
                    <div class="space-y-1">
                        <div class="font-bold">
                            {{ robotsFound ? 'Mevcut robots.txt sitenizden başarıyla çekildi' : 'Sitenizde henüz bir robots.txt bulunamadı' }}
                        </div>
                        <p class="text-slate-400 leading-relaxed">
                            {{ robotsFound 
                                ? 'Mevcut robots.txt dosyanızdaki Disallow veya kısıtlama kuralları asla silinmez. ChatGPT, Gemini, Perplexity, Claude ve Siri\'nin sitenizi kaynak gösterebilmesi için gereken izin blokları ve llms.txt referansı dosyanızın en sonuna güvenle eklenmiştir.'
                                : 'Sitenizde aktif bir robots.txt bulunmadığı için tüm yapay zeka modelleri ve web tarayıcılarına uygun standart bir başlangıç robots.txt şablonu oluşturulmuştur.' 
                            }}
                        </p>
                    </div>
                </div>

                <!-- View Switcher Tabs -->
                <div class="flex items-center space-x-2 border-b border-slate-800 pb-3">
                    <button
                        @click="activeRobotsTab = 'merged'"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all flex items-center space-x-1.5"
                        :class="activeRobotsTab === 'merged' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white bg-slate-800/50'"
                    >
                        <Layers class="w-3.5 h-3.5" />
                        <span>Önerilen & Birleştirilmiş robots.txt</span>
                    </button>
                    <button
                        @click="activeRobotsTab = 'block'"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all flex items-center space-x-1.5"
                        :class="activeRobotsTab === 'block' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white bg-slate-800/50'"
                    >
                        <Plus class="w-3.5 h-3.5" />
                        <span>Yalnızca Eklenen AI Bloğu</span>
                    </button>
                    <button
                        v-if="robotsFound"
                        @click="activeRobotsTab = 'original'"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all flex items-center space-x-1.5"
                        :class="activeRobotsTab === 'original' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white bg-slate-800/50'"
                    >
                        <FileText class="w-3.5 h-3.5" />
                        <span>Sitenizdeki Orijinal Dosya</span>
                    </button>
                </div>

                <!-- File Code Display -->
                <div class="relative">
                    <pre
                        v-if="activeRobotsTab === 'merged'"
                        class="p-5 rounded-2xl bg-slate-950 border border-slate-800 text-xs font-mono text-slate-300 overflow-x-auto whitespace-pre-wrap leading-relaxed max-h-[380px]"
                    >{{ mergedRobotsTxt }}</pre>

                    <pre
                        v-else-if="activeRobotsTab === 'block'"
                        class="p-5 rounded-2xl bg-slate-950 border border-slate-800 text-xs font-mono text-emerald-300/90 overflow-x-auto whitespace-pre-wrap leading-relaxed max-h-[380px]"
                    >{{ aiRobotsBlock }}</pre>

                    <pre
                        v-else-if="activeRobotsTab === 'original'"
                        class="p-5 rounded-2xl bg-slate-950 border border-slate-800 text-xs font-mono text-slate-400 overflow-x-auto whitespace-pre-wrap leading-relaxed max-h-[380px]"
                    >{{ originalRobotsTxt || '# Sitenizde robots.txt bulunamadı.' }}</pre>
                </div>

                <!-- Fast Implementation Guide -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pt-2 text-xs">
                    <div class="p-3 rounded-2xl bg-slate-950/60 border border-slate-800/60 space-y-1">
                        <span class="font-bold text-indigo-400">1. Kopyalayın / İndirin</span>
                        <p class="text-slate-400 text-[11px]">Yukarıdaki birleştirilmiş robots.txt dosyasını indirin veya kopyalayın.</p>
                    </div>
                    <div class="p-3 rounded-2xl bg-slate-950/60 border border-slate-800/60 space-y-1">
                        <span class="font-bold text-indigo-400">2. public_html'e Yükleyin</span>
                        <p class="text-slate-400 text-[11px]">Web sitenizin ana dizinindeki (cPanel public_html/robots.txt veya sunucu ana dizini) dosyanın yerine kaydedin.</p>
                    </div>
                    <div class="p-3 rounded-2xl bg-slate-950/60 border border-slate-800/60 space-y-1">
                        <span class="font-bold text-indigo-400">3. Anında AI Erişimi</span>
                        <p class="text-slate-400 text-[11px]">ChatGPT, Claude ve Perplexity gibi modeller sitenizi engel olmadan tarayıp yanıtlarda referans verecektir.</p>
                    </div>
                </div>
            </div>

            <!-- llms.txt Generator Box -->
            <div class="rounded-3xl bg-slate-900/50 border border-slate-800/80 p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-white flex items-center space-x-2">
                            <Sparkles class="w-5 h-5 text-fuchsia-400" />
                            <span>Önerilen llms.txt Dosyanız</span>
                        </h2>
                        <p class="text-xs text-slate-400 mt-1">
                            Bu içeriği sitenizin ana dizininde <code class="text-fuchsia-400 font-mono">public_html/llms.txt</code> olarak kaydedin.
                        </p>
                    </div>

                    <button
                        @click="copyToClipboard"
                        class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs transition-all flex items-center space-x-1.5"
                    >
                        <Check v-if="copied" class="w-3.5 h-3.5 text-emerald-300" />
                        <Copy v-else class="w-3.5 h-3.5" />
                        <span>{{ copied ? 'Kopyalandı!' : 'Şablonu Kopyala' }}</span>
                    </button>
                </div>

                <div class="relative">
                    <pre class="p-5 rounded-2xl bg-slate-950 border border-slate-800 text-xs font-mono text-slate-300 overflow-x-auto whitespace-pre-wrap leading-relaxed">{{ sampleLlmsTxt }}</pre>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
