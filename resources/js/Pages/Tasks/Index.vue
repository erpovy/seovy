<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    CheckSquare,
    Plus,
    Filter,
    Clock,
    User,
    AlertCircle,
    ArrowLeft,
    CheckCircle2
} from 'lucide-vue-next';

const props = defineProps<{
    project: any;
    tasks: {
        data: Array<any>;
        links: Array<any>;
        total: number;
    };
    members: Array<any>;
    filters: {
        status?: string;
        priority?: string;
    };
}>();

const showCreateModal = ref(false);

const form = useForm({
    title: '',
    description: '',
    priority: 'medium',
    assigned_to: null as number | null,
    due_date: '',
});

const submitTask = () => {
    form.post(`/projects/${props.project.id}/tasks`, {
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        },
    });
};

const updateStatus = (task: any, newStatus: string) => {
    router.patch(`/projects/${props.project.id}/tasks/${task.id}`, {
        status: newStatus,
    }, { preserveScroll: true });
};
</script>

<template>
    <AppLayout :title="`${$t('tasks.page_title')} - ${project.name}`">
        <Head :title="`${$t('tasks.page_title')} - ${project.name}`" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center space-x-3">
                    <Link :href="`/projects/${project.id}`" class="p-2 rounded-xl bg-slate-900 border border-slate-800 text-slate-400 hover:text-white transition-colors">
                        <ArrowLeft class="w-4 h-4" />
                    </Link>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-white">{{ $t('tasks.title') }}</h1>
                        <p class="text-xs text-slate-400 mt-0.5">
                            {{ $t('tasks.subtitle', { total: tasks.total }) }}
                        </p>
                    </div>
                </div>

                <div>
                    <button
                        @click="showCreateModal = true"
                        class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 flex items-center space-x-1.5 transition-all"
                    >
                        <Plus class="w-4 h-4" />
                        <span>{{ $t('tasks.add_task') }}</span>
                    </button>
                </div>
            </div>

            <!-- Task List -->
            <div v-if="tasks.data.length === 0" class="p-12 rounded-3xl bg-slate-900/40 border border-slate-800 text-center text-xs text-slate-500">
                <CheckSquare class="w-12 h-12 text-slate-600 mx-auto mb-3" />
                <h3 class="text-sm font-semibold text-white">{{ $t('tasks.no_tasks') }}</h3>
                <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1 mb-4">
                    {{ $t('tasks.no_tasks_desc') }}
                </p>
                <button
                    @click="showCreateModal = true"
                    class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs"
                >
                    {{ $t('tasks.create_first_task') }}
                </button>
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="task in tasks.data"
                    :key="task.id"
                    class="p-5 rounded-2xl bg-slate-900/50 border border-slate-800/80 hover:border-slate-700 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                >
                    <div class="space-y-1 min-w-0">
                        <div class="flex items-center space-x-2">
                            <span
                                class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider"
                                :class="task.priority === 'critical' ? 'bg-rose-500/15 text-rose-400' : (task.priority === 'high' ? 'bg-amber-500/15 text-amber-400' : 'bg-slate-800 text-slate-400')"
                            >
                                {{ task.priority === 'critical' ? $t('tasks.priority_critical') : (task.priority === 'high' ? $t('tasks.priority_high') : (task.priority === 'medium' ? $t('tasks.priority_medium') : $t('tasks.priority_low'))) }}
                            </span>
                            <span class="text-xs text-slate-500 font-mono">#G-{{ task.id }}</span>
                        </div>
                        <h3 class="text-sm font-bold text-white truncate">{{ task.title }}</h3>
                        <p v-if="task.description" class="text-xs text-slate-400 line-clamp-2">{{ task.description }}</p>
                    </div>

                    <div class="flex items-center space-x-3 shrink-0">
                        <select
                            :value="task.status"
                            @change="updateStatus(task, ($event.target as any).value)"
                            class="px-3 py-1.5 rounded-xl bg-slate-950 border border-slate-800 text-xs font-semibold text-white focus:ring-1 focus:ring-indigo-500"
                        >
                            <option value="open">{{ $t('tasks.status_open') }}</option>
                            <option value="in_progress">{{ $t('tasks.status_in_progress') }}</option>
                            <option value="completed">{{ $t('tasks.status_completed') }}</option>
                            <option value="ignored">{{ $t('tasks.status_ignored') }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Create Modal -->
            <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
                <div class="w-full max-w-md bg-slate-900 border border-slate-800 p-6 rounded-3xl space-y-4 shadow-2xl">
                    <h2 class="text-base font-bold text-white">{{ $t('tasks.modal_title') }}</h2>

                    <form @submit.prevent="submitTask" class="space-y-3 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">{{ $t('tasks.task_title_label') }}</label>
                            <input
                                v-model="form.title"
                                type="text"
                                required
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:ring-1 focus:ring-indigo-500"
                                :placeholder="$t('tasks.task_title_placeholder')"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">{{ $t('tasks.description_label') }}</label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:ring-1 focus:ring-indigo-500"
                                :placeholder="$t('tasks.description_placeholder')"
                            ></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-300 mb-1">{{ $t('tasks.priority_label') }}</label>
                                <select v-model="form.priority" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                                    <option value="low">{{ $t('tasks.priority_low') }}</option>
                                    <option value="medium">{{ $t('tasks.priority_medium') }}</option>
                                    <option value="high">{{ $t('tasks.priority_high') }}</option>
                                    <option value="critical">{{ $t('tasks.priority_critical') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-300 mb-1">{{ $t('tasks.assignee_label') }}</label>
                                <select v-model="form.assigned_to" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                                    <option :value="null">{{ $t('tasks.unassigned') }}</option>
                                    <option v-for="m in members" :key="m.id" :value="m.id">{{ m.name }}</option>
                                </select>
                            </div>
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
                                {{ $t('common.save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
