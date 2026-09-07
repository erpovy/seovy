<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Globe,
    ArrowLeft,
    Layers,
    Save,
    Trash2,
    AlertTriangle,
    Check
} from 'lucide-vue-next';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps<{
    project: {
        id: number;
        name: string;
        domain: string;
        start_url: string;
        target_country: string;
        target_language: string;
        timezone: string;
        crawl_settings?: {
            max_depth?: number;
            max_pages?: number;
            respect_robots?: boolean;
            follow_subdomains?: boolean;
        };
    };
}>();

const form = useForm({
    name: props.project.name || '',
    start_url: props.project.start_url || '',
    target_country: props.project.target_country || 'US',
    target_language: props.project.target_language || 'en',
    timezone: props.project.timezone || 'UTC',
    crawl_settings: {
        max_depth: props.project.crawl_settings?.max_depth ?? 3,
        max_pages: props.project.crawl_settings?.max_pages ?? 250,
        respect_robots: props.project.crawl_settings?.respect_robots ?? true,
        follow_subdomains: props.project.crawl_settings?.follow_subdomains ?? false,
    },
});

const submit = () => {
    form.patch(`/projects/${props.project.id}`);
};

const showDeleteModal = ref(false);
const deleteConfirmText = ref('');
const isDeleting = ref(false);

const openDeleteModal = () => {
    deleteConfirmText.value = '';
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    deleteConfirmText.value = '';
};

const confirmDelete = () => {
    isDeleting.value = true;
    router.delete(`/projects/${props.project.id}`, {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
        }
    });
};
</script>

