<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';
import {
    Sparkles,
    Plus,
    Trash2,
    Check,
    RotateCcw,
    AlertCircle,
    Eye,
    EyeOff,
    Search,
    ShieldCheck,
    Layers,
    BarChart3,
    CreditCard,
    Zap,
    Globe,
    Cpu,
    FileText,
    Lock,
    Activity,
    ArrowUp,
    ArrowDown
} from 'lucide-vue-next';

interface FeatureItem {
    id: string;
    title: string;
    description: string;
    icon?: string;
    color?: string;
    badge?: string;
    is_active?: boolean;
}

const props = defineProps<{
    systemSettings?: {
        features_badge?: string | null;
        features_title?: string | null;
        features_subtitle?: string | null;
        features_list?: FeatureItem[] | null;
    };
}>();

const { t } = useI18n();

const defaultBadge = 'Platform Özellikleri & Mimarisi';
const defaultTitle = 'Gelişmiş Teknik SEO & Tarama Özellikleri';
const defaultSubtitle = 'Kendi sunucunuzda çalışan, çoklu çalışma alanları, SSRF korumalı crawler ve 25+ teknik analiz kuralı içeren kurumsal platform.';

const currentBadge = computed(() => props.systemSettings?.features_badge || defaultBadge);
const currentTitle = computed(() => props.systemSettings?.features_title || defaultTitle);
const currentSubtitle = computed(() => props.systemSettings?.features_subtitle || defaultSubtitle);

const form = useForm({
    features_badge: currentBadge.value,
    features_title: currentTitle.value,
    features_subtitle: currentSubtitle.value,
    features_list: JSON.parse(JSON.stringify(props.systemSettings?.features_list || [])),
});

const isSaving = ref(false);
const isResetting = ref(false);

const availableIcons = [
    { name: 'Search', label: 'Tarayıcı / Arama', icon: Search },
    { name: 'ShieldCheck', label: 'Güvenlik / Doğrulama', icon: ShieldCheck },
    { name: 'Layers', label: 'Çalışma Alanı / Katman', icon: Layers },
    { name: 'BarChart3', label: 'Grafik / Sıra Takibi', icon: BarChart3 },
    { name: 'Sparkles', label: 'Yapay Zeka (AI)', icon: Sparkles },
    { name: 'CreditCard', label: 'Ödeme / Faturalandırma', icon: CreditCard },
    { name: 'Zap', label: 'Hız / Performans', icon: Zap },
    { name: 'Globe', label: 'Web / Domain', icon: Globe },
    { name: 'Cpu', label: 'Motor / Altyapı', icon: Cpu },
    { name: 'FileText', label: 'Rapor / Denetim', icon: FileText },
    { name: 'Lock', label: 'İzole / Şifreleme', icon: Lock },
    { name: 'Activity', label: 'Sağlık / Durum', icon: Activity },
];

const availableColors = [
    { name: 'indigo', label: 'İndigo', bg: 'bg-indigo-500' },
    { name: 'violet', label: 'Mor', bg: 'bg-violet-500' },
    { name: 'cyan', label: 'Camgöbeği', bg: 'bg-cyan-500' },
    { name: 'emerald', label: 'Zümrüt Yeşili', bg: 'bg-emerald-500' },
    { name: 'amber', label: 'Kehribar Sarı', bg: 'bg-amber-500' },
    { name: 'pink', label: 'Pembe', bg: 'bg-pink-500' },
];

const addFeature = () => {
    form.features_list.push({
        id: 'f_' + Date.now().toString(36),
        title: 'Yeni Platform Özelliği',
        description: 'Bu özelliğin sağladığı faydayı ve teknik yeteneklerini açıklayan metin.',
        icon: 'Sparkles',
        color: 'indigo',
        badge: 'Yeni',
        is_active: true,
    });
};

const removeFeature = (index: number) => {
    form.features_list.splice(index, 1);
};

const moveUp = (index: number) => {
    if (index > 0) {
        const item = form.features_list.splice(index, 1)[0];
        form.features_list.splice(index - 1, 0, item);
    }
};

const moveDown = (index: number) => {
    if (index < form.features_list.length - 1) {
        const item = form.features_list.splice(index, 1)[0];
        form.features_list.splice(index + 1, 0, item);
    }
};

const toggleActive = (feature: FeatureItem) => {
    feature.is_active = !feature.is_active;
};

const submit = () => {
    isSaving.value = true;
    form.post('/admin/settings/features', {
        preserveScroll: true,
        onFinish: () => {
            isSaving.value = false;
        },
    });
};

const resetToDefaults = () => {
    if (!confirm(t('admin.features_confirm_reset', 'Tüm özellikleri ve başlıkları fabrika ayarlarına sıfırlamak istediğinize emin misiniz?'))) {
        return;
    }
    isResetting.value = true;
    router.post('/admin/settings/features/reset', {}, {
        preserveScroll: true,
        onFinish: () => {
            isResetting.value = false;
        },
    });
};
</script>

