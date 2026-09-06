<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    FileText,
    Search,
    Globe,
    Smartphone,
    Monitor,
    Sparkles,
    CheckCircle2,
    AlertCircle,
    ArrowRight,
    Link2,
    RefreshCw
} from 'lucide-vue-next';

const props = defineProps<{
    project: any;
    page: any;
    url: string;
    findings: Array<any>;
    internalLinkSuggestions: Array<any>;
}>();

const targetUrl = ref(props.url || props.project.start_url);
const focusKeyword = ref('');
const previewDevice = ref<'desktop' | 'mobile'>('desktop');

const livePage = ref<any>(props.page);
const liveFindings = ref<Array<any>>(props.findings || []);
const keywordAnalysis = ref<any>(null);
const isAnalyzing = ref(false);
const errorMsg = ref('');

// Editable Title and Description for real-time SERP snippet prototyping
const customTitle = ref(props.page?.title || props.project.name);
const customDescription = ref(props.page?.meta_description || 'Sayfa meta açıklaması buraya gelecek...');

const runLiveAnalysis = async () => {
    isAnalyzing.value = true;
    errorMsg.value = '';

    try {
        const response = await fetch(`/projects/${props.project.id}/on-page/live`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content || '',
            },
            body: JSON.stringify({
                url: targetUrl.value,
                focus_keyword: focusKeyword.value,
            }),
        });

        const data = await response.json();
        if (response.ok && data.success) {
            livePage.value = data.page;
            liveFindings.value = data.findings;
            keywordAnalysis.value = data.keyword_analysis;
            customTitle.value = data.page.title || customTitle.value;
            customDescription.value = data.page.meta_description || customDescription.value;
        } else {
            errorMsg.value = data.message || 'Analiz başarısız oldu.';
        }
    } catch (e: any) {
        errorMsg.value = e.message || 'Bağlantı hatası.';
    } finally {
        isAnalyzing.value = false;
    }
};
</script>

