<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Users,
    UserPlus,
    Mail,
    Shield,
    Trash2,
    Copy,
    Check,
    Clock
} from 'lucide-vue-next';

const props = defineProps<{
    workspace: any;
    members: Array<any>;
    invitations: Array<any>;
    canManage: boolean;
}>();

const showInviteModal = ref(false);

const inviteForm = useForm({
    email: '',
    role: 'specialist',
});

const submitInvite = () => {
    inviteForm.post(`/workspaces/${props.workspace.id}/members/invite`, {
        onSuccess: () => {
            showInviteModal.value = false;
            inviteForm.reset();
        },
    });
};

const updateRole = (memberId: number, newRole: string) => {
    router.patch(`/workspaces/${props.workspace.id}/members/${memberId}`, {
        role: newRole,
    }, { preserveScroll: true });
};

const removeMember = (memberId: number) => {
    if (confirm('Are you sure you want to remove this user from the workspace?')) {
        router.delete(`/workspaces/${props.workspace.id}/members/${memberId}`, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AppLayout :title="`${$t('members.page_title')} - ${workspace.name}`">
        <Head :title="`${$t('members.page_title')} - ${workspace.name}`" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-white">{{ $t('members.title') }}</h1>
                    <p class="text-sm text-slate-400 mt-1">
                        {{ $t('members.subtitle', { workspace: workspace.name }) }}
                    </p>
                </div>

                <button
                    v-if="canManage"
                    @click="showInviteModal = true"
                    class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 flex items-center space-x-1.5 transition-all"
                >
                    <UserPlus class="w-4 h-4" />
                    <span>{{ $t('members.invite_button') }}</span>
                </button>
            </div>

            <!-- Members Table -->
            <div class="rounded-3xl bg-slate-900/50 border border-slate-800/80 overflow-hidden">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="p-4">{{ $t('members.user_th') }}</th>
                            <th class="p-4">{{ $t('members.email_th') }}</th>
                            <th class="p-4">{{ $t('members.role_th') }}</th>
                            <th class="p-4 text-right" v-if="canManage">{{ $t('members.actions_th') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <tr v-for="m in members" :key="m.id" class="hover:bg-slate-800/30 transition-colors">
                            <td class="p-4 font-bold text-white flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center font-bold text-indigo-400 text-xs">
                                    {{ m.name.charAt(0).toUpperCase() }}
                                </div>
                                <span>{{ m.name }}</span>
                            </td>
                            <td class="p-4 text-slate-400">{{ m.email }}</td>
                            <td class="p-4">
                                <select
                                    v-if="canManage && !m.is_owner"
                                    :value="m.role"
                                    @change="updateRole(m.id, ($event.target as any).value)"
                                    class="px-3 py-1.5 rounded-xl bg-slate-950 border border-slate-800 text-xs font-medium text-white focus:ring-1 focus:ring-indigo-500"
                                >
                                    <option value="admin">{{ $t('members.admin_role') }}</option>
                                    <option value="specialist">{{ $t('members.specialist_role') }}</option>
                                    <option value="viewer">{{ $t('members.viewer_role') }}</option>
                                </select>
                                <span
                                    v-else
                                    class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-500/10 text-indigo-400 border border-indigo-500/20"
                                >
                                    {{ m.role }}
                                </span>
                            </td>
                            <td class="p-4 text-right" v-if="canManage">
                                <button
                                    v-if="!m.is_owner"
                                    @click="removeMember(m.id)"
                                    class="text-rose-400 hover:text-rose-300 font-semibold text-xs"
                                >
                                    {{ $t('members.remove_button') }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pending Invitations -->
            <div v-if="invitations.length > 0" class="space-y-3 pt-4">
                <h2 class="text-sm font-bold text-white flex items-center space-x-2">
                    <Clock class="w-4 h-4 text-amber-400" />
                    <span>{{ $t('members.pending_invites') }}</span>
                </h2>

                <div class="space-y-2">
                    <div
                        v-for="inv in invitations"
                        :key="inv.id"
                        class="p-4 rounded-2xl bg-slate-900/40 border border-slate-800 flex items-center justify-between text-xs"
                    >
                        <div class="flex items-center space-x-3">
                            <Mail class="w-4 h-4 text-slate-500" />
                            <span class="font-medium text-white">{{ inv.email }}</span>
                            <span class="text-slate-500">({{ inv.role }})</span>
                        </div>
                        <div class="text-[11px] text-amber-400">
                            {{ $t('members.expires_at') }} {{ new Date(inv.expires_at).toLocaleDateString() }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invite Modal -->
            <div v-if="showInviteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
                <div class="w-full max-w-md bg-slate-900 border border-slate-800 p-6 rounded-3xl space-y-4 shadow-2xl">
                    <h2 class="text-base font-bold text-white">{{ $t('members.invite_modal_title') }}</h2>
                    <form @submit.prevent="submitInvite" class="space-y-3 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">{{ $t('members.email_label') }}</label>
                            <input
                                v-model="inviteForm.email"
                                type="email"
                                required
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white"
                                :placeholder="$t('members.email_placeholder')"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">{{ $t('members.role_label') }}</label>
                            <select v-model="inviteForm.role" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                                <option value="admin">{{ $t('members.role_admin_desc') }}</option>
                                <option value="specialist">{{ $t('members.role_specialist_desc') }}</option>
                                <option value="viewer">{{ $t('members.role_viewer_desc') }}</option>
                            </select>
                        </div>

                        <div class="pt-3 flex items-center justify-end space-x-2">
                            <button
                                type="button"
                                @click="showInviteModal = false"
                                class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 hover:text-white"
                            >
                                {{ $t('common.cancel') }}
                            </button>
                            <button
                                type="submit"
                                :disabled="inviteForm.processing"
                                class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold"
                            >
                                {{ $t('members.send_invite') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
