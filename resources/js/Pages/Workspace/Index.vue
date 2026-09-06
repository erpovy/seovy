<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Users,
    Plus,
    Download,
    Check,
    Briefcase,
    Shield,
    Trash2
} from 'lucide-vue-next';

const props = defineProps<{
    workspaces: Array<any>;
    currentWorkspace: any;
}>();

const showCreateModal = ref(false);

const form = useForm({
    name: '',
});

const submitWorkspace = () => {
    form.post('/workspaces', {
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        },
    });
};

const switchWs = (id: number) => {
    router.post(`/workspaces/${id}/switch`);
};
</script>

<template>
    <AppLayout :title="$t('workspaces.page_title')">
        <Head :title="$t('workspaces.page_title')" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-white">{{ $t('workspaces.title') }}</h1>
                    <p class="text-sm text-slate-400 mt-1">
                        {{ $t('workspaces.subtitle') }}
                    </p>
                </div>

                <button
                    @click="showCreateModal = true"
                    class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 flex items-center space-x-1.5 transition-all"
                >
                    <Plus class="w-4 h-4" />
                    <span>{{ $t('workspaces.create_button') }}</span>
                </button>
            </div>

            <!-- Workspace Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="ws in workspaces"
                    :key="ws.id"
                    class="p-6 rounded-3xl bg-slate-900/50 border border-slate-800/80 hover:border-slate-700 transition-all flex flex-col justify-between"
                    :class="{'border-indigo-500/50 bg-indigo-500/5': ws.id === currentWorkspace?.id}"
                >
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-2xl bg-slate-800 flex items-center justify-center font-bold text-white text-lg">
                                {{ ws.name.substring(0, 2).toUpperCase() }}
                            </div>
                            <span
                                v-if="ws.id === currentWorkspace?.id"
                                class="px-2.5 py-0.5 rounded-full bg-indigo-500/15 text-indigo-400 border border-indigo-500/30 text-[10px] font-bold"
                            >
                                {{ $t('workspaces.active_badge') }}
                            </span>
                        </div>

                        <h3 class="text-base font-bold text-white">{{ ws.name }}</h3>
                        <p class="text-xs text-slate-500 font-mono mt-0.5 truncate">{{ ws.slug }}</p>

                        <div class="mt-4 pt-4 border-t border-slate-800/60 grid grid-cols-2 gap-2 text-xs text-slate-400">
                            <div>{{ $t('workspaces.projects_stat') }} <span class="font-bold text-white">{{ ws.projects_count }}</span></div>
                            <div>{{ $t('workspaces.members_stat') }} <span class="font-bold text-white">{{ ws.users_count }}</span></div>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-800/60 flex items-center justify-between">
                        <a
                            :href="`/workspaces/${ws.id}/export`"
                            class="text-xs text-slate-400 hover:text-white flex items-center space-x-1"
                        >
                            <Download class="w-3.5 h-3.5" />
                            <span>{{ $t('workspaces.export_data') }}</span>
                        </a>

                        <button
                            v-if="ws.id !== currentWorkspace?.id"
                            @click="switchWs(ws.id)"
                            class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-white"
                        >
                            {{ $t('workspaces.switch_button') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Create Modal -->
            <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
                <div class="w-full max-w-md bg-slate-900 border border-slate-800 p-6 rounded-3xl space-y-4 shadow-2xl">
                    <h2 class="text-base font-bold text-white">{{ $t('workspaces.modal_title') }}</h2>
                    <form @submit.prevent="submitWorkspace" class="space-y-3 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">{{ $t('workspaces.name_label') }}</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white"
                                :placeholder="$t('workspaces.name_placeholder')"
                            />
                        </div>

                        <div class="pt-3 flex items-center justify-end space-x-2">
                            <button
                                type="button"
                                @click="showCreateModal = false"
                                class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 hover:text-white"
                            >
                                {{ $t('common.cancel') }}
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold"
                            >
                                {{ $t('workspaces.create_submit') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
