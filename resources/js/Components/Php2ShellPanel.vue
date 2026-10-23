<script setup lang="ts">
import { computed } from 'vue';
import { ShieldAlert, ExternalLink } from 'lucide-vue-next';
import { useI18n } from '@/i18n';

interface Assessment {
    status: 'potential_risk' | 'fixed_version_reported' | 'unknown';
    checked_pages: number;
    counts: Record<string, number>;
    observations: Array<{ url: string; version: string; header: string; status: string; fixed_version: string | null }>;
    fixed_versions: string[];
    checked_at: string;
}

const props = defineProps<{ assessment?: Assessment | null; running: boolean }>();
const { t } = useI18n();
const status = computed(() => props.running ? 'pending' : props.assessment?.status ?? 'not_checked');
const statusClass = computed(() => status.value === 'potential_risk'
    ? 'border-amber-500/30 bg-amber-500/10 text-amber-300'
    : 'border-slate-700 bg-slate-800 text-slate-200');
</script>

<template>
    <section aria-labelledby="php2shell-title" class="p-5 sm:p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 id="php2shell-title" class="flex items-center gap-2 text-base font-bold text-white">
                <ShieldAlert class="w-5 h-5 text-indigo-400" aria-hidden="true" />
                {{ t('php2shell.title') }}
            </h2>
            <span class="px-3 py-1 rounded-full border text-sm font-medium" :class="statusClass">{{ t(`php2shell.${status}`) }}</span>
        </div>
        <p class="text-sm text-slate-300 leading-relaxed">{{ t(`php2shell.${status}_description`) }}</p>
        <p class="text-sm text-slate-400 leading-relaxed">{{ t('php2shell.limitations') }}</p>

        <template v-if="assessment && !running">
            <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-slate-300">
                <span>{{ t('php2shell.checked_pages') }}: {{ assessment.checked_pages }}</span>
                <span>{{ t('php2shell.risk_pages') }}: {{ assessment.counts.potential_risk }}</span>
                <span>{{ t('php2shell.unknown_pages') }}: {{ assessment.counts.unknown }}</span>
            </div>
            <details v-if="assessment.observations.length" class="rounded-xl border border-slate-800 p-3">
                <summary class="cursor-pointer text-sm text-indigo-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-400">{{ t('php2shell.evidence') }}</summary>
                <ul class="mt-3 space-y-2 text-sm text-slate-300">
                    <li v-for="(item, index) in assessment.observations" :key="index" class="break-all">
                        <span class="font-mono">PHP/{{ item.version }}</span> · {{ item.header }} · {{ t(`php2shell.${item.status}`) }}
                        <div class="text-slate-400">{{ item.url }}</div>
                    </li>
                </ul>
            </details>
            <p class="text-sm text-slate-300">{{ t('php2shell.upgrade') }} {{ assessment.fixed_versions.join(', ') }}.</p>
            <p class="text-sm text-slate-300 leading-relaxed">{{ t('php2shell.remediation') }}</p>
        </template>

        <div class="border-t border-slate-800 pt-4 space-y-2">
            <p class="text-sm text-slate-400 leading-relaxed">{{ t('php2shell.session_advisory') }}</p>
            <a href="https://www.php.net/archive/2026.php" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center gap-2 text-sm text-indigo-300 hover:text-indigo-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-400">
                {{ t('php2shell.source') }} <ExternalLink class="w-4 h-4" aria-hidden="true" />
            </a>
        </div>
    </section>
</template>