<template>
    <AppLayout :title="`Sayfa İçi SEO & SERP Önizleme - ${project.name}`">
        <Head :title="`Sayfa İçi SEO & SERP Önizleme - ${project.name}`" />

        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-white">Sayfa İçi SEO & SERP Simülatörü</h1>
                <p class="text-sm text-slate-400 mt-1">
                    Tekil bir URL'yi canlı analiz edin, Google arama sonuçları önizlemesini test edin ve iç bağlantı önerilerini inceleyin.
                </p>
            </div>

            <!-- Analysis Input Bar -->
            <div class="p-6 rounded-3xl bg-slate-900/50 border border-slate-800/80 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                            İncelenecek Sayfa URL'si
                        </label>
                        <div class="relative">
                            <input
                                v-model="targetUrl"
                                type="url"
                                required
                                class="w-full px-4 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-sm text-white font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                                placeholder="https://example.com/ornek-sayfa"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                            Odak Anahtar Kelime (İsteğe Bağlı)
                        </label>
                        <input
                            v-model="focusKeyword"
                            type="text"
                            class="w-full px-4 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                            placeholder="Örn: seo uzmanı"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <span v-if="errorMsg" class="text-xs text-rose-400 flex items-center space-x-1.5">
                        <AlertCircle class="w-4 h-4 shrink-0" />
                        <span>{{ errorMsg }}</span>
                    </span>
                    <span v-else class="text-xs text-slate-500">SSRF korumalı güvenli tekil analiz motoru</span>

                    <button
                        @click="runLiveAnalysis"
                        :disabled="isAnalyzing"
                        class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 flex items-center space-x-2 transition-all disabled:opacity-50"
                    >
                        <RefreshCw v-if="isAnalyzing" class="w-3.5 h-3.5 animate-spin" />
                        <Sparkles v-else class="w-3.5 h-3.5" />
                        <span>Canlı Analiz Başlat</span>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- SERP Google Result Preview Card -->
                <div class="p-6 rounded-3xl bg-slate-900/50 border border-slate-800/80 space-y-5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Google SERP Önizlemesi</span>
                        <div class="flex items-center bg-slate-950 rounded-lg p-1 border border-slate-800">
                            <button
                                @click="previewDevice = 'desktop'"
                                class="p-1.5 rounded text-xs transition-colors"
                                :class="previewDevice === 'desktop' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white'"
                            >
                                <Monitor class="w-3.5 h-3.5" />
                            </button>
                            <button
                                @click="previewDevice = 'mobile'"
                                class="p-1.5 rounded text-xs transition-colors"
                                :class="previewDevice === 'mobile' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white'"
                            >
                                <Smartphone class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>

                    <!-- Google Snippet Simulation Box (White background like Google) -->
                    <div
                        class="p-5 rounded-2xl bg-white text-slate-900 shadow-md font-sans transition-all"
                        :class="previewDevice === 'mobile' ? 'max-w-sm mx-auto' : ''"
                    >
                        <!-- Site Identity / Breadcrumb -->
                        <div class="flex items-center space-x-2 mb-1">
                            <div class="w-4 h-4 rounded-full bg-slate-200 flex items-center justify-center text-[9px] font-bold text-slate-600">
                                G
                            </div>
                            <div class="text-[12px] text-slate-700 leading-tight">
                                <span class="font-medium">{{ project.name }}</span>
                                <span class="text-slate-500 ml-1 truncate block font-mono text-[11px]">{{ targetUrl }}</span>
                            </div>
                        </div>

                        <!-- Title -->
                        <div class="text-[18px] text-[#1a0dab] hover:underline font-normal cursor-pointer leading-snug break-words">
                            {{ customTitle }}
                        </div>

                        <!-- Snippet Meta Description -->
                        <div class="text-[13px] text-[#4d5156] mt-1 leading-normal break-words">
                            {{ customDescription }}
                        </div>
                    </div>

                    <!-- Interactive Character Gauges -->
                    <div class="space-y-3 pt-2">
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-slate-400">Başlık Karakter Uzunluğu:</span>
                                <span :class="customTitle.length > 60 ? 'text-amber-400 font-bold' : 'text-emerald-400'">
                                    {{ customTitle.length }} / 60 önerilen
                                </span>
                            </div>
                            <input
                                v-model="customTitle"
                                type="text"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white"
                                placeholder="Başlık metnini test edin..."
                            />
                        </div>

                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-slate-400">Açıklama Karakter Uzunluğu:</span>
                                <span :class="customDescription.length > 155 ? 'text-amber-400 font-bold' : 'text-emerald-400'">
                                    {{ customDescription.length }} / 155 önerilen
                                </span>
                            </div>
                            <textarea
                                v-model="customDescription"
                                rows="2"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white"
                                placeholder="Meta açıklama metnini test edin..."
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Focus Keyword & Content Analysis -->
                <div class="p-6 rounded-3xl bg-slate-900/50 border border-slate-800/80 space-y-5">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">İçerik & Anahtar Kelime Denetimi</span>

                    <div v-if="keywordAnalysis" class="p-4 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 space-y-3 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Odak Kelime:</span>
                            <span class="font-bold text-white">"{{ keywordAnalysis.keyword }}"</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Sayfada Bulunma:</span>
                            <span class="font-bold text-white">{{ keywordAnalysis.found_count }} kez</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Kelime Yoğunluğu (Density):</span>
                            <span class="font-bold text-indigo-400">%{{ keywordAnalysis.density_percent }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Başlıkta (Title) Geçiyor mu:</span>
                            <span :class="keywordAnalysis.in_title ? 'text-emerald-400 font-bold' : 'text-rose-400'">
                                {{ keywordAnalysis.in_title ? 'Evet ✓' : 'Hayır ✗' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Meta Açıklamada Geçiyor mu:</span>
                            <span :class="keywordAnalysis.in_meta_description ? 'text-emerald-400 font-bold' : 'text-rose-400'">
                                {{ keywordAnalysis.in_meta_description ? 'Evet ✓' : 'Hayır ✗' }}
                            </span>
                        </div>
                        <div class="pt-2 border-t border-indigo-500/20 text-slate-300">
                            <strong>Öneri:</strong> {{ keywordAnalysis.recommendation }}
                        </div>
                    </div>

                    <div v-else class="p-4 rounded-2xl bg-slate-950/60 border border-slate-850 text-xs text-slate-500 text-center">
                        Odak anahtar kelime denetimi yapmak için yukarıdaki kutuya bir terim yazıp analizi başlatın.
                    </div>

                    <!-- Internal Linking Suggestions -->
                    <div class="space-y-3 pt-2">
                        <div class="flex items-center space-x-2 text-xs font-semibold text-slate-300 uppercase tracking-wider">
                            <Link2 class="w-4 h-4 text-cyan-400" />
                            <span>İç Bağlantı Önerileri</span>
                        </div>

                        <div v-if="internalLinkSuggestions.length === 0" class="text-xs text-slate-500">
                            Bu sayfa için henüz taranmış ilişkili iç bağlantı önerisi bulunmuyor.
                        </div>

                        <div v-else class="space-y-2">
                            <div
                                v-for="(suggestion, idx) in internalLinkSuggestions"
                                :key="idx"
                                class="p-3 rounded-xl bg-slate-950/60 border border-slate-850 text-xs flex items-center justify-between"
                            >
                                <div class="truncate mr-2">
                                    <div class="font-semibold text-white truncate">{{ suggestion.title || 'Başlıksız Sayfa' }}</div>
                                    <div class="text-[11px] text-slate-500 truncate">{{ suggestion.url }}</div>
                                </div>
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-cyan-500/10 text-cyan-400 shrink-0">
                                    İç Link Önerisi
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
