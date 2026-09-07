<script setup lang="ts">
import { ref, computed, watch } from 'vue';
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
    Type,
    Moon,
    Sun,
    CheckCircle2
} from 'lucide-vue-next';

const props = defineProps<{
    systemSettings?: {
        logo?: string | null;
        logo_dark?: string | null;
        logo_light?: string | null;
        favicon?: string | null;
        brand_name?: string | null;
    };
}>();

const { t } = useI18n();
const page = usePage();

const currentDarkLogo = computed(() => {
    return props.systemSettings?.logo_dark || props.systemSettings?.logo || (page.props as any).system_settings?.logo_dark || (page.props as any).system_settings?.logo || null;
});

const currentLightLogo = computed(() => {
    return props.systemSettings?.logo_light || (page.props as any).system_settings?.logo_light || null;
});

const currentFavicon = computed(() => {
    return props.systemSettings?.favicon || (page.props as any).system_settings?.favicon || null;
});

const currentBrandName = computed(() => {
    return props.systemSettings?.brand_name || (page.props as any).system_settings?.brand_name || 'Seovy';
});

// File input refs
const darkFileInputRef = ref<HTMLInputElement | null>(null);
const lightFileInputRef = ref<HTMLInputElement | null>(null);
const faviconInputRef = ref<HTMLInputElement | null>(null);

// Local file preview object URLs
const darkPreviewUrl = ref<string | null>(null);
const lightPreviewUrl = ref<string | null>(null);
const faviconPreviewUrl = ref<string | null>(null);

// Active Tab in logo card: 'dark' | 'light'
const activeLogoTab = ref<'dark' | 'light'>('dark');

// Upload modes: 'file' | 'url'
const darkUploadMode = ref<'file' | 'url'>('file');
const lightUploadMode = ref<'file' | 'url'>('file');
const faviconUploadMode = ref<'file' | 'url'>('file');

const form = useForm({
    logo_dark_file: null as File | null,
    logo_dark_url: currentDarkLogo.value || '',
    logo_light_file: null as File | null,
    logo_light_url: currentLightLogo.value || '',
    favicon_file: null as File | null,
    favicon_url: currentFavicon.value || '',
    brand_name: currentBrandName.value,
});

const isResetting = ref(false);
const isSaving = ref(false);
const saveSuccessMessage = ref<string | null>(null);
const globalErrorMessage = ref<string | null>(null);

const isDraggingDark = ref(false);
const isDraggingLight = ref(false);
const isDraggingFavicon = ref(false);

// Watch external changes (from Inertia response or props) and sync form
watch(
    () => [currentDarkLogo.value, currentLightLogo.value, currentFavicon.value, currentBrandName.value],
    ([newDark, newLight, newFavicon, newBrand]) => {
        if (!form.logo_dark_file) {
            form.logo_dark_url = newDark || '';
        }
        if (!form.logo_light_file) {
            form.logo_light_url = newLight || '';
        }
        if (!form.favicon_file) {
            form.favicon_url = newFavicon || '';
        }
        form.brand_name = newBrand || 'Seovy';
    }
);

const onDarkFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        form.logo_dark_file = file;
        darkPreviewUrl.value = URL.createObjectURL(file);
    }
};

const handleDarkDrop = (e: DragEvent) => {
    isDraggingDark.value = false;
    if (e.dataTransfer?.files && e.dataTransfer.files[0]) {
        const file = e.dataTransfer.files[0];
        form.logo_dark_file = file;
        darkPreviewUrl.value = URL.createObjectURL(file);
    }
};

const onLightFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        form.logo_light_file = file;
        lightPreviewUrl.value = URL.createObjectURL(file);
    }
};

const handleLightDrop = (e: DragEvent) => {
    isDraggingLight.value = false;
    if (e.dataTransfer?.files && e.dataTransfer.files[0]) {
        const file = e.dataTransfer.files[0];
        form.logo_light_file = file;
        lightPreviewUrl.value = URL.createObjectURL(file);
    }
};

const onFaviconChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        form.favicon_file = file;
        faviconPreviewUrl.value = URL.createObjectURL(file);
    }
};

