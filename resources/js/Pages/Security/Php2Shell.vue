<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ShieldAlert, Search } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import Php2ShellPanel from '@/Components/Php2ShellPanel.vue';
import { useI18n } from '@/i18n';

const props = defineProps<{
    projects: Array<{ id: number; name: string; domain: string; can_scan: boolean }>;
    selectedProjectId: number | null;
    assessment: any;
}>();
const { t } = useI18n();
const projectId = ref(props.selectedProjectId ? String(props.selectedProjectId) : '');
const form = useForm({});
const selectedProject = computed(() => props.projects.find(project => String(project.id) === projectId.value));
watch(() => props.selectedProjectId, value => { projectId.value = value ? String(value) : ''; });

const selectProject = () => {
    form.clearErrors();
    router.get('/security/php2shell', projectId.value ? { project: projectId.value } : {}, { preserveScroll: true });
};
const scan = () => {
    if (!selectedProject.value?.can_scan || form.processing) return;
    form.post(`/security/php2shell/${projectId.value}`, { preserveScroll: true });
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

            <form v-if="projects.length" @submit.prevent="scan" class="rounded-2xl border border-slate-800 bg-slate-900 p-5 sm:p-6 space-y-4" :aria-busy="form.processing">
                <label for="security-project" class="block text-sm font-medium text-slate-200">{{ t('php2shell.project') }}</label>
                <div class="flex flex-col sm:flex-row gap-3">
                    <select id="security-project" v-model="projectId" @change="selectProject" :disabled="form.processing" class="min-h-11 min-w-0 flex-1 rounded-xl bg-slate-950 border border-slate-700 px-3 text-sm text-slate-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-400">
                        <option value="">{{ t('php2shell.choose_project') }}</option>
                        <option v-for="project in projects" :key="project.id" :value="String(project.id)">{{ project.name }} · {{ project.domain }}</option>
                    </select>
                    <button type="submit" :disabled="!selectedProject?.can_scan || form.processing" class="min-h-11 inline-flex justify-center items-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-400">
                        <Search class="w-4 h-4" aria-hidden="true" />
                        {{ t(form.processing ? 'php2shell.scanning' : 'php2shell.scan') }}
                    </button>
                </div>
                <p v-if="selectedProject && !selectedProject.can_scan" class="text-sm text-amber-300">{{ t('php2shell.permission') }}</p>
                <p v-if="form.hasErrors" role="alert" class="text-sm text-rose-300">{{ t('php2shell.connection_failed') }}</p>
                <p class="text-sm text-slate-400">{{ t('php2shell.cache_note') }}</p>
            </form>
            <div v-else class="rounded-2xl border border-slate-800 bg-slate-900 p-6 space-y-3">
                <p class="text-sm text-slate-300">{{ t('php2shell.no_projects') }}</p>
                <Link href="/projects" class="inline-flex min-h-11 items-center text-sm text-indigo-300 hover:text-indigo-200">{{ t('php2shell.manage_projects') }}</Link>
            </div>

            <div v-if="selectedProject" :key="selectedProject.id" aria-live="polite" class="space-y-3">
                <p v-if="assessment && !form.processing" class="text-sm text-slate-400 break-all">
                    {{ t('php2shell.last_check') }}: {{ new Date(assessment.checked_at).toLocaleString() }} · HTTP {{ assessment.http_status }} · {{ assessment.url }}
                </p>
                <Php2ShellPanel :assessment="assessment" :running="form.processing" />
            </div>
        </div>
    </AppLayout>
</template>