<template>
    <div class="bg-slate-900/60 backdrop-blur-xl border border-slate-800/80 rounded-3xl p-6 sm:p-8 space-y-8 shadow-xl">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800/80 pb-6">
            <div>
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 text-xs font-semibold mb-2">
                    <Sparkles class="w-3.5 h-3.5" />
                    <span>{{ t('admin.features_badge_label', 'Özellikler Sayfası Yönetimi') }}</span>
                </div>
                <h3 class="text-xl font-bold text-white tracking-tight">{{ t('admin.features_title', 'Landing Page & Özellikler Sayfası Özelleştirme') }}</h3>
                <p class="text-xs sm:text-sm text-slate-400 mt-1 max-w-2xl">
                    {{ t('admin.features_desc', 'Landing page ve /features sayfasındaki başlıkları, açıklamaları ve listelenen modül kartlarını yönetin.') }}
                </p>
            </div>

            <div class="flex items-center space-x-2 shrink-0">
                <button
                    type="button"
                    @click="resetToDefaults"
                    :disabled="isResetting || isSaving"
                    class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700/80 transition-all flex items-center space-x-1.5 cursor-pointer disabled:opacity-50"
                >
                    <RotateCcw class="w-3.5 h-3.5" :class="{ 'animate-spin': isResetting }" />
                    <span>{{ t('admin.features_btn_reset', 'Varsayılana Sıfırla') }}</span>
                </button>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Page Headers Configuration -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-950/60 p-5 rounded-2xl border border-slate-800/80">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        {{ t('admin.features_page_badge', 'Rozet / Üst Başlık (Badge)') }}
                    </label>
                    <input
                        v-model="form.features_badge"
                        type="text"
                        placeholder="Örn: Platform Özellikleri & Mimarisi"
                        class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        {{ t('admin.features_page_title', 'Ana Başlık (Title)') }}
                    </label>
                    <input
                        v-model="form.features_title"
                        type="text"
                        placeholder="Örn: Gelişmiş Teknik SEO & Tarama Özellikleri"
                        class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                    />
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        {{ t('admin.features_page_subtitle', 'Açıklama / Alt Başlık (Subtitle)') }}
                    </label>
                    <textarea
                        v-model="form.features_subtitle"
                        rows="2"
                        placeholder="Özellikler sayfasının üstünde yer alan tanıtım paragrafı..."
                        class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                    ></textarea>
                </div>
            </div>

            <!-- Features List Section -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-bold text-white uppercase tracking-wider">
                            {{ t('admin.features_cards_title', 'Özellik Kartları') }} ({{ form.features_list.length }})
                        </h4>
                        <p class="text-xs text-slate-400 mt-0.5">
                            {{ t('admin.features_cards_desc', 'Kartların sırasını değiştirebilir, aktif/pasif yapabilir veya yeni özellikler ekleyebilirsiniz.') }}
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="addFeature"
                        class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-md shadow-indigo-600/20 transition-all flex items-center space-x-1.5 cursor-pointer"
                    >
                        <Plus class="w-4 h-4" />
                        <span>{{ t('admin.features_btn_add', 'Yeni Özellik Ekle') }}</span>
                    </button>
                </div>

                <div v-if="form.features_list.length === 0" class="text-center py-10 rounded-2xl bg-slate-950/40 border border-dashed border-slate-800 text-slate-500 text-xs">
                    {{ t('admin.features_empty', 'Henüz hiçbir özellik tanımlanmamış. \'Yeni Özellik Ekle\' butonuna veya \'Varsayılana Sıfırla\'ya tıklayın.') }}
                </div>

                <div v-else class="space-y-4">
                    <div
                        v-for="(feature, idx) in form.features_list"
                        :key="feature.id || idx"
                        class="p-5 rounded-2xl bg-slate-950/70 border border-slate-800/80 transition-all space-y-4"
                        :class="{ 'opacity-60 border-slate-800/40': feature.is_active === false }"
                    >
                        <!-- Top Bar: Index, Order, Visibility, Delete -->
                        <div class="flex items-center justify-between border-b border-slate-800/60 pb-3">
                            <div class="flex items-center space-x-2">
                                <span class="w-6 h-6 rounded-lg bg-slate-800 text-slate-300 font-mono text-xs font-bold flex items-center justify-center">
                                    #{{ idx + 1 }}
                                </span>
                                <span class="text-xs font-semibold text-white truncate max-w-[200px] sm:max-w-xs">
                                    {{ feature.title || 'Başlıksız Özellik' }}
                                </span>
                                <span
                                    class="text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider"
                                    :class="feature.is_active !== false ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-800 text-slate-400'"
                                >
                                    {{ feature.is_active !== false ? t('admin.features_published', 'Yayında') : t('admin.features_hidden', 'Gizli') }}
                                </span>
                            </div>

                            <div class="flex items-center space-x-1.5">
                                <button
                                    type="button"
                                    @click="moveUp(idx)"
                                    :disabled="idx === 0"
                                    class="p-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white disabled:opacity-30 disabled:cursor-not-allowed"
                                    :title="t('admin.features_move_up', 'Yukarı Taşı')"
                                >
                                    <ArrowUp class="w-3.5 h-3.5" />
                                </button>
                                <button
                                    type="button"
                                    @click="moveDown(idx)"
                                    :disabled="idx === form.features_list.length - 1"
                                    class="p-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white disabled:opacity-30 disabled:cursor-not-allowed"
                                    :title="t('admin.features_move_down', 'Aşağı Taşı')"
                                >
                                    <ArrowDown class="w-3.5 h-3.5" />
                                </button>
                                <button
                                    type="button"
                                    @click="toggleActive(feature)"
                                    class="p-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white"
                                    :title="feature.is_active !== false ? t('admin.features_hide', 'Gizle') : t('admin.features_show', 'Göster')"
                                >
                                    <Eye v-if="feature.is_active !== false" class="w-3.5 h-3.5 text-emerald-400" />
                                    <EyeOff v-else class="w-3.5 h-3.5 text-slate-500" />
                                </button>
                                <button
                                    type="button"
                                    @click="removeFeature(idx)"
                                    class="p-1.5 rounded-lg bg-slate-900 hover:bg-rose-900/40 text-slate-400 hover:text-rose-400"
                                    :title="t('common.delete', 'Sil')"
                                >
                                    <Trash2 class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>

                        <!-- Fields Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            <div class="lg:col-span-2">
                                <label class="block text-[11px] font-semibold text-slate-400 mb-1">
                                    {{ t('admin.features_col_title', 'Özellik Başlığı') }}
                                </label>
                                <input
                                    v-model="feature.title"
                                    type="text"
                                    required
                                    placeholder="Örn: Güvenli ve Asenkron Tarayıcı"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-white focus:outline-none focus:border-indigo-500"
                                />
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-slate-400 mb-1">
                                    {{ t('admin.features_col_badge', 'Küçük Rozet (Opsiyonel)') }}
                                </label>
                                <input
                                    v-model="feature.badge"
                                    type="text"
                                    placeholder="Örn: Yüksek Hızlı"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-white focus:outline-none focus:border-indigo-500"
                                />
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-slate-400 mb-1">
                                    {{ t('admin.features_col_icon', 'İkon Seçimi') }}
                                </label>
                                <select
                                    v-model="feature.icon"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-white focus:outline-none focus:border-indigo-500"
                                >
                                    <option v-for="ic in availableIcons" :key="ic.name" :value="ic.name">
                                        {{ ic.name }} ({{ ic.label }})
                                    </option>
                                </select>
                            </div>

                            <div class="lg:col-span-3">
                                <label class="block text-[11px] font-semibold text-slate-400 mb-1">
                                    {{ t('admin.features_col_desc', 'Açıklama') }}
                                </label>
                                <textarea
                                    v-model="feature.description"
                                    rows="2"
                                    required
                                    placeholder="Özelliğin detaylı açıklaması..."
                                    class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-white focus:outline-none focus:border-indigo-500"
                                ></textarea>
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-slate-400 mb-1">
                                    {{ t('admin.features_col_color', 'Renk Teması') }}
                                </label>
                                <select
                                    v-model="feature.color"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-white focus:outline-none focus:border-indigo-500"
                                >
                                    <option v-for="col in availableColors" :key="col.name" :value="col.name">
                                        {{ col.label }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button Bar -->
            <div class="pt-4 border-t border-slate-800 flex items-center justify-between">
                <button
                    type="submit"
                    :disabled="form.processing || isSaving"
                    class="px-6 py-3 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/30 transition-all flex items-center space-x-2 disabled:opacity-50 cursor-pointer disabled:cursor-not-allowed"
                >
                    <Check class="w-4 h-4" :class="{ 'animate-spin': form.processing || isSaving }" />
                    <span>{{ (form.processing || isSaving) ? t('admin.features_saving', 'Kaydediliyor...') : t('admin.features_btn_save', 'Özellikleri ve Sayfayı Kaydet') }}</span>
                </button>

                <a
                    href="/features"
                    target="_blank"
                    class="text-xs text-indigo-400 hover:text-indigo-300 font-medium flex items-center space-x-1"
                >
                    <span>{{ t('admin.features_preview_page', '/features Sayfasını Önizle') }}</span>
                    <Globe class="w-3.5 h-3.5" />
                </a>
            </div>
        </form>
    </div>
</template>
