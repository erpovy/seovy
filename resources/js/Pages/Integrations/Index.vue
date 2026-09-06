<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Zap,
    Gauge,
    Search,
    BarChart3,
    ArrowLeft,
    CheckCircle2,
    RefreshCw,
    ExternalLink,
    Smartphone,
    Monitor,
    Key
} from 'lucide-vue-next';

const props = defineProps<{
    project: any;
    integrations: {
        gsc?: any;
        ga4?: any;
        pagespeed?: any;
    };
    pagespeedConfigured: boolean;
}>();

const pageSpeedForm = useForm({
    url: props.project.start_url,
});

const runPageSpeed = () => {
    pageSpeedForm.post(`/projects/${props.project.id}/integrations/pagespeed`);
};

const showKeyModal = ref(false);
const keyForm = useForm({
    type: 'pagespeed',
    credentials: {
        api_key: '',
    },
});

const saveKey = () => {
    keyForm.post(`/projects/${props.project.id}/integrations/save`, {
        onSuccess: () => {
            showKeyModal.value = false;
        },
    });
};
</script>

<template>
    <AppLayout :title="`${$t('integrations.page_title')} - ${project.name}`">
        <Head :title="`${$t('integrations.page_title')} - ${project.name}`" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center space-x-3">
                <Link :href="`/projects/${project.id}`" class="p-2 rounded-xl bg-slate-900 border border-slate-800 text-slate-400 hover:text-white transition-colors">
                    <ArrowLeft class="w-4 h-4" />
                </Link>
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-white">{{ $t('integrations.title') }}</h1>
                    <p class="text-xs text-slate-400 mt-0.5">
                        {{ $t('integrations.subtitle') }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Google PageSpeed Insights Card -->
                <div class="lg:col-span-2 p-6 rounded-3xl bg-slate-900/50 border border-slate-800/80 space-y-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center">
                                <Gauge class="w-5 h-5" />
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-white">{{ $t('integrations.pagespeed_title') }}</h2>
                                <p class="text-xs text-slate-400">{{ $t('integrations.pagespeed_desc') }}</p>
                            </div>
                        </div>

                        <button
                            @click="runPageSpeed"
                            :disabled="pageSpeedForm.processing"
                            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 flex items-center space-x-1.5 transition-all disabled:opacity-50"
                        >
                            <RefreshCw v-if="pageSpeedForm.processing" class="w-3.5 h-3.5 animate-spin" />
                            <Zap v-else class="w-3.5 h-3.5" />
                            <span>{{ $t('integrations.run_audit') }}</span>
                        </button>
                    </div>

                    <!-- PageSpeed Results -->
                    <div v-if="integrations.pagespeed?.settings" class="space-y-6 pt-2">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Mobile -->
                            <div v-if="integrations.pagespeed.settings.mobile" class="p-5 rounded-2xl bg-slate-950/60 border border-slate-850 space-y-3">
                                <div class="flex items-center justify-between text-xs font-semibold text-slate-400">
                                    <span class="flex items-center space-x-1.5">
                                        <Smartphone class="w-4 h-4 text-indigo-400" />
                                        <span>{{ $t('integrations.mobile_score') }}</span>
                                    </span>
                                    <span class="text-[10px] text-slate-500">Lighthouse</span>
                                </div>
                                <div class="text-3xl font-extrabold text-white">
                                    {{ integrations.pagespeed.settings.mobile.scores.performance ?? '—' }}%
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-[11px] text-slate-400 pt-2 border-t border-slate-800">
                                    <div>LCP: <span class="text-white font-mono">{{ integrations.pagespeed.settings.mobile.lab_metrics?.lcp || '—' }}</span></div>
                                    <div>TBT: <span class="text-white font-mono">{{ integrations.pagespeed.settings.mobile.lab_metrics?.tbt || '—' }}</span></div>
                                    <div>CLS: <span class="text-white font-mono">{{ integrations.pagespeed.settings.mobile.lab_metrics?.cls || '—' }}</span></div>
                                    <div>FCP: <span class="text-white font-mono">{{ integrations.pagespeed.settings.mobile.lab_metrics?.fcp || '—' }}</span></div>
                                </div>
                            </div>

                            <!-- Desktop -->
                            <div v-if="integrations.pagespeed.settings.desktop" class="p-5 rounded-2xl bg-slate-950/60 border border-slate-850 space-y-3">
                                <div class="flex items-center justify-between text-xs font-semibold text-slate-400">
                                    <span class="flex items-center space-x-1.5">
                                        <Monitor class="w-4 h-4 text-cyan-400" />
                                        <span>{{ $t('integrations.desktop_score') }}</span>
                                    </span>
                                    <span class="text-[10px] text-slate-500">Lighthouse</span>
                                </div>
                                <div class="text-3xl font-extrabold text-white">
                                    {{ integrations.pagespeed.settings.desktop.scores.performance ?? '—' }}%
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-[11px] text-slate-400 pt-2 border-t border-slate-800">
                                    <div>LCP: <span class="text-white font-mono">{{ integrations.pagespeed.settings.desktop.lab_metrics?.lcp || '—' }}</span></div>
                                    <div>TBT: <span class="text-white font-mono">{{ integrations.pagespeed.settings.desktop.lab_metrics?.tbt || '—' }}</span></div>
                                    <div>CLS: <span class="text-white font-mono">{{ integrations.pagespeed.settings.desktop.lab_metrics?.cls || '—' }}</span></div>
                                    <div>FCP: <span class="text-white font-mono">{{ integrations.pagespeed.settings.desktop.lab_metrics?.fcp || '—' }}</span></div>
                                </div>
                            </div>
                        </div>

                        <!-- Notice -->
                        <div class="p-4 rounded-2xl bg-indigo-500/5 border border-indigo-500/20 text-xs text-slate-400 leading-relaxed">
                            <strong class="text-indigo-300 font-semibold block mb-1">{{ $t('integrations.lab_notice_title') }}</strong>
                            {{ $t('integrations.lab_notice_desc') }}
                        </div>
                    </div>

                    <div v-else class="p-8 rounded-2xl bg-slate-950/40 border border-slate-850 text-center text-xs text-slate-500">
                        {{ $t('integrations.no_pagespeed') }}
                    </div>
                </div>

                <!-- Google Search Console & GA4 Side Cards -->
                <div class="space-y-6">
                    <!-- GSC -->
                    <div class="p-6 rounded-3xl bg-slate-900/50 border border-slate-800/80 space-y-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center">
                                <Search class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white">{{ $t('integrations.gsc_title') }}</h3>
                                <p class="text-[11px] text-slate-400">{{ $t('integrations.gsc_desc') }}</p>
                            </div>
                        </div>

                        <p class="text-xs text-slate-400 leading-relaxed">
                            {{ $t('integrations.gsc_text') }}
                        </p>

                        <button
                            @click="showKeyModal = true"
                            class="w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-white transition-all flex items-center justify-center space-x-1.5"
                        >
                            <Key class="w-3.5 h-3.5 text-blue-400" />
                            <span>{{ $t('integrations.config_api') }}</span>
                        </button>
                    </div>

                    <!-- GA4 -->
                    <div class="p-6 rounded-3xl bg-slate-900/50 border border-slate-800/80 space-y-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center">
                                <BarChart3 class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white">{{ $t('integrations.ga4_title') }}</h3>
                                <p class="text-[11px] text-slate-400">{{ $t('integrations.ga4_desc') }}</p>
                            </div>
                        </div>

                        <p class="text-xs text-slate-400 leading-relaxed">
                            {{ $t('integrations.ga4_text') }}
                        </p>

                        <button
                            class="w-full py-2.5 rounded-xl bg-slate-800/60 text-xs font-semibold text-slate-400 cursor-not-allowed"
                            disabled
                        >
                            <span>{{ $t('integrations.awaiting_oauth') }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- API Key Setup Modal -->
            <div v-if="showKeyModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
                <div class="w-full max-w-md bg-slate-900 border border-slate-800 p-6 rounded-3xl space-y-4 shadow-2xl">
                    <h2 class="text-base font-bold text-white">{{ $t('integrations.api_modal_title') }}</h2>
                    <form @submit.prevent="saveKey" class="space-y-3 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">{{ $t('integrations.api_key_label') }}</label>
                            <input
                                v-model="keyForm.credentials.api_key"
                                type="password"
                                required
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-mono"
                                placeholder="AIzaSy..."
                            />
                            <span class="text-[10px] text-slate-500 mt-1 block">
                                {{ $t('integrations.aes_notice') }}
                            </span>
                        </div>

                        <div class="pt-3 flex items-center justify-end space-x-2">
                            <button
                                type="button"
                                @click="showKeyModal = false"
                                class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 hover:text-white"
                            >
                                {{ $t('common.cancel') }}
                            </button>
                            <button
                                type="submit"
                                :disabled="keyForm.processing"
                                class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold"
                            >
                                {{ $t('integrations.save_securely') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
