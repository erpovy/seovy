<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Globe,
    Plus,
    Search,
    ChevronRight,
    Activity,
    Clock,
    Archive
} from 'lucide-vue-next';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps<{
    projects: {
        data: Array<any>;
        links: Array<any>;
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: {
        search?: string;
        archived?: boolean;
    };
}>();

const search = ref(props.filters.search || '');

const handleSearch = () => {
    router.get('/projects', { search: search.value }, { preserveState: true, replace: true });
};
</script>

<template>
    <AppLayout :title="t('projects.title')">
        <Head :title="t('projects.title')" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-white">{{ t('projects.title') }}</h1>
                    <p class="text-sm text-slate-400 mt-1">
                        {{ t('projects.subtitle') }} ({{ projects.total }}).
                    </p>
                </div>
                <div>
                    <Link
                        href="/projects/create"
                        class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold shadow-lg shadow-indigo-600/30 transition-all"
                    >
                        <Plus class="w-4 h-4" />
                        <span>{{ t('projects.add_website') }}</span>
                    </Link>
                </div>
            </div>

            <!-- Search & Filters -->
            <div class="flex items-center justify-between gap-4">
                <div class="relative flex-1 max-w-md">
                    <Search class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
                    <input
                        v-model="search"
                        @keyup.enter="handleSearch"
                        type="text"
                        :placeholder="t('common.search') + '...'"
                        class="w-full pl-10 pr-4 py-2 bg-slate-900/60 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500"
                    />
                </div>
            </div>

            <!-- Projects Grid -->
            <div v-if="projects.data.length === 0" class="p-12 rounded-3xl bg-slate-900/40 border border-slate-800 text-center">
                <Globe class="w-12 h-12 text-slate-600 mx-auto mb-3" />
                <h3 class="text-base font-semibold text-white">{{ t('dashboard.no_audits') }}</h3>
                <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1 mb-4">
                    {{ t('projects.subtitle') }}
                </p>
                <Link
                    href="/projects/create"
                    class="inline-flex items-center space-x-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold"
                >
                    <Plus class="w-3.5 h-3.5" />
                    <span>{{ t('projects.add_website') }}</span>
                </Link>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="project in projects.data"
                    :key="project.id"
                    class="p-5 rounded-3xl bg-slate-900/60 border border-slate-800/80 hover:border-slate-700 transition-all flex flex-col justify-between group"
                >
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-600/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold text-sm group-hover:scale-105 transition-transform">
                                {{ project.name.substring(0, 2).toUpperCase() }}
                            </div>
                            <span
                                v-if="project.latest_crawl?.health_score !== undefined"
                                class="text-xs px-2.5 py-1 rounded-full font-bold"
                                :class="project.latest_crawl.health_score >= 80 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : (project.latest_crawl.health_score >= 50 ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20')"
                            >
                                {{ project.latest_crawl.health_score }}%
                            </span>
                            <span v-else class="text-[11px] text-slate-500">{{ t('common.pending') }}</span>
                        </div>

                        <Link :href="`/projects/${project.id}`" class="block font-bold text-base text-white hover:text-indigo-400 transition-colors truncate">
                            {{ project.name }}
                        </Link>
                        <div class="text-xs text-slate-400 mt-1 truncate">{{ project.domain }}</div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-800/80 flex items-center justify-between text-xs">
                        <span class="text-slate-500">{{ project.target_country }} &bull; {{ project.target_language }}</span>
                        <Link :href="`/projects/${project.id}`" class="text-indigo-400 font-semibold flex items-center space-x-1 group-hover:translate-x-1 transition-transform">
                            <span>{{ t('common.view') }}</span>
                            <ChevronRight class="w-3.5 h-3.5" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