const handleFaviconDrop = (e: DragEvent) => {
    isDraggingFavicon.value = false;
    if (e.dataTransfer?.files && e.dataTransfer.files[0]) {
        const file = e.dataTransfer.files[0];
        form.favicon_file = file;
        faviconPreviewUrl.value = URL.createObjectURL(file);
    }
};

const triggerDarkFileInput = () => {
    darkFileInputRef.value?.click();
};

const triggerLightFileInput = () => {
    lightFileInputRef.value?.click();
};

const triggerFaviconInput = () => {
    faviconInputRef.value?.click();
};

const clearSelectedDarkFile = () => {
    form.logo_dark_file = null;
    darkPreviewUrl.value = null;
    if (darkFileInputRef.value) {
        darkFileInputRef.value.value = '';
    }
};

const clearSelectedLightFile = () => {
    form.logo_light_file = null;
    lightPreviewUrl.value = null;
    if (lightFileInputRef.value) {
        lightFileInputRef.value.value = '';
    }
};

const clearSelectedFavicon = () => {
    form.favicon_file = null;
    faviconPreviewUrl.value = null;
    if (faviconInputRef.value) {
        faviconInputRef.value.value = '';
    }
};

const submitForm = () => {
    saveSuccessMessage.value = null;
    globalErrorMessage.value = null;
    isSaving.value = true;

    form.transform((data) => {
        const payload: Record<string, any> = {
            brand_name: data.brand_name?.trim() || 'Seovy',
        };

        if (darkUploadMode.value === 'file') {
            if (data.logo_dark_file instanceof File) {
                payload.logo_dark_file = data.logo_dark_file;
            }
        } else if (data.logo_dark_url && data.logo_dark_url.trim()) {
            payload.logo_dark_url = data.logo_dark_url.trim();
        }

        if (lightUploadMode.value === 'file') {
            if (data.logo_light_file instanceof File) {
                payload.logo_light_file = data.logo_light_file;
            }
        } else if (data.logo_light_url && data.logo_light_url.trim()) {
            payload.logo_light_url = data.logo_light_url.trim();
        }

        if (faviconUploadMode.value === 'file') {
            if (data.favicon_file instanceof File) {
                payload.favicon_file = data.favicon_file;
            }
        } else if (data.favicon_url && data.favicon_url.trim()) {
            payload.favicon_url = data.favicon_url.trim();
        }

        return payload;
    });

    form.post('/admin/settings/logo', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            isSaving.value = false;
            clearSelectedDarkFile();
            clearSelectedLightFile();
            clearSelectedFavicon();
            saveSuccessMessage.value = t('admin.logo_save_success', 'Logolar ve marka ayarları başarıyla kaydedildi.');
            setTimeout(() => {
                saveSuccessMessage.value = null;
            }, 6000);
        },
        onError: (errors) => {
            isSaving.value = false;
            const values = Object.values(errors);
            globalErrorMessage.value = values.length > 0 ? String(values[0]) : t('admin.logo_save_error', 'Ayarlar kaydedilirken bir hata oluştu.');
            console.error('Logo settings save failed:', errors);
        },
        onFinish: () => {
            isSaving.value = false;
        },
    });
};

const resetToDefault = () => {
    if (confirm(t('admin.logo_confirm_reset', 'Sistem logolarını ve faviconu varsayılana sıfırlamak istediğinize emin misiniz?'))) {
        isResetting.value = true;
        saveSuccessMessage.value = null;
        router.post('/admin/settings/logo', { action: 'reset' }, {
            preserveScroll: true,
            onFinish: () => {
                isResetting.value = false;
                clearSelectedDarkFile();
                clearSelectedLightFile();
                clearSelectedFavicon();
                form.brand_name = 'Seovy';
                form.logo_dark_url = '';
                form.logo_light_url = '';
                form.favicon_url = '';
                saveSuccessMessage.value = t('admin.logo_reset_success', 'Varsayılan marka ve logolara dönüldü.');
                setTimeout(() => {
                    saveSuccessMessage.value = null;
                }, 6000);
            },
        });
    }
};

const activeDarkPreview = computed(() => {
    if (darkPreviewUrl.value) return darkPreviewUrl.value;
    if (darkUploadMode.value === 'url' && form.logo_dark_url?.trim()) return form.logo_dark_url.trim();
    return currentDarkLogo.value;
});

