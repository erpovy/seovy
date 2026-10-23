<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ShieldAlert, Search } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import Php2ShellPanel from '@/Components/Php2ShellPanel.vue';
import { useI18n } from '@/i18n';

const props = defineProps<{
    url: string;
    canScan: boolean;
    assessment: any;
}>();
const { t } = useI18n();
const form = useForm({ url: props.url });
const scan = () => {
    if (!props.canScan || form.processing) return;
    form.transform(data => ({ url: data.url.trim() })).post('/security/php2shell', { preserveScroll: true });
};
</script>

<template>
    <AppLayout :title="t('php2shell.title')">
        <Head :title="t('php2shell.title')" />
        <div class="max-w-5xl mx-auto space-y-6">
            <header class="space-y-2">
                <h1 class="flex items-center gap-3 text-2xl font-bold text-white">
                    <ShieldAlert class="w-7 h-7 text-indigo-400" aria-hidden="true" />
                    {{ t('php2shell.title') }}
                </h1>
                <p class="text-sm text-slate-400 leading-relaxed">{{ t('php2shell.intro') }}</p>
            </header>

            <form @submit.prevent="scan" class="rounded-2xl border border-slate-800 bg-slate-900 p-5 sm:p-6 space-y-4" :aria-busy="form.processing">
                <label for="security-url" class="block text-sm font-medium text-slate-200">{{ t('php2shell.url_label') }}</label>
                <div class="flex flex-col sm:flex-row gap-3">
                    <input id="security-url" v-model="form.url" type="url" inputmode="url" required maxlength="2048"
                        placeholder="https://example.com" autocomplete="url" spellcheck="false"
                        :disabled="form.processing || !canScan" :aria-invalid="!!form.errors.url"
                        aria-describedby="security-url-help security-url-error"
                        class="min-h-11 min-w-0 flex-1 rounded-xl bg-slate-950 border border-slate-700 px-3 text-sm text-slate-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-400" />
                    <button type="submit" :disabled="!canScan || !form.url.trim() || form.processing" class="min-h-11 inline-flex justify-center items-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-400">
                        <Search class="w-4 h-4" aria-hidden="true" />
                        {{ t(form.processing ? 'php2shell.scanning' : 'php2shell.scan') }}
                    </button>
                </div>
                <p v-if="!canScan" class="text-sm text-amber-300">{{ t('php2shell.permission') }}</p>
                <p id="security-url-error" v-if="form.errors.url" role="alert" class="text-sm text-rose-300">{{ form.errors.url }}</p>
                <p v-if="form.hasErrors && !form.errors.url" role="alert" class="text-sm text-rose-300">{{ t('php2shell.connection_failed') }}</p>
                <p id="security-url-help" class="text-sm text-slate-400">{{ t('php2shell.cache_note') }}</p>
            </form>
            <div v-if="form.processing || (assessment && !form.hasErrors)" aria-live="polite" class="space-y-3">
                <p v-if="assessment && !form.processing" class="text-sm text-slate-400 break-all">
                    {{ t('php2shell.last_check') }}: {{ new Date(assessment.checked_at).toLocaleString() }} · HTTP {{ assessment.http_status }} · {{ assessment.url }}
                </p>
                <Php2ShellPanel :assessment="assessment" :running="form.processing" />
            </div>
        </div>
    </AppLayout>
</template>
