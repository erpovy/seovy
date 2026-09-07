<script setup lang="ts">
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import {
    CreditCard,
    Plus,
    Check,
    Settings,
    Trash2,
    Sparkles,
    Shield,
    Layers,
    Globe,
    Search,
    Users,
    Tag,
    X
} from 'lucide-vue-next';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps<{
    plansList: Array<any>;
    stats: any;
}>();

// Modal state
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingPlanId = ref<number | null>(null);

const form = useForm({
    code: '',
    name: '',
    price: 0,
    currency: 'TRY',
    featuresText: '',
    limits: {
        max_projects: 1,
        max_pages_monthly: 500,
        max_keywords: 10,
        team_members: 1,
    },
    is_popular: false,
    is_active: true,
});

const openCreateModal = () => {
    isEditing.value = false;
    editingPlanId.value = null;
    form.reset();
    form.code = '';
    form.name = '';
    form.price = 299;
    form.currency = 'TRY';
    form.featuresText = "10 Proje / Web Sitesi\nAylık 50.000 Taranan Sayfa\n250 Anahtar Kelime Sıra Takibi\n10 Ekip Üyesi\nPDF Raporlama";
    form.limits = {
        max_projects: 10,
        max_pages_monthly: 50000,
        max_keywords: 250,
        team_members: 10,
    };
    form.is_popular = false;
    form.is_active = true;
    isModalOpen.value = true;
};

const openEditModal = (plan: any) => {
    isEditing.value = true;
    editingPlanId.value = plan.id;
    form.code = plan.code;
    form.name = plan.name;
    form.price = parseFloat(plan.price) || 0;
    form.currency = plan.currency || 'TRY';
    form.featuresText = Array.isArray(plan.features) ? plan.features.join('\n') : '';
    form.limits = {
        max_projects: plan.limits?.max_projects ?? 1,
        max_pages_monthly: plan.limits?.max_pages_monthly ?? 500,
        max_keywords: plan.limits?.max_keywords ?? 10,
        team_members: plan.limits?.team_members ?? 1,
    };
    form.is_popular = !!plan.is_popular;
    form.is_active = !!plan.is_active;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    editingPlanId.value = null;
};

const submitForm = () => {
    const featuresArray = form.featuresText
        .split('\n')
        .map(f => f.trim())
        .filter(f => f.length > 0);

    const payload = {
        code: form.code,
        name: form.name,
        price: form.price,
        currency: form.currency,
        features: featuresArray,
        limits: form.limits,
        is_popular: form.is_popular,
        is_active: form.is_active,
    };

    if (isEditing.value && editingPlanId.value) {
        router.patch(`/admin/plans/${editingPlanId.value}`, payload, {
            onSuccess: () => closeModal(),
        });
    } else {
        router.post('/admin/plans', payload, {
            onSuccess: () => closeModal(),
        });
    }
};

const deletePlan = (plan: any) => {
    if (confirm(`"${plan.name}" planını silmek istediğinize emin misiniz?`)) {
        router.delete(`/admin/plans/${plan.id}`);
    }
};
</script>

