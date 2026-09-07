<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';
import {
    Image as ImageIcon,
    Upload,
    RotateCcw,
    Check,
    AlertCircle,
    Activity,
    Sparkles,
    Eye,
    Globe,
    Type
} from 'lucide-vue-next';

const props = defineProps<{
    systemSettings?: {
        logo?: string | null;
        brand_name?: string | null;
    };
}>();

const { t } = useI18n();
const page = usePage();

const currentLogo = computed(() => {
    return props.systemSettings?.logo || (page.props as any).system_settings?.logo || null;
});

const currentBrandName = computed(() => {
    return props.systemSettings?.brand_name || (page.props as any).system_settings?.brand_name || 'Seovy';
});

const fileInputRef = ref<HTMLInputElement | null>(null);
const previewUrl = ref<string | null>(null);
const uploadMode = ref<'file' | 'url'>('file');

const form = useForm({
    logo_file: null as File | null,
    logo_url: currentLogo.value || '',
    brand_name: currentBrandName.value,
});

const isResetting = ref(false);

const onFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        form.logo_file = file;
        previewUrl.value = URL.createObjectURL(file);
    }
};

const triggerFileInput = () => {
    fileInputRef.value?.click();
};

const clearSelectedFile = () => {
    form.logo_file = null;
    previewUrl.value = null;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const submitForm = () => {
    form.post('/admin/settings/logo', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            clearSelectedFile();
        },
    });
};

const resetToDefault = () => {
    if (confirm(t('admin.logo_confirm_reset', 'Sistem logosunu varsayılana sıfırlamak istediğinize emin misiniz?'))) {
        isResetting.value = true;
        router.post('/admin/settings/logo', { action: 'reset' }, {
            preserveScroll: true,
            onFinish: () => {
                isResetting.value = false;
                clearSelectedFile();
                form.brand_name = 'Seovy';
                form.logo_url = '';
            },
        });
    }
};

const activePreview = computed(() => {
    if (previewUrl.value) return previewUrl.value;
    if (uploadMode.value === 'url' && form.logo_url.trim()) return form.logo_url.trim();
    return currentLogo.value;
});
</script>