const activeLightPreview = computed(() => {
    if (lightPreviewUrl.value) return lightPreviewUrl.value;
    if (lightUploadMode.value === 'url' && form.logo_light_url?.trim()) return form.logo_light_url.trim();
    return currentLightLogo.value || activeDarkPreview.value;
});

const activeFaviconPreview = computed(() => {
    if (faviconPreviewUrl.value) return faviconPreviewUrl.value;
    if (faviconUploadMode.value === 'url' && form.favicon_url?.trim()) return form.favicon_url.trim();
    return currentFavicon.value || activeDarkPreview.value || activeLightPreview.value;
});

const darkPreviewFailed = ref(false);
const lightPreviewFailed = ref(false);
const faviconPreviewFailed = ref(false);

watch(activeDarkPreview, () => {
    darkPreviewFailed.value = false;
});
watch(activeLightPreview, () => {
    lightPreviewFailed.value = false;
});
watch(activeFaviconPreview, () => {
    faviconPreviewFailed.value = false;
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
                        <span>{{ t('admin.logo_card_title', 'Sistem Logoları & Marka Ayarları') }}</span>
                        <span v-if="currentDarkLogo || currentLightLogo" class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            {{ t('admin.logo_custom_active', 'Özel Logo Aktif') }}
                        </span>
                        <span v-else class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-800 text-slate-400 border border-slate-700">
                            {{ t('admin.logo_default_active', 'Varsayılan Logo') }}
                        </span>
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">
                        {{ t('admin.logo_card_desc', 'Platformun aydınlık ve karanlık temalarında görüntülenecek logolarınızı, favicon ve marka adınızı özelleştirin.') }}
                    </p>
                </div>
            </div>

            <div v-if="currentDarkLogo || currentLightLogo || currentFavicon" class="flex items-center space-x-2">
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

        <!-- Success Notification Banner -->
        <div v-if="saveSuccessMessage" class="p-3.5 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs flex items-center justify-between shadow-lg shadow-emerald-500/5">
            <div class="flex items-center space-x-2.5">
                <CheckCircle2 class="w-4 h-4 shrink-0 text-emerald-400" />
                <span class="font-medium">{{ saveSuccessMessage }}</span>
            </div>
            <button
                type="button"
                @click="saveSuccessMessage = null"
                class="text-emerald-400/70 hover:text-emerald-300 ml-3"
            >
                &times;
            </button>
        </div>

        <!-- Global Error Notification Banner -->
        <div v-if="globalErrorMessage" class="p-3.5 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs flex items-center justify-between shadow-lg shadow-rose-500/5">
            <div class="flex items-center space-x-2.5">
                <AlertCircle class="w-4 h-4 shrink-0 text-rose-400" />
                <span class="font-medium">{{ globalErrorMessage }}</span>
            </div>
            <button
                type="button"
                @click="globalErrorMessage = null"
                class="text-rose-400/70 hover:text-rose-300 ml-3 text-sm font-bold"
            >
                &times;
            </button>
        </div>

        <form @submit.prevent="submitForm" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Left: Inputs (7 cols) -->
                <div class="lg:col-span-7 space-y-6">
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

                    <!-- Logo Tabs: Dark Theme Logo vs Light Theme Logo -->
                    <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800/80 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                            <div class="flex items-center space-x-2">
                                <button
                                    type="button"
                                    @click="activeLogoTab = 'dark'"
                                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center space-x-2"
                                    :class="activeLogoTab === 'dark' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white bg-slate-900 border border-slate-800'"
                                >
                                    <Moon class="w-3.5 h-3.5" />
                                    <span>{{ t('admin.logo_tab_dark', 'Karanlık Tema Logosu') }}</span>
                                    <span v-if="currentDarkLogo" class="w-1.5 h-1.5 rounded-full bg-emerald-400 ml-1"></span>
                                </button>
                                <button
                                    type="button"
                                    @click="activeLogoTab = 'light'"
                                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center space-x-2"
                                    :class="activeLogoTab === 'light' ? 'bg-amber-500 text-slate-950 shadow-lg shadow-amber-500/30' : 'text-slate-400 hover:text-white bg-slate-900 border border-slate-800'"
                                >
                                    <Sun class="w-3.5 h-3.5" />
                                    <span>{{ t('admin.logo_tab_light', 'Aydınlık Tema Logosu') }}</span>
                                    <span v-if="currentLightLogo" class="w-1.5 h-1.5 rounded-full bg-emerald-400 ml-1"></span>
                                </button>
                            </div>
                            <span class="text-[11px] text-slate-500 hidden sm:inline">
                                {{ activeLogoTab === 'dark' ? t('admin.logo_dark_desc', 'Koyu arayüz zemininde görüntülenecek açık renkli logo') : t('admin.logo_light_desc', 'Açık arayüz zemininde görüntülenecek koyu/renkli logo') }}
                            </span>
                        </div>

                        <!-- Dark Theme Logo Form Fields (Using v-show so file input element and state are preserved) -->
                        <div v-show="activeLogoTab === 'dark'" class="space-y-3">
                            <div class="flex items-center space-x-2 bg-slate-900 p-1 rounded-xl border border-slate-800 text-xs font-medium w-fit">
                                <button
                                    type="button"
                                    @click="darkUploadMode = 'file'"
                                    class="px-3 py-1.5 rounded-lg transition-all flex items-center space-x-1.5"
                                    :class="darkUploadMode === 'file' ? 'bg-indigo-600 text-white font-semibold shadow-sm' : 'text-slate-400 hover:text-white'"
                                >
                                    <Upload class="w-3.5 h-3.5" />
                                    <span>{{ t('admin.logo_mode_file', 'Görsel Yükle (Dosya)') }}</span>
                                </button>
                                <button
                                    type="button"
                                    @click="darkUploadMode = 'url'"
                                    class="px-3 py-1.5 rounded-lg transition-all flex items-center space-x-1.5"
                                    :class="darkUploadMode === 'url' ? 'bg-indigo-600 text-white font-semibold shadow-sm' : 'text-slate-400 hover:text-white'"
                                >
                                    <Globe class="w-3.5 h-3.5" />
                                    <span>{{ t('admin.logo_mode_url', 'Logo URL Adresi') }}</span>
                                </button>
                            </div>

                            <div v-show="darkUploadMode === 'file'" class="space-y-2">
                                <input
                                    ref="darkFileInputRef"
                                    type="file"
                                    accept=".svg, image/svg+xml, .png, image/png, .jpg, .jpeg, image/jpeg, .webp, image/webp"
                                    class="hidden"
                                    @change="onDarkFileChange"
                                />

                                <div
                                    @click="triggerDarkFileInput"
                                    @dragover.prevent="isDraggingDark = true"
                                    @dragleave.prevent="isDraggingDark = false"
                                    @drop.prevent="handleDarkDrop"
                                    class="border-2 border-dashed rounded-2xl p-5 flex flex-col items-center justify-center text-center cursor-pointer transition-all group"
                                    :class="isDraggingDark ? 'border-indigo-400 bg-indigo-950/40 ring-2 ring-indigo-500/30' : 'border-slate-700/80 hover:border-indigo-500/80 bg-slate-900/50 hover:bg-slate-900/80'"
                                >
                                    <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 flex items-center justify-center group-hover:scale-110 transition-transform mb-2">
                                        <Moon class="w-5 h-5" />
                                    </div>
                                    <span class="text-xs font-semibold text-white group-hover:text-indigo-300 transition-colors">
                                        {{ form.logo_dark_file ? form.logo_dark_file.name : t('admin.logo_dark_drag_or_browse', 'Karanlık tema logosu seçmek için tıklayın veya dosyayı sürükleyin') }}
                                    </span>
                                    <span class="text-[11px] text-slate-500 mt-1">
                                        {{ t('admin.logo_supported_formats', 'PNG, SVG, JPG veya WebP (Önerilen: Şeffaf arka plan, Maksimum 5 MB)') }}
                                    </span>
                                </div>

                                <div v-if="form.logo_dark_file" class="flex items-center justify-between text-xs text-indigo-300 bg-indigo-950/30 border border-indigo-500/30 p-2.5 rounded-xl">
                                    <span class="truncate">{{ form.logo_dark_file.name }} ({{ Math.round(form.logo_dark_file.size / 1024) }} KB)</span>
                                    <button
                                        type="button"
                                        @click="clearSelectedDarkFile"
                                        class="text-slate-400 hover:text-rose-400 ml-2 shrink-0"
                                    >
                                        {{ t('admin.logo_cancel', 'Vazgeç') }}
                                    </button>
                                </div>

                                <div v-if="form.errors.logo_dark_file" class="text-xs text-rose-400 flex items-center space-x-1.5 mt-1">
                                    <AlertCircle class="w-3.5 h-3.5 shrink-0" />
                                    <span>{{ form.errors.logo_dark_file }}</span>
                                </div>
                            </div>

                            <div v-show="darkUploadMode === 'url'" class="space-y-2">
                                <input
                                    v-model="form.logo_dark_url"
                                    type="url"
                                    placeholder="https://example.com/logo-white.svg"
                                    class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                />
                                <p class="text-[11px] text-slate-500">
                                    {{ t('admin.logo_url_hint', 'Doğrudan HTTPS erişilebilir şeffaf logo görseli bağlantısı girin.') }}
                                </p>
                                <div v-if="form.errors.logo_dark_url" class="text-xs text-rose-400 flex items-center space-x-1.5 mt-1">
                                    <AlertCircle class="w-3.5 h-3.5 shrink-0" />
                                    <span>{{ form.errors.logo_dark_url }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Light Theme Logo Form Fields (Using v-show so file input element and state are preserved) -->
                        <div v-show="activeLogoTab === 'light'" class="space-y-3">
                            <div class="flex items-center space-x-2 bg-slate-900 p-1 rounded-xl border border-slate-800 text-xs font-medium w-fit">
                                <button
                                    type="button"
                                    @click="lightUploadMode = 'file'"
                                    class="px-3 py-1.5 rounded-lg transition-all flex items-center space-x-1.5"
                                    :class="lightUploadMode === 'file' ? 'bg-amber-500 text-slate-950 font-bold shadow-sm' : 'text-slate-400 hover:text-white'"
                                >
                                    <Upload class="w-3.5 h-3.5" />
                                    <span>{{ t('admin.logo_mode_file', 'Görsel Yükle (Dosya)') }}</span>
                                </button>
                                <button
                                    type="button"
                                    @click="lightUploadMode = 'url'"
                                    class="px-3 py-1.5 rounded-lg transition-all flex items-center space-x-1.5"
                                    :class="lightUploadMode === 'url' ? 'bg-amber-500 text-slate-950 font-bold shadow-sm' : 'text-slate-400 hover:text-white'"
                                >
                                    <Globe class="w-3.5 h-3.5" />
                                    <span>{{ t('admin.logo_mode_url', 'Logo URL Adresi') }}</span>
                                </button>
                            </div>

                            <div v-show="lightUploadMode === 'file'" class="space-y-2">
                                <input
                                    ref="lightFileInputRef"
                                    type="file"
                                    accept=".svg, image/svg+xml, .png, image/png, .jpg, .jpeg, image/jpeg, .webp, image/webp"
                                    class="hidden"
                                    @change="onLightFileChange"
                                />

                                <div
                                    @click="triggerLightFileInput"
                                    @dragover.prevent="isDraggingLight = true"
                                    @dragleave.prevent="isDraggingLight = false"
                                    @drop.prevent="handleLightDrop"
                                    class="border-2 border-dashed rounded-2xl p-5 flex flex-col items-center justify-center text-center cursor-pointer transition-all group"
                                    :class="isDraggingLight ? 'border-amber-400 bg-amber-950/40 ring-2 ring-amber-500/30' : 'border-slate-700/80 hover:border-amber-500/80 bg-slate-900/50 hover:bg-slate-900/80'"
                                >
                                    <div class="w-10 h-10 rounded-2xl bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center justify-center group-hover:scale-110 transition-transform mb-2">
                                        <Sun class="w-5 h-5" />
                                    </div>
                                    <span class="text-xs font-semibold text-white group-hover:text-amber-300 transition-colors">
                                        {{ form.logo_light_file ? form.logo_light_file.name : t('admin.logo_light_drag_or_browse', 'Aydınlık tema logosu seçmek için tıklayın veya dosyayı sürükleyin') }}
                                    </span>
                                    <span class="text-[11px] text-slate-500 mt-1">
                                        {{ t('admin.logo_supported_formats', 'PNG, SVG, JPG veya WebP (Önerilen: Şeffaf arka plan, koyu veya renkli logo, Maksimum 5 MB)') }}
                                    </span>
                                </div>

                                <div v-if="form.logo_light_file" class="flex items-center justify-between text-xs text-amber-300 bg-amber-950/30 border border-amber-500/30 p-2.5 rounded-xl">
                                    <span class="truncate">{{ form.logo_light_file.name }} ({{ Math.round(form.logo_light_file.size / 1024) }} KB)</span>
                                    <button
                                        type="button"
                                        @click="clearSelectedLightFile"
                                        class="text-slate-400 hover:text-rose-400 ml-2 shrink-0"
                                    >
                                        {{ t('admin.logo_cancel', 'Vazgeç') }}
                                    </button>
                                </div>

                                <div v-if="form.errors.logo_light_file" class="text-xs text-rose-400 flex items-center space-x-1.5 mt-1">
                                    <AlertCircle class="w-3.5 h-3.5 shrink-0" />
                                    <span>{{ form.errors.logo_light_file }}</span>
                                </div>
                            </div>

                            <div v-show="lightUploadMode === 'url'" class="space-y-2">
                                <input
                                    v-model="form.logo_light_url"
                                    type="url"
                                    placeholder="https://example.com/logo-dark.svg"
                                    class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                                />
                                <p class="text-[11px] text-slate-500">
                                    {{ t('admin.logo_light_url_hint', 'Aydınlık tema için doğrudan HTTPS erişilebilir koyu renkli logo görseli bağlantısı girin.') }}
                                </p>
                                <div v-if="form.errors.logo_light_url" class="text-xs text-rose-400 flex items-center space-x-1.5 mt-1">
                                    <AlertCircle class="w-3.5 h-3.5 shrink-0" />
                                    <span>{{ form.errors.logo_light_url }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Favicon Section -->
                    <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800/80 space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-semibold text-slate-300 uppercase tracking-wider flex items-center space-x-1.5">
                                <Sparkles class="w-3.5 h-3.5 text-amber-400" />
                                <span>{{ t('admin.logo_favicon_title', 'Favicon (Tarayıcı Sekme Simgesi)') }}</span>
                            </label>
                            <span v-if="currentFavicon" class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                {{ t('admin.logo_custom_active', 'Özel Aktif') }}
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400">
                            {{ t('admin.logo_favicon_hint', 'Tarayıcı sekmelerinde ve yer imlerinde görüntülenecek 16x16 / 32x32 piksel simge (.ico, .png, .svg).') }}
                        </p>

                        <!-- Favicon Upload Mode Switcher -->
                        <div class="flex items-center space-x-2 bg-slate-900 p-1 rounded-xl border border-slate-800 text-xs font-medium w-fit">
                            <button
                                type="button"
                                @click="faviconUploadMode = 'file'"
                                class="px-3 py-1.5 rounded-lg transition-all flex items-center space-x-1.5"
                                :class="faviconUploadMode === 'file' ? 'bg-indigo-600 text-white font-semibold shadow-sm' : 'text-slate-400 hover:text-white'"
                            >
                                <Upload class="w-3.5 h-3.5" />
                                <span>{{ t('admin.logo_favicon_mode_file', 'Favicon Dosyası Yükle') }}</span>
                            </button>
                            <button
                                type="button"
                                @click="faviconUploadMode = 'url'"
                                class="px-3 py-1.5 rounded-lg transition-all flex items-center space-x-1.5"
                                :class="faviconUploadMode === 'url' ? 'bg-indigo-600 text-white font-semibold shadow-sm' : 'text-slate-400 hover:text-white'"
                            >
                                <Globe class="w-3.5 h-3.5" />
                                <span>{{ t('admin.logo_favicon_mode_url', 'Favicon URL Adresi') }}</span>
                            </button>
                        </div>

                        <!-- Favicon File Upload Mode -->
                        <div v-show="faviconUploadMode === 'file'" class="space-y-2">
                            <input
                                ref="faviconInputRef"
                                type="file"
                                accept=".ico, image/x-icon, image/vnd.microsoft.icon, .svg, image/svg+xml, .png, image/png, .webp, image/webp"
                                class="hidden"
                                @change="onFaviconChange"
                            />

                            <div
                                @click="triggerFaviconInput"
                                @dragover.prevent="isDraggingFavicon = true"
                                @dragleave.prevent="isDraggingFavicon = false"
                                @drop.prevent="handleFaviconDrop"
                                class="border-2 border-dashed rounded-2xl p-5 flex flex-col items-center justify-center text-center cursor-pointer transition-all group"
                                :class="isDraggingFavicon ? 'border-amber-400 bg-amber-950/40 ring-2 ring-amber-500/30' : 'border-slate-700/80 hover:border-amber-500/80 bg-slate-900/50 hover:bg-slate-900/80'"
                            >
                                <div class="w-10 h-10 rounded-2xl bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center justify-center group-hover:scale-110 transition-transform mb-2">
                                    <Sparkles class="w-5 h-5" />
                                </div>
                                <span class="text-xs font-semibold text-white group-hover:text-amber-300 transition-colors">
                                    {{ form.favicon_file ? form.favicon_file.name : t('admin.logo_favicon_drag_or_browse', 'Favicon seçmek için tıklayın veya dosyayı sürükleyin') }}
                                </span>
                                <span class="text-[11px] text-slate-500 mt-1">
                                    {{ t('admin.logo_favicon_supported_formats', 'ICO, SVG, PNG, WebP (Önerilen: 32x32 piksel, Maks 2 MB)') }}
                                </span>
                            </div>

                            <div v-if="form.favicon_file" class="flex items-center justify-between text-xs text-amber-300 bg-amber-950/30 border border-amber-500/30 p-2.5 rounded-xl">
                                <span class="truncate">{{ form.favicon_file.name }} ({{ Math.round(form.favicon_file.size / 1024) }} KB)</span>
                                <button
                                    type="button"
                                    @click="clearSelectedFavicon"
                                    class="text-slate-400 hover:text-rose-400 ml-2 shrink-0"
                                >
                                    {{ t('admin.logo_cancel', 'Vazgeç') }}
                                </button>
                            </div>

                            <div v-if="form.errors.favicon_file" class="text-xs text-rose-400 flex items-center space-x-1.5 mt-1">
                                <AlertCircle class="w-3.5 h-3.5 shrink-0" />
                                <span>{{ form.errors.favicon_file }}</span>
                            </div>
                        </div>

                        <!-- Favicon Direct URL Mode -->
                        <div v-show="faviconUploadMode === 'url'" class="space-y-2">
                            <input
                                v-model="form.favicon_url"
                                type="url"
                                placeholder="https://example.com/favicon.ico"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                            />
                            <p class="text-[11px] text-slate-500">
                                {{ t('admin.logo_url_hint', 'Doğrudan HTTPS erişilebilir favicon bağlantısı girin.') }}
                            </p>
                            <div v-if="form.errors.favicon_url" class="text-xs text-rose-400 flex items-center space-x-1.5 mt-1">
                                <AlertCircle class="w-3.5 h-3.5 shrink-0" />
                                <span>{{ form.errors.favicon_url }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Global Form Errors Alert -->
                    <div v-if="Object.keys(form.errors).length > 0" class="text-xs text-rose-400 bg-rose-950/30 border border-rose-500/30 p-3 rounded-xl flex items-center space-x-2">
                        <AlertCircle class="w-4 h-4 shrink-0" />
                        <span>{{ Object.values(form.errors)[0] }}</span>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing || isSaving"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/30 transition-all flex items-center space-x-2 disabled:opacity-50 cursor-pointer disabled:cursor-not-allowed"
                        >
                            <Check class="w-4 h-4" :class="{ 'animate-spin': form.processing || isSaving }" />
                            <span>{{ (form.processing || isSaving) ? t('admin.logo_saving', 'Kaydediliyor...') : t('admin.logo_save_btn', 'Logoları & Markayı Kaydet') }}</span>
                        </button>
                    </div>
                </div>

                <!-- Right: Live Previews (5 cols) -->
                <div class="lg:col-span-5 space-y-4">
                    <span class="block text-xs font-semibold text-slate-300 uppercase tracking-wider flex items-center space-x-1.5">
                        <Eye class="w-3.5 h-3.5 text-indigo-400" />
                        <span>{{ t('admin.logo_live_preview', 'Canlı Önizlemeler') }}</span>
                    </span>

                    <!-- Dark Sidebar Simulation Box -->
                    <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800/90 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider flex items-center space-x-1">
                                <Moon class="w-3 h-3 text-indigo-400" />
                                <span>{{ t('admin.logo_preview_sidebar', 'Karanlık Tema Önizlemesi') }}</span>
                            </span>
                            <span class="text-[9px] px-1.5 py-0.5 rounded bg-slate-900 text-slate-400 border border-slate-800">
                                Dark Mode
                            </span>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between">
                            <div class="flex items-center space-x-3 min-w-0">
                                <template v-if="activeDarkPreview && !darkPreviewFailed">
                                    <img
                                        :key="activeDarkPreview"
                                        :src="activeDarkPreview"
                                        alt="Dark Logo Preview"
                                        class="h-9 max-w-[130px] object-contain rounded-lg"
                                        @error="darkPreviewFailed = true"
                                        @load="darkPreviewFailed = false"
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

                    <!-- Light Sidebar / Surface Simulation Box -->
                    <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800/90 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-semibold text-amber-400 uppercase tracking-wider flex items-center space-x-1">
                                <Sun class="w-3 h-3 text-amber-400" />
                                <span>{{ t('admin.logo_preview_light', 'Aydınlık Tema Önizlemesi') }}</span>
                            </span>
                            <span class="text-[9px] px-1.5 py-0.5 rounded bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                Light Mode
                            </span>
                        </div>
                        <div class="p-3 rounded-xl bg-white border border-slate-200 flex items-center justify-between shadow-sm">
                            <div class="flex items-center space-x-3 min-w-0">
                                <template v-if="activeLightPreview && !lightPreviewFailed">
                                    <img
                                        :key="activeLightPreview"
                                        :src="activeLightPreview"
                                        alt="Light Logo Preview"
                                        class="h-9 max-w-[130px] object-contain rounded-lg"
                                        @error="lightPreviewFailed = true"
                                        @load="lightPreviewFailed = false"
                                    />
                                </template>
                                <template v-else>
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center shadow-md shrink-0">
                                        <Activity class="w-5 h-5 text-white" />
                                    </div>
                                    <span class="text-xl font-bold tracking-tight text-slate-900 truncate">
                                        {{ form.brand_name || 'Seovy' }}
                                    </span>
                                </template>
                            </div>
                            <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 shrink-0">
                                Owner
                            </span>
                        </div>

                        <!-- Simulated Light Plan Badge -->
                        <div class="px-1 pt-1">
                            <div class="flex items-center justify-between px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-xs">
                                <div class="flex items-center space-x-2">
                                    <span class="relative flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-600"></span>
                                    </span>
                                    <div class="flex flex-col">
                                        <span class="text-[9px] uppercase tracking-wider font-semibold text-slate-500 leading-tight">
                                            {{ t('nav.active_plan', 'Aktif Paket') }}
                                        </span>
                                        <span class="text-xs font-bold text-slate-800 truncate">
                                            Pro Plan
                                        </span>
                                    </div>
                                </div>
                                <span class="text-[10px] text-indigo-600 font-medium">
                                    {{ t('nav.upgrade', 'Yükselt') }} &rarr;
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Browser Tab Simulation (Favicon Preview) -->
                    <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800/90 space-y-2">
                        <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider block">
                            {{ t('admin.logo_preview_browser_tab', 'Tarayıcı Sekmesi & Favicon Önizlemesi') }}
                        </span>
                        <div class="bg-slate-900 border border-slate-800 rounded-xl p-2.5 flex items-center space-x-2.5 shadow-sm">
                            <div class="w-5 h-5 rounded flex items-center justify-center overflow-hidden shrink-0 bg-slate-800 border border-slate-700/60">
                                <img
                                    v-if="activeFaviconPreview && !faviconPreviewFailed"
                                    :key="activeFaviconPreview"
                                    :src="activeFaviconPreview"
                                    alt="Favicon"
                                    class="w-4 h-4 object-contain"
                                    @error="faviconPreviewFailed = true"
                                    @load="faviconPreviewFailed = false"
                                />
                                <Activity v-else class="w-3.5 h-3.5 text-indigo-400" />
                            </div>
                            <span class="text-xs font-medium text-slate-200 truncate">
                                {{ form.brand_name || 'Seovy' }} — SEO & Technical Crawler
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>