<template>
    <AppLayout :title="t('projects.edit_website')">
        <Head :title="t('projects.edit_website')" />

        <div class="max-w-3xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center space-x-4">
                <Link
                    :href="`/projects/${project.id}`"
                    class="p-2.5 rounded-2xl bg-slate-900 border border-slate-800 text-slate-400 hover:text-white transition-colors"
                >
                    <ArrowLeft class="w-4 h-4" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-white">{{ t('projects.edit_website') }}</h1>
                    <p class="text-xs text-slate-400 mt-0.5">
                        {{ t('projects.edit_website_subtitle') }}
                    </p>
                </div>
            </div>

            <!-- Form Card -->
            <form @submit.prevent="submit" class="p-8 rounded-3xl bg-slate-900/60 border border-slate-800/80 shadow-2xl space-y-6">
                <!-- Name -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        {{ t('projects.website_name') }}
                    </label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        class="w-full px-4 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500"
                        placeholder="e.g. My Website"
                    />
                    <div v-if="form.errors.name" class="mt-1 text-xs text-rose-400">{{ form.errors.name }}</div>
                </div>

                <!-- Start URL -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        {{ t('projects.start_url') }}
                    </label>
                    <div class="relative">
                        <input
                            v-model="form.start_url"
                            type="url"
                            required
                            class="w-full px-4 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-sm text-white font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500"
                            placeholder="https://example.com"
                        />
                    </div>
                    <p class="mt-1.5 text-[11px] text-slate-500">
                        {{ t('projects.start_url_help') }}
                    </p>
                    <div v-if="form.errors.start_url" class="mt-1 text-xs text-rose-400">{{ form.errors.start_url }}</div>
                </div>

                <!-- Country, Language, Timezone Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                            {{ t('projects.target_country') }}
                        </label>
                        <select
                            v-model="form.target_country"
                            class="w-full px-3 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                        >
                            <option value="US">United States (US)</option>
                            <option value="GB">United Kingdom (GB)</option>
                            <option value="TR">Türkiye (TR)</option>
                            <option value="DE">Germany (DE)</option>
                            <option value="FR">France (FR)</option>
                            <option value="ES">Spain (ES)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                            {{ t('projects.target_language') }}
                        </label>
                        <select
                            v-model="form.target_language"
                            class="w-full px-3 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                        >
                            <option value="en">English (en)</option>
                            <option value="tr">Türkçe (tr)</option>
                            <option value="es">Español (es)</option>
                            <option value="de">Deutsch (de)</option>
                            <option value="fr">Français (fr)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                            {{ t('projects.timezone') }}
                        </label>
                        <select
                            v-model="form.timezone"
                            class="w-full px-3 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                        >
                            <option value="UTC">UTC (GMT+0)</option>
                            <option value="Europe/London">Europe/London (GMT+0)</option>
                            <option value="Europe/Istanbul">Europe/Istanbul (GMT+3)</option>
                            <option value="America/New_York">America/New_York (EST)</option>
                            <option value="Europe/Berlin">Europe/Berlin (CET)</option>
                        </select>
                    </div>
                </div>

                <!-- Advanced Crawl Settings -->
                <div class="pt-4 border-t border-slate-800 space-y-4">
                    <h3 class="text-sm font-semibold text-white flex items-center space-x-2">
                        <span>{{ t('projects.crawl_settings') }}</span>
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">{{ t('projects.crawl_depth') }} (1 - 8)</label>
                            <input
                                v-model.number="form.crawl_settings.max_depth"
                                type="number"
                                min="1"
                                max="8"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs text-slate-400 mb-1">{{ t('projects.max_pages') }}</label>
                            <input
                                v-model.number="form.crawl_settings.max_pages"
                                type="number"
                                min="5"
                                max="5000"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500"
                            />
                        </div>
                    </div>

                    <!-- Crawl Depth Explanatory Guide Card -->
                    <div class="p-4 rounded-2xl bg-indigo-950/30 border border-indigo-500/20 space-y-3">
                        <div class="flex items-center space-x-2 text-indigo-400 font-semibold text-xs">
                            <Layers class="w-4 h-4" />
                            <span>{{ t('projects.click_depth_guide') }}</span>
                        </div>
                        <p class="text-[11px] text-slate-300 leading-relaxed">
                            {{ t('projects.click_depth_desc') }}
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-[11px]">
                            <div class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800/80">
                                <span class="font-bold text-emerald-400 block mb-0.5">{{ t('projects.depth_1_2') }}</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-slate-900/80 border border-indigo-500/30">
                                <span class="font-bold text-indigo-300 block mb-0.5">{{ t('projects.depth_3') }}</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800/80">
                                <span class="font-bold text-amber-400 block mb-0.5">{{ t('projects.depth_4_8') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-4 pt-2">
                        <label class="flex items-center space-x-2 cursor-pointer text-xs text-slate-300">
                            <input
                                v-model="form.crawl_settings.respect_robots"
                                type="checkbox"
                                class="rounded bg-slate-800 border-slate-700 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span>{{ t('projects.respect_robots') }}</span>
                        </label>

                        <label class="flex items-center space-x-2 cursor-pointer text-xs text-slate-300">
                            <input
                                v-model="form.crawl_settings.follow_subdomains"
                                type="checkbox"
                                class="rounded bg-slate-800 border-slate-700 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span>{{ t('projects.follow_subdomains') }}</span>
                        </label>
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-end space-x-3">
                    <Link
                        :href="`/projects/${project.id}`"
                        class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold"
                    >
                        {{ t('projects.cancel') }}
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 flex items-center space-x-1.5 transition-all disabled:opacity-50"
                    >
                        <Save class="w-4 h-4" />
                        <span>{{ t('projects.save_changes') }}</span>
                    </button>
                </div>
            </form>

            <!-- Danger Zone Card -->
            <div class="p-8 rounded-3xl bg-rose-950/20 border border-rose-900/40 shadow-xl space-y-4">
                <div class="flex items-start justify-between flex-col sm:flex-row gap-4">
                    <div>
                        <div class="flex items-center space-x-2 text-rose-400 font-bold text-sm">
                            <AlertTriangle class="w-4 h-4 text-rose-500" />
                            <span>{{ t('projects.danger_zone') }}</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1 max-w-xl">
                            {{ t('projects.danger_zone_desc') }}
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="openDeleteModal"
                        class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-semibold shadow-lg shadow-rose-600/20 flex items-center space-x-2 transition-all shrink-0"
                    >
                        <Trash2 class="w-4 h-4" />
                        <span>{{ t('projects.delete_project') }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div
            v-if="showDeleteModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
        >
            <div class="w-full max-w-md rounded-3xl bg-slate-900 border border-slate-800 p-6 shadow-2xl space-y-5 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center space-x-3 text-rose-400">
                    <div class="w-10 h-10 rounded-2xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center">
                        <AlertTriangle class="w-5 h-5 text-rose-500" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">{{ t('projects.delete_confirm_title') }}</h3>
                        <p class="text-xs text-slate-400">{{ project.name }} ({{ project.domain }})</p>
                    </div>
                </div>

                <p class="text-xs text-slate-300 leading-relaxed">
                    {{ t('projects.delete_confirm_desc') }}
                </p>

                <div class="space-y-1.5">
                    <label class="block text-[11px] text-slate-400">
                        {{ t('projects.type_to_confirm') }} <strong class="text-white">{{ project.name }}</strong>
                    </label>
                    <input
                        v-model="deleteConfirmText"
                        type="text"
                        :placeholder="project.name"
                        class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-600 focus:outline-none focus:border-rose-500"
                    />
                </div>

                <div class="flex items-center justify-end space-x-3 pt-2">
                    <button
                        type="button"
                        @click="closeDeleteModal"
                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition-colors"
                    >
                        {{ t('projects.cancel') }}
                    </button>
                    <button
                        type="button"
                        @click="confirmDelete"
                        :disabled="deleteConfirmText.trim().toLowerCase() !== project.name.trim().toLowerCase() || isDeleting"
                        class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-semibold shadow-lg shadow-rose-600/30 flex items-center space-x-1.5 transition-all"
                    >
                        <Trash2 class="w-3.5 h-3.5" />
                        <span>{{ t('projects.delete') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