<template>
    <div class="p-6 rounded-3xl bg-slate-900/70 border border-slate-800 shadow-xl space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800/80 pb-5">
            <div class="flex items-center space-x-3.5">
                <div class="w-11 h-11 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center shadow-lg shadow-indigo-500/5">
                    <ImageIcon class="w-5 h-5" />
                </div>
                <div>
                    <h3 class="text-base font-bold text-white flex items-center space-x-2">
                        <span>{{ t('admin.logo_card_title', 'Sistem Logosu & Marka Ayarları') }}</span>
                        <span v-if="currentLogo" class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            {{ t('admin.logo_custom_active', 'Özel Logo Aktif') }}
                        </span>
                        <span v-else class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-800 text-slate-400 border border-slate-700">
                            {{ t('admin.logo_default_active', 'Varsayılan Logo') }}
                        </span>
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">
                        {{ t('admin.logo_card_desc', 'Platformun tüm kullanıcı sayfalarında ve sol menüde (sidebar) görüntülenecek logonuzu ve marka adınızı özelleştirin.') }}
                    </p>
                </div>
            </div>

            <div v-if="currentLogo" class="flex items-center space-x-2">
                <button
                    type="button"
                    @click="resetToDefault"
                    :disabled="isResetting"
                    class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-rose-950/40 text-slate-300 hover:text-rose-300 border border-slate-700 hover:border-rose-500/30 transition-all flex items-center space-x-1.5"
                >
                    <RotateCcw class="w-3.5 h-3.5" :class="{ 'animate-spin': isResetting }" />
                    <span>{{ t('admin.logo_btn_reset', 'Varsayılana Sıfırla') }}</span>
                </button>
            </div>
        </div>

        <form @submit.prevent="submitForm" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Left: Logo Upload & Inputs (7 cols) -->
                <div class="lg:col-span-7 space-y-4">
                    <!-- Brand Name Input -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2 flex items-center space-x-1.5">
                            <Type class="w-3.5 h-3.5 text-indigo-400" />
                            <span>{{ t('admin.logo_brand_name', 'Marka / Sistem Adı') }}</span>
                        </label>
                        <input
                            v-model="form.brand_name"
                            type="text"
                            placeholder="Seovy"
                            class="w-full px-4 py-2.5 rounded-xl bg-slate-950/80 border border-slate-800 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                        />
                        <p class="text-[11px] text-slate-500 mt-1">
                            {{ t('admin.logo_brand_name_hint', 'Logonun yanında veya logo bulunmadığında görüntülenecek platform adı.') }}
                        </p>
                    </div>

                    <!-- Upload Type Switcher -->
                    <div class="flex items-center space-x-2 bg-slate-950/60 p-1 rounded-xl border border-slate-800 text-xs font-medium w-fit">
                        <button
                            type="button"
                            @click="uploadMode = 'file'"
                            class="px-3 py-1.5 rounded-lg transition-all flex items-center space-x-1.5"
                            :class="uploadMode === 'file' ? 'bg-indigo-600 text-white font-semibold shadow-sm' : 'text-slate-400 hover:text-white'"
                        >
                            <Upload class="w-3.5 h-3.5" />
                            <span>{{ t('admin.logo_mode_file', 'Görsel Yükle (Dosya)') }}</span>
                        </button>
                        <button
                            type="button"
                            @click="uploadMode = 'url'"
                            class="px-3 py-1.5 rounded-lg transition-all flex items-center space-x-1.5"
                            :class="uploadMode === 'url' ? 'bg-indigo-600 text-white font-semibold shadow-sm' : 'text-slate-400 hover:text-white'"
                        >
                            <Globe class="w-3.5 h-3.5" />
                            <span>{{ t('admin.logo_mode_url', 'Logo URL Adresi') }}</span>
                        </button>
                    </div>

                    <!-- File Upload Mode -->
                    <div v-if="uploadMode === 'file'" class="space-y-2">
                        <input
                            ref="fileInputRef"
                            type="file"
                            accept="image/png, image/jpeg, image/svg+xml, image/webp"
                            class="hidden"
                            @change="onFileChange"
                        />

                        <div
                            @click="triggerFileInput"
                            class="border-2 border-dashed border-slate-700/80 hover:border-indigo-500/80 rounded-2xl p-6 flex flex-col items-center justify-center text-center cursor-pointer transition-all bg-slate-950/40 hover:bg-slate-950/70 group"
                        >
                            <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 flex items-center justify-center group-hover:scale-110 transition-transform mb-3">
                                <Upload class="w-6 h-6" />
                            </div>
                            <span class="text-xs font-semibold text-white group-hover:text-indigo-300 transition-colors">
                                {{ form.logo_file ? form.logo_file.name : t('admin.logo_drag_or_browse', 'Yeni logo seçmek için tıklayın veya dosyayı sürükleyin') }}
                            </span>
                            <span class="text-[11px] text-slate-500 mt-1">
                                {{ t('admin.logo_supported_formats', 'PNG, SVG, JPG veya WebP (Önerilen: Şeffaf arka plan, Maksimum 2 MB)') }}
                            </span>
                        </div>

                        <div v-if="form.logo_file" class="flex items-center justify-between text-xs text-indigo-300 bg-indigo-950/30 border border-indigo-500/30 p-2.5 rounded-xl">
                            <span class="truncate">{{ form.logo_file.name }} ({{ Math.round(form.logo_file.size / 1024) }} KB)</span>
                            <button
                                type="button"
                                @click="clearSelectedFile"
                                class="text-slate-400 hover:text-rose-400 ml-2 shrink-0"
                            >
                                {{ t('admin.logo_cancel', 'Vazgeç') }}
                            </button>
                        </div>
                    </div>

                    <!-- Direct URL Mode -->
                    <div v-else class="space-y-2">
                        <input
                            v-model="form.logo_url"
                            type="url"
                            placeholder="https://example.com/assets/my-logo.png"
                            class="w-full px-4 py-2.5 rounded-xl bg-slate-950/80 border border-slate-800 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                        />
                        <p class="text-[11px] text-slate-500">
                            {{ t('admin.logo_url_hint', 'Doğrudan HTTPS erişilebilir şeffaf logo görseli bağlantısı girin.') }}
                        </p>
                    </div>

                    <!-- Errors -->
                    <div v-if="form.errors.logo_file || form.errors.logo_url" class="text-xs text-rose-400 bg-rose-950/30 border border-rose-500/30 p-3 rounded-xl flex items-center space-x-2">
                        <AlertCircle class="w-4 h-4 shrink-0" />
                        <span>{{ form.errors.logo_file || form.errors.logo_url }}</span>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/30 transition-all flex items-center space-x-2 disabled:opacity-50"
                        >
                            <Check class="w-4 h-4" />
                            <span>{{ form.processing ? t('admin.logo_saving', 'Kaydediliyor...') : t('admin.logo_save_btn', 'Logoyu & Markayı Kaydet') }}</span>
                        </button>
                    </div>
                </div>

                <!-- Right: Live Previews (5 cols) -->
                <div class="lg:col-span-5 space-y-4">
                    <span class="block text-xs font-semibold text-slate-300 uppercase tracking-wider flex items-center space-x-1.5">
                        <Eye class="w-3.5 h-3.5 text-indigo-400" />
                        <span>{{ t('admin.logo_live_preview', 'Canlı Önizleme') }}</span>
                    </span>

                    <!-- Dark Sidebar Simulation Box -->
                    <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800/90 space-y-2">
                        <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider block">
                            {{ t('admin.logo_preview_sidebar', 'Koyu Zemin & Sol Menü Önizlemesi') }}
                        </span>
                        <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between">
                            <div class="flex items-center space-x-3 min-w-0">
                                <template v-if="activePreview">
                                    <img
                                        :src="activePreview"
                                        alt="Logo Preview"
                                        class="h-9 max-w-[130px] object-contain rounded-lg"
                                        @error="($event.target as HTMLElement).style.display = 'none'"
                                    />
                                </template>
                                <template v-else>
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center shadow-lg shadow-indigo-500/20 shrink-0">
                                        <Activity class="w-5 h-5 text-white" />
                                    </div>
                                    <span class="text-xl font-bold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-white via-slate-200 to-slate-400 truncate">
                                        {{ form.brand_name || 'Seovy' }}
                                    </span>
                                </template>
                            </div>
                            <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 shrink-0">
                                Owner
                            </span>
                        </div>

                        <!-- Simulated Plan Badge under Logo -->
                        <div class="px-1 pt-1">
                            <div class="flex items-center justify-between px-2.5 py-1.5 rounded-lg bg-indigo-950/30 border border-indigo-500/20 text-xs">
                                <div class="flex items-center space-x-2">
                                    <span class="relative flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                    </span>
                                    <div class="flex flex-col">
                                        <span class="text-[9px] uppercase tracking-wider font-semibold text-slate-400 leading-tight">
                                            {{ t('nav.active_plan', 'Aktif Paket') }}
                                        </span>
                                        <span class="text-xs font-bold text-white truncate">
                                            Pro Plan
                                        </span>
                                    </div>
                                </div>
                                <span class="text-[10px] text-indigo-400 font-medium">
                                    {{ t('nav.upgrade', 'Yükselt') }} &rarr;
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Light Container Contrast Check -->
                    <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800/90 space-y-2">
                        <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider block">
                            {{ t('admin.logo_preview_light', 'Açık Zemin Kontrast Testi') }}
                        </span>
                        <div class="p-3 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center min-h-[54px]">
                            <template v-if="activePreview">
                                <img
                                    :src="activePreview"
                                    alt="Logo Contrast Check"
                                    class="h-8 max-w-[150px] object-contain"
                                />
                            </template>
                            <template v-else>
                                <div class="flex items-center space-x-2">
                                    <div class="w-7 h-7 rounded-lg bg-indigo-600 flex items-center justify-center text-white">
                                        <Activity class="w-4 h-4" />
                                    </div>
                                    <span class="text-base font-bold text-slate-900">
                                        {{ form.brand_name || 'Seovy' }}
                                    </span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>