<template>
    <div class="space-y-6">
        <!-- Top Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-white flex items-center space-x-2">
                    <CreditCard class="w-5 h-5 text-indigo-400" />
                    <span>{{ $t('admin.plans_management_title') }}</span>
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">
                    {{ $t('admin.plans_management_subtitle') }}
                </p>
            </div>

            <button
                type="button"
                @click="openCreateModal"
                class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-lg shadow-indigo-600/30 flex items-center space-x-2 transition-all shrink-0"
            >
                <Plus class="w-4 h-4" />
                <span>{{ $t('admin.plans_create_new') }}</span>
            </button>
        </div>

        <!-- Plans Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div
                v-for="plan in plansList"
                :key="plan.id"
                class="p-6 sm:p-7 rounded-3xl bg-slate-900/60 border transition-all flex flex-col justify-between group relative overflow-hidden"
                :class="plan.is_popular ? 'border-indigo-500/60 shadow-xl shadow-indigo-500/10 bg-indigo-950/20' : 'border-slate-800/80 hover:border-slate-700'"
            >
                <!-- Popular Badge -->
                <div v-if="plan.is_popular" class="absolute -right-12 top-6 bg-indigo-600 text-white text-[10px] font-extrabold uppercase px-12 py-1 rotate-45 shadow-lg tracking-wider">
                    {{ $t('admin.plans_popular') }}
                </div>

                <div class="space-y-5">
                    <div class="flex items-center justify-between pr-10">
                        <div>
                            <span class="text-[10px] font-mono uppercase tracking-wider text-slate-500 font-bold">#{{ plan.code }}</span>
                            <h3 class="text-xl font-bold text-white mt-0.5">{{ plan.name }}</h3>
                        </div>
                        <span
                            class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase"
                            :class="plan.is_active ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-800 text-slate-500 border border-slate-700'"
                        >
                            {{ plan.is_active ? $t('admin.pos_active') : $t('admin.pos_inactive') }}
                        </span>
                    </div>

                    <!-- Price -->
                    <div class="flex items-baseline space-x-1.5">
                        <span class="text-3xl font-extrabold text-white font-mono">
                            {{ plan.price == 0 ? '0' : Number(plan.price).toLocaleString() }}
                        </span>
                        <span class="text-sm font-bold text-slate-400">
                            {{ plan.currency === 'TRY' ? '₺' : plan.currency }} / {{ $t('admin.plans_month') }}
                        </span>
                    </div>

                    <!-- Limits & Quotas Box -->
                    <div class="p-3.5 rounded-2xl bg-slate-950/70 border border-slate-800/80 space-y-2 text-xs">
                        <div class="text-[10px] font-bold text-indigo-400 uppercase tracking-wider mb-1">
                            {{ $t('admin.plans_quotas_title') }}
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div class="flex items-center space-x-1.5 text-slate-300">
                                <Globe class="w-3.5 h-3.5 text-cyan-400 shrink-0" />
                                <span>{{ plan.limits?.max_projects || 1 }} {{ $t('admin.plans_limit_projects') }}</span>
                            </div>
                            <div class="flex items-center space-x-1.5 text-slate-300">
                                <Search class="w-3.5 h-3.5 text-violet-400 shrink-0" />
                                <span>{{ (plan.limits?.max_pages_monthly || 500).toLocaleString() }} {{ $t('admin.plans_limit_pages') }}</span>
                            </div>
                            <div class="flex items-center space-x-1.5 text-slate-300">
                                <Tag class="w-3.5 h-3.5 text-emerald-400 shrink-0" />
                                <span>{{ plan.limits?.max_keywords || 10 }} {{ $t('admin.plans_limit_keywords') }}</span>
                            </div>
                            <div class="flex items-center space-x-1.5 text-slate-300">
                                <Users class="w-3.5 h-3.5 text-amber-400 shrink-0" />
                                <span>{{ plan.limits?.team_members || 1 }} {{ $t('admin.plans_limit_members') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Feature Bullet Points -->
                    <div class="space-y-2 pt-2 border-t border-slate-800/60 text-xs text-slate-300">
                        <div v-for="(feat, idx) in plan.features" :key="idx" class="flex items-start space-x-2">
                            <Check class="w-3.5 h-3.5 text-emerald-400 shrink-0 mt-0.5" />
                            <span class="leading-relaxed">{{ feat }}</span>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="pt-6 mt-6 border-t border-slate-800/80 flex items-center justify-between">
                    <button
                        type="button"
                        @click="openEditModal(plan)"
                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold flex items-center space-x-1.5 transition-colors"
                    >
                        <Settings class="w-3.5 h-3.5" />
                        <span>{{ $t('admin.plans_edit_btn') }}</span>
                    </button>

                    <button
                        v-if="!['free', 'pro', 'agency'].includes(plan.code)"
                        type="button"
                        @click="deletePlan(plan)"
                        class="p-2 rounded-xl text-slate-500 hover:text-rose-400 hover:bg-rose-500/10 transition-colors"
                        title="Planı Sil"
                    >
                        <Trash2 class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Plan Create / Edit Modal -->
        <div
            v-if="isModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-in fade-in duration-150"
        >
            <div class="w-full max-w-xl rounded-3xl bg-slate-900 border border-slate-800 p-6 sm:p-8 shadow-2xl space-y-6 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
                            <CreditCard class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white">
                                {{ isEditing ? $t('admin.plans_modal_edit_title') : $t('admin.plans_modal_create_title') }}
                            </h3>
                            <p class="text-xs text-slate-400">
                                {{ $t('admin.plans_modal_desc') }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="closeModal"
                        class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="space-y-4 text-xs">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1.5">{{ $t('admin.plans_input_name') }}</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500"
                                placeholder="Örn: Kurumsal Plus"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-300 mb-1.5">{{ $t('admin.plans_input_code') }}</label>
                            <input
                                v-model="form.code"
                                type="text"
                                required
                                :disabled="isEditing"
                                class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white font-mono disabled:opacity-50 focus:outline-none focus:border-indigo-500"
                                placeholder="kurumsal_plus"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1.5">{{ $t('admin.plans_input_price') }}</label>
                            <input
                                v-model.number="form.price"
                                type="number"
                                min="0"
                                step="0.01"
                                required
                                class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white font-mono focus:outline-none focus:border-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-300 mb-1.5">{{ $t('admin.plans_input_currency') }}</label>
                            <select
                                v-model="form.currency"
                                class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500"
                            >
                                <option value="TRY">TRY (₺ Türk Lirası)</option>
                                <option value="USD">USD ($ Amerikan Doları)</option>
                                <option value="EUR">EUR (€ Euro)</option>
                                <option value="BRL">BRL (R$ Brezilya Reali)</option>
                                <option value="RUB">RUB (₽ Rus Rublesi)</option>
                                <option value="SAR">SAR (﷼ Suudi Riyali)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Quotas / Limits Section -->
                    <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 space-y-3">
                        <span class="font-bold text-indigo-400 uppercase tracking-wider text-[11px] block">
                            {{ $t('admin.plans_quotas_header') }}
                        </span>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-slate-400 mb-1">{{ $t('admin.plans_limit_projects_label') }}</label>
                                <input
                                    v-model.number="form.limits.max_projects"
                                    type="number"
                                    min="1"
                                    required
                                    class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-white"
                                />
                            </div>

                            <div>
                                <label class="block text-slate-400 mb-1">{{ $t('admin.plans_limit_pages_label') }}</label>
                                <input
                                    v-model.number="form.limits.max_pages_monthly"
                                    type="number"
                                    min="10"
                                    step="100"
                                    required
                                    class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-white"
                                />
                            </div>

                            <div>
                                <label class="block text-slate-400 mb-1">{{ $t('admin.plans_limit_keywords_label') }}</label>
                                <input
                                    v-model.number="form.limits.max_keywords"
                                    type="number"
                                    min="0"
                                    required
                                    class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-white"
                                />
                            </div>

                            <div>
                                <label class="block text-slate-400 mb-1">{{ $t('admin.plans_limit_members_label') }}</label>
                                <input
                                    v-model.number="form.limits.team_members"
                                    type="number"
                                    min="1"
                                    required
                                    class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-white"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Features bullets -->
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1.5">
                            {{ $t('admin.plans_features_label') }}
                            <span class="text-[10px] text-slate-500 font-normal">({{ $t('admin.plans_features_help') }})</span>
                        </label>
                        <textarea
                            v-model="form.featuresText"
                            rows="5"
                            class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-sans text-xs focus:outline-none focus:border-indigo-500"
                            placeholder="Her satıra bir özellik maddesi yazın..."
                        ></textarea>
                    </div>

                    <!-- Toggles -->
                    <div class="flex items-center space-x-6 pt-2">
                        <label class="flex items-center space-x-2 cursor-pointer text-slate-300">
                            <input
                                v-model="form.is_popular"
                                type="checkbox"
                                class="rounded bg-slate-800 border-slate-700 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span>{{ $t('admin.plans_mark_popular') }}</span>
                        </label>

                        <label class="flex items-center space-x-2 cursor-pointer text-slate-300">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="rounded bg-slate-800 border-slate-700 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span>{{ $t('admin.plans_mark_active') }}</span>
                        </label>
                    </div>

                    <div class="pt-4 border-t border-slate-800 flex items-center justify-end space-x-3">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold"
                        >
                            {{ $t('common.cancel') }}
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold shadow-lg shadow-indigo-600/30 flex items-center space-x-1.5"
                        >
                            <Check class="w-4 h-4" />
                            <span>{{ $t('admin.plans_save_btn') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
