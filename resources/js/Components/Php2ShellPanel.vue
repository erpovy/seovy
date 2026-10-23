<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { ShieldAlert, ExternalLink, Info, CheckCircle2, Copy, Check, LoaderCircle } from 'lucide-vue-next';
import { useI18n } from '@/i18n';

interface Assessment {
    status: 'potential_risk' | 'fixed_version_reported' | 'unknown';
    url: string;
    requested_url: string;
    http_status: number;
    checked_at: string;
    observations: Array<{ url: string; version: string; header: string; status: string; fixed_version: string | null }>;
    fixed_versions: string[];
}

const props = defineProps<{ assessment?: Assessment | null; running: boolean }>();
const { t, locale } = useI18n();
const status = computed(() => props.running ? 'pending' : props.assessment?.status ?? 'not_checked');
const versions = computed(() => [...new Set(props.assessment?.observations.map(item => item.version) ?? [])].join(', '));
const riskyObservation = computed(() => props.assessment?.observations.find(item => item.status === 'potential_risk'));
const fixedVersion = computed(() => riskyObservation.value?.fixed_version);
const icon = computed(() => status.value === 'potential_risk' ? ShieldAlert : status.value === 'fixed_version_reported' ? CheckCircle2 : Info);
const tone = computed(() => status.value === 'potential_risk' ? 'border-amber-500/40 bg-amber-500/10 text-amber-300'
    : status.value === 'fixed_version_reported' ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300'
    : 'border-indigo-500/30 bg-indigo-500/10 text-indigo-300');
const explanation = computed(() => status.value === 'unknown'
    ? t(versions.value ? 'php2shell.simple.unknown_branch' : 'php2shell.simple.unknown_reason')
    : t(`php2shell.simple.${status.value}_reason`));
const supportMessage = computed(() => t('php2shell.simple.support_message', {
    url: props.assessment?.requested_url || props.assessment?.url || '',
    version: versions.value || t('php2shell.simple.hidden_version'),
}));
const showMessage = ref(false);
const copyState = ref<'idle' | 'copied' | 'failed'>('idle');
watch([() => props.assessment, () => props.running, locale], () => {
    showMessage.value = false;
    copyState.value = 'idle';
});
const copyMessage = async () => {
    showMessage.value = true;
    try {
        await navigator.clipboard.writeText(supportMessage.value);
        copyState.value = 'copied';
    } catch {
        copyState.value = 'failed';
    }
};
</script>

<template>
    <section aria-labelledby="php2shell-result" class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900">
        <div v-if="running" class="p-6 sm:p-8 flex items-center gap-4" role="status">
            <LoaderCircle class="w-6 h-6 text-indigo-400 motion-safe:animate-spin shrink-0" aria-hidden="true" />
            <div>
                <h2 id="php2shell-result" class="font-semibold text-white">{{ t('php2shell.scanning') }}</h2>
                <p class="mt-1 text-sm text-slate-300">{{ t('php2shell.simple.pending_reason') }}</p>
            </div>
        </div>
        <template v-else-if="assessment">
            <div class="p-6 sm:p-8 space-y-4">
                <div class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-sm font-medium" :class="tone">
                    <component :is="icon" class="w-4 h-4 shrink-0" aria-hidden="true" />
                    {{ t(`php2shell.simple.${status}_badge`) }}
                </div>
                <h2 id="php2shell-result" class="max-w-2xl text-xl sm:text-2xl font-bold text-white leading-snug [text-wrap:balance]">{{ t(`php2shell.simple.${status}_title`) }}</h2>
                <p class="max-w-3xl text-base leading-relaxed text-slate-300">{{ explanation }}</p>
                <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
                    <span class="text-slate-400">{{ t('php2shell.simple.php_version') }}: <strong class="font-medium text-slate-200">{{ versions || t('php2shell.simple.hidden_version') }}</strong></span>
                    <span v-if="fixedVersion" class="text-amber-200">{{ t('php2shell.simple.required_version', { version: fixedVersion }) }}</span>
                </div>
            </div>

            <div class="border-t border-slate-800 p-6 sm:p-8 space-y-5">
                <h3 class="text-base font-semibold text-white">{{ t('php2shell.simple.next_steps') }}</h3>
                <ol class="space-y-5">
                    <li class="flex gap-3">
                        <span aria-hidden="true" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-500/15 text-sm font-semibold text-indigo-300">1</span>
                        <div class="space-y-2 min-w-0">
                            <h4 class="font-semibold text-slate-100">{{ t(`php2shell.simple.${status}_action`) }}</h4>
                            <p class="text-sm leading-relaxed text-slate-300">{{ t(`php2shell.simple.${status}_action_detail`) }}</p>
                            <button type="button" @click="copyMessage" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-400">
                                <component :is="copyState === 'copied' ? Check : Copy" class="h-4 w-4 shrink-0" aria-hidden="true" />
                                {{ t(copyState === 'copied' ? 'php2shell.simple.copied' : 'php2shell.simple.copy_message') }}
                            </button>
                            <div v-if="showMessage" class="space-y-2 pt-2">
                                <label for="php2shell-support-message" class="block text-sm font-medium text-slate-200">{{ t('php2shell.simple.message_label') }}</label>
                                <textarea id="php2shell-support-message" :value="supportMessage" readonly rows="7" class="w-full min-h-40 rounded-xl border border-slate-700 bg-slate-950 p-3 text-sm leading-relaxed text-slate-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-400" />
                                <p role="status" class="text-sm text-slate-300">{{ t(copyState === 'failed' ? 'php2shell.simple.copy_failed' : 'php2shell.simple.copy_hint') }}</p>
                            </div>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <span aria-hidden="true" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-500/15 text-sm font-semibold text-indigo-300">2</span>
                        <div>
                            <h4 class="font-semibold text-slate-100">{{ t(`php2shell.simple.${status}_followup`) }}</h4>
                            <p class="mt-1 text-sm leading-relaxed text-slate-300">{{ t(`php2shell.simple.${status}_followup_detail`) }}</p>
                        </div>
                    </li>
                </ol>
            </div>

            <details class="border-t border-slate-800 px-6 sm:px-8 py-3">
                <summary class="min-h-11 cursor-pointer py-3 text-sm text-slate-300 hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-400">{{ t('php2shell.simple.details') }}</summary>
                <div class="pb-4 pt-2 space-y-4 text-sm text-slate-300 leading-relaxed">
                    <dl class="space-y-2">
                        <div><dt class="inline text-slate-400">{{ t('php2shell.url_label') }}: </dt><dd class="inline break-all">{{ assessment.url }}</dd></div>
                        <div><dt class="inline text-slate-400">{{ t('php2shell.last_check') }}: </dt><dd class="inline">{{ new Date(assessment.checked_at).toLocaleString() }}</dd></div>
                        <div><dt class="inline text-slate-400">HTTP: </dt><dd class="inline">{{ assessment.http_status }} · {{ t('php2shell.simple.http_note') }}</dd></div>
                    </dl>
                    <ul v-if="assessment.observations.length" class="space-y-2">
                        <li v-for="(item, index) in assessment.observations" :key="index" class="break-all font-mono">{{ item.header }}: PHP/{{ item.version }}</li>
                    </ul>
                    <p>{{ t('php2shell.limitations') }}</p>
                    <p>{{ t('php2shell.upgrade') }} {{ assessment.fixed_versions.join(', ') }}.</p>
                    <p>{{ t('php2shell.session_advisory') }}</p>
                    <a href="https://www.php.net/archive/2026.php" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center gap-2 text-indigo-300 hover:text-indigo-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-400">
                        {{ t('php2shell.source') }} <ExternalLink class="w-4 h-4" aria-hidden="true" />
                    </a>
                </div>
            </details>
        </template>
    </section>
</template>
