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
    Globe,
    Moon,
    Sun,
    Sparkles,
    CheckCircle2,
    Type,
    X,
    Activity
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

// Selected file previews (Object URLs)
const darkPreviewUrl = ref<string | null>(null);
const lightPreviewUrl = ref<string | null>(null);
const faviconPreviewUrl = ref<string | null>(null);

// URL input modes toggle
const showDarkUrlInput = ref(false);
const showLightUrlInput = ref(false);
const showFaviconUrlInput = ref(false);

// Drag states
const isDraggingDark = ref(false);
const isDraggingLight = ref(false);
const isDraggingFavicon = ref(false);

const isSaving = ref(false);
const isResetting = ref(false);
const saveSuccessMessage = ref<string | null>(null);
const globalErrorMessage = ref<string | null>(null);

const form = useForm({
    brand_name: currentBrandName.value,
    logo_dark_file: null as File | null,
    logo_dark_url: currentDarkLogo.value || '',
    logo_light_file: null as File | null,
    logo_light_url: currentLightLogo.value || '',
    favicon_file: null as File | null,
    favicon_url: currentFavicon.value || '',
});

// Sync with incoming server state
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

// MAX FILE SIZE: 2MB in bytes
const MAX_FILE_SIZE = 2 * 1024 * 1024;

const validateFile = (file: File): boolean => {
    if (file.size > MAX_FILE_SIZE) {
        const sizeMb = (file.size / (1024 * 1024)).toFixed(1);
        globalErrorMessage.value = `"${file.name}" dosyası ${sizeMb} MB. Sunucu yükleme sınırı 2 MB'tır. Lütfen daha küçük bir görsel seçin veya SVG formatı kullanın.`;
        return false;
    }
    const ext = file.name.split('.').pop()?.toLowerCase() || '';
    const validExts = ['png', 'jpg', 'jpeg', 'svg', 'webp', 'ico'];
    if (!validExts.includes(ext)) {
        globalErrorMessage.value = `"${file.name}" dosya türü desteklenmiyor. Desteklenen türler: PNG, JPG, SVG, WebP, ICO.`;
        return false;
    }
    globalErrorMessage.value = null;
    return true;
};

// Dark logo file handlers
const handleDarkFile = (file: File) => {
    if (!validateFile(file)) return;
    form.logo_dark_file = file;
    darkPreviewUrl.value = URL.createObjectURL(file);
};
const onDarkFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        handleDarkFile(target.files[0]);
    }
};
const onDarkDrop = (e: DragEvent) => {
    isDraggingDark.value = false;
    if (e.dataTransfer?.files && e.dataTransfer.files[0]) {
        handleDarkFile(e.dataTransfer.files[0]);
    }
};
const clearDarkFile = () => {
    form.logo_dark_file = null;
    darkPreviewUrl.value = null;
    if (darkFileInputRef.value) darkFileInputRef.value.value = '';
};

// Light logo file handlers
const handleLightFile = (file: File) => {
    if (!validateFile(file)) return;
    form.logo_light_file = file;
    lightPreviewUrl.value = URL.createObjectURL(file);
};
const onLightFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        handleLightFile(target.files[0]);
    }
};
const onLightDrop = (e: DragEvent) => {
    isDraggingLight.value = false;
    if (e.dataTransfer?.files && e.dataTransfer.files[0]) {
        handleLightFile(e.dataTransfer.files[0]);
    }
};
const clearLightFile = () => {
    form.logo_light_file = null;
    lightPreviewUrl.value = null;
    if (lightFileInputRef.value) lightFileInputRef.value.value = '';
};

// Favicon file handlers
const handleFaviconFile = (file: File) => {
    if (!validateFile(file)) return;
    form.favicon_file = file;
    faviconPreviewUrl.value = URL.createObjectURL(file);
};
const onFaviconChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        handleFaviconFile(target.files[0]);
    }
};
const onFaviconDrop = (e: DragEvent) => {
    isDraggingFavicon.value = false;
    if (e.dataTransfer?.files && e.dataTransfer.files[0]) {
        handleFaviconFile(e.dataTransfer.files[0]);
    }
};
const clearFaviconFile = () => {
    form.favicon_file = null;
    faviconPreviewUrl.value = null;
    if (faviconInputRef.value) faviconInputRef.value.value = '';
};

// Active preview displays
const displayDarkLogo = computed(() => {
    if (darkPreviewUrl.value) return darkPreviewUrl.value;
    if (showDarkUrlInput.value && form.logo_dark_url?.trim()) return form.logo_dark_url.trim();
    return currentDarkLogo.value;
});

const displayLightLogo = computed(() => {
    if (lightPreviewUrl.value) return lightPreviewUrl.value;
    if (showLightUrlInput.value && form.logo_light_url?.trim()) return form.logo_light_url.trim();
    return currentLightLogo.value || currentDarkLogo.value;
});

const displayFavicon = computed(() => {
    if (faviconPreviewUrl.value) return faviconPreviewUrl.value;
    if (showFaviconUrlInput.value && form.favicon_url?.trim()) return form.favicon_url.trim();
    return currentFavicon.value || displayDarkLogo.value;
});

const darkPreviewFailed = ref(false);
const lightPreviewFailed = ref(false);
const faviconPreviewFailed = ref(false);

watch(displayDarkLogo, () => {
    darkPreviewFailed.value = false;
});
watch(displayLightLogo, () => {
    lightPreviewFailed.value = false;
});
watch(displayFavicon, () => {
    faviconPreviewFailed.value = false;
});

const submitForm = () => {
    saveSuccessMessage.value = null;
    globalErrorMessage.value = null;
    isSaving.value = true;

    // Send only populated fields cleanly
    form.transform((data) => {
        const payload: Record<string, any> = {
            brand_name: data.brand_name?.trim() || 'Seovy',
        };

        if (data.logo_dark_file instanceof File) {
            payload.logo_dark_file = data.logo_dark_file;
        } else if (showDarkUrlInput.value && data.logo_dark_url?.trim()) {
            payload.logo_dark_url = data.logo_dark_url.trim();
        }

        if (data.logo_light_file instanceof File) {
            payload.logo_light_file = data.logo_light_file;
        } else if (showLightUrlInput.value && data.logo_light_url?.trim()) {
            payload.logo_light_url = data.logo_light_url.trim();
        }

        if (data.favicon_file instanceof File) {
            payload.favicon_file = data.favicon_file;
        } else if (showFaviconUrlInput.value && data.favicon_url?.trim()) {
            payload.favicon_url = data.favicon_url.trim();
        }

        return payload;
    });

    form.post('/admin/settings/logo', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            isSaving.value = false;
            clearDarkFile();
            clearLightFile();
            clearFaviconFile();
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
        globalErrorMessage.value = null;
        router.post('/admin/settings/logo', { action: 'reset' }, {
            preserveScroll: true,
            onFinish: () => {
                isResetting.value = false;
                clearDarkFile();
                clearLightFile();
                clearFaviconFile();
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
</script>

<template>
    <div class="p-6 rounded-3xl bg-slate-900/70 border border-slate-800 shadow-xl space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800/80 pb-5">
            <div class="flex items-center space-x-3.5">
                <div class="w-11 h-11 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center shadow-lg shadow-indigo-500/5 shrink-0">
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
                class="text-emerald-400/70 hover:text-emerald-300 ml-3 text-sm font-bold"
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
            <!-- Brand Name Input -->
            <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800/80 space-y-2">
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider flex items-center space-x-1.5">
                    <Type class="w-3.5 h-3.5 text-indigo-400" />
                    <span>{{ t('admin.logo_brand_name', 'Platform / Marka Adı') }}</span>
                </label>
                <div class="flex flex-col sm:flex-row gap-3">
                    <input
                        v-model="form.brand_name"
                        type="text"
                        placeholder="Seovy"
                        class="flex-1 px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                    />
                </div>
                <p class="text-[11px] text-slate-500">
                    {{ t('admin.logo_brand_name_hint', 'Logonun yanında veya logo bulunmadığında görüntülenecek platform adı.') }}
                </p>
            </div>

            <!-- TWO LOGOS SIDE-BY-SIDE: Dark Mode Logo & Light Mode Logo -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- 1. Dark Mode Logo Card -->
                <div class="p-5 rounded-2xl bg-slate-950/80 border border-slate-800/90 space-y-4 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                            <div class="flex items-center space-x-2">
                                <div class="w-7 h-7 rounded-lg bg-indigo-500/10 text-indigo-400 flex items-center justify-center">
                                    <Moon class="w-4 h-4" />
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-white uppercase tracking-wider">
                                        {{ t('admin.logo_tab_dark', 'Karanlık Tema Logosu') }}
                                    </h4>
                                    <span class="text-[11px] text-slate-500">Koyu zeminlerde görüntülenecek açık logo</span>
                                </div>
                            </div>
                            <span v-if="currentDarkLogo" class="w-2 h-2 rounded-full bg-emerald-400" title="Kayıtlı logo mevcut"></span>
                        </div>

                        <!-- Dark Logo Preview Area -->
                        <div class="mt-4 p-4 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center min-h-[90px] relative overflow-hidden">
                            <template v-if="displayDarkLogo && !darkPreviewFailed">
                                <img
                                    :key="displayDarkLogo"
                                    :src="displayDarkLogo"
                                    alt="Dark Logo"
                                    class="max-h-12 max-w-[200px] object-contain"
                                    @error="darkPreviewFailed = true"
                                    @load="darkPreviewFailed = false"
                                />
                            </template>
                            <template v-else>
                                <div class="flex items-center space-x-2 text-slate-500 text-xs font-medium">
                                    <Activity class="w-4 h-4 text-indigo-400" />
                                    <span>{{ form.brand_name || 'Seovy' }} (Varsayılan)</span>
                                </div>
                            </template>
                        </div>

                        <!-- Dark Logo File Dropzone -->
                        <div class="mt-3 space-y-2">
                            <input
                                ref="darkFileInputRef"
                                type="file"
                                accept=".svg, image/svg+xml, .png, image/png, .jpg, .jpeg, image/jpeg, .webp, image/webp"
                                class="hidden"
                                @change="onDarkFileChange"
                            />

                            <div
                                @click="darkFileInputRef?.click()"
                                @dragover.prevent="isDraggingDark = true"
                                @dragleave.prevent="isDraggingDark = false"
                                @drop.prevent="onDarkDrop"
                                class="border-2 border-dashed rounded-xl p-4 flex flex-col items-center justify-center text-center cursor-pointer transition-all"
                                :class="isDraggingDark ? 'border-indigo-400 bg-indigo-950/40' : 'border-slate-700/80 hover:border-indigo-500/80 bg-slate-900/40 hover:bg-slate-900/70'"
                            >
                                <Upload class="w-5 h-5 text-indigo-400 mb-1.5" />
                                <span class="text-xs font-semibold text-white">
                                    {{ form.logo_dark_file ? form.logo_dark_file.name : 'Karanlık tema logosu seç veya sürükle' }}
                                </span>
                                <span class="text-[11px] text-slate-500 mt-0.5">PNG, SVG, JPG veya WebP (Maks 2 MB)</span>
                            </div>

                            <!-- Selected File Badge -->
                            <div v-if="form.logo_dark_file" class="flex items-center justify-between text-xs text-indigo-300 bg-indigo-950/40 border border-indigo-500/30 p-2 rounded-lg">
                                <span class="truncate font-mono text-[11px]">{{ form.logo_dark_file.name }} ({{ Math.round(form.logo_dark_file.size / 1024) }} KB)</span>
                                <button type="button" @click="clearDarkFile" class="text-slate-400 hover:text-rose-400 ml-2">
                                    <X class="w-3.5 h-3.5" />
                                </button>
                            </div>

                            <!-- URL Input Toggle -->
                            <div class="pt-1">
                                <button
                                    type="button"
                                    @click="showDarkUrlInput = !showDarkUrlInput"
                                    class="text-[11px] text-indigo-400 hover:text-indigo-300 flex items-center space-x-1"
                                >
                                    <Globe class="w-3 h-3" />
                                    <span>{{ showDarkUrlInput ? 'URL girişini gizle' : 'Veya doğrudan logo URL adresi gir' }}</span>
                                </button>
                                <input
                                    v-if="showDarkUrlInput"
                                    v-model="form.logo_dark_url"
                                    type="url"
                                    placeholder="https://example.com/logo-white.svg"
                                    class="mt-2 w-full px-3 py-2 rounded-lg bg-slate-900 border border-slate-800 text-xs text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Light Mode Logo Card -->
                <div class="p-5 rounded-2xl bg-slate-950/80 border border-slate-800/90 space-y-4 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                            <div class="flex items-center space-x-2">
                                <div class="w-7 h-7 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center">
                                    <Sun class="w-4 h-4" />
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-white uppercase tracking-wider">
                                        {{ t('admin.logo_tab_light', 'Aydınlık Tema Logosu') }}
                                    </h4>
                                    <span class="text-[11px] text-slate-500">Açık zeminlerde görüntülenecek koyu logo</span>
                                </div>
                            </div>
                            <span v-if="currentLightLogo" class="w-2 h-2 rounded-full bg-emerald-400" title="Kayıtlı logo mevcut"></span>
                        </div>

                        <!-- Light Logo Preview Area (Light background so dark logo is clearly visible) -->
                        <div class="mt-4 p-4 rounded-xl bg-slate-100 border border-slate-300 flex items-center justify-center min-h-[90px] relative overflow-hidden">
                            <template v-if="displayLightLogo && !lightPreviewFailed">
                                <img
                                    :key="displayLightLogo"
                                    :src="displayLightLogo"
                                    alt="Light Logo"
                                    class="max-h-12 max-w-[200px] object-contain"
                                    @error="lightPreviewFailed = true"
                                    @load="lightPreviewFailed = false"
                                />
                            </template>
                            <template v-else>
                                <div class="flex items-center space-x-2 text-slate-700 text-xs font-medium">
                                    <Activity class="w-4 h-4 text-indigo-600" />
                                    <span>{{ form.brand_name || 'Seovy' }} (Varsayılan)</span>
                                </div>
                            </template>
                        </div>

                        <!-- Light Logo File Dropzone -->
                        <div class="mt-3 space-y-2">
                            <input
                                ref="lightFileInputRef"
                                type="file"
                                accept=".svg, image/svg+xml, .png, image/png, .jpg, .jpeg, image/jpeg, .webp, image/webp"
                                class="hidden"
                                @change="onLightFileChange"
                            />

                            <div
                                @click="lightFileInputRef?.click()"
                                @dragover.prevent="isDraggingLight = true"
                                @dragleave.prevent="isDraggingLight = false"
                                @drop.prevent="onLightDrop"
                                class="border-2 border-dashed rounded-xl p-4 flex flex-col items-center justify-center text-center cursor-pointer transition-all"
                                :class="isDraggingLight ? 'border-amber-400 bg-amber-950/40' : 'border-slate-700/80 hover:border-amber-500/80 bg-slate-900/40 hover:bg-slate-900/70'"
                            >
                                <Upload class="w-5 h-5 text-amber-400 mb-1.5" />
                                <span class="text-xs font-semibold text-white">
                                    {{ form.logo_light_file ? form.logo_light_file.name : 'Aydınlık tema logosu seç veya sürükle' }}
                                </span>
                                <span class="text-[11px] text-slate-500 mt-0.5">PNG, SVG, JPG veya WebP (Maks 2 MB)</span>
                            </div>

                            <!-- Selected File Badge -->
                            <div v-if="form.logo_light_file" class="flex items-center justify-between text-xs text-amber-300 bg-amber-950/40 border border-amber-500/30 p-2 rounded-lg">
                                <span class="truncate font-mono text-[11px]">{{ form.logo_light_file.name }} ({{ Math.round(form.logo_light_file.size / 1024) }} KB)</span>
                                <button type="button" @click="clearLightFile" class="text-slate-400 hover:text-rose-400 ml-2">
                                    <X class="w-3.5 h-3.5" />
                                </button>
                            </div>

                            <!-- URL Input Toggle -->
                            <div class="pt-1">
                                <button
                                    type="button"
                                    @click="showLightUrlInput = !showLightUrlInput"
                                    class="text-[11px] text-amber-400 hover:text-amber-300 flex items-center space-x-1"
                                >
                                    <Globe class="w-3 h-3" />
                                    <span>{{ showLightUrlInput ? 'URL girişini gizle' : 'Veya doğrudan logo URL adresi gir' }}</span>
                                </button>
                                <input
                                    v-if="showLightUrlInput"
                                    v-model="form.logo_light_url"
                                    type="url"
                                    placeholder="https://example.com/logo-dark.svg"
                                    class="mt-2 w-full px-3 py-2 rounded-lg bg-slate-900 border border-slate-800 text-xs text-white placeholder-slate-600 focus:outline-none focus:border-amber-500"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Favicon Section -->
            <div class="p-5 rounded-2xl bg-slate-950/80 border border-slate-800/90 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                    <div class="flex items-center space-x-2">
                        <div class="w-7 h-7 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center">
                            <Sparkles class="w-4 h-4" />
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-white uppercase tracking-wider">
                                {{ t('admin.logo_favicon_title', 'Favicon (Tarayıcı Sekme Simgesi)') }}
                            </h4>
                            <span class="text-[11px] text-slate-500">Tarayıcı sekmelerinde görüntülenecek simge (16x16 / 32x32)</span>
                        </div>
                    </div>
                    <span v-if="currentFavicon" class="w-2 h-2 rounded-full bg-emerald-400" title="Kayıtlı favicon mevcut"></span>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-4">
                    <!-- Favicon Preview -->
                    <div class="w-14 h-14 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center p-2 shrink-0">
                        <template v-if="displayFavicon && !faviconPreviewFailed">
                            <img
                                :key="displayFavicon"
                                :src="displayFavicon"
                                alt="Favicon"
                                class="w-8 h-8 object-contain rounded"
                                @error="faviconPreviewFailed = true"
                                @load="faviconPreviewFailed = false"
                            />
                        </template>
                        <template v-else>
                            <Sparkles class="w-6 h-6 text-amber-400" />
                        </template>
                    </div>

                    <!-- Dropzone -->
                    <div class="flex-1 w-full space-y-2">
                        <input
                            ref="faviconInputRef"
                            type="file"
                            accept=".ico, image/x-icon, image/vnd.microsoft.icon, .svg, image/svg+xml, .png, image/png, .webp, image/webp"
                            class="hidden"
                            @change="onFaviconChange"
                        />
                        <div
                            @click="faviconInputRef?.click()"
                            @dragover.prevent="isDraggingFavicon = true"
                            @dragleave.prevent="isDraggingFavicon = false"
                            @drop.prevent="onFaviconDrop"
                            class="border-2 border-dashed rounded-xl p-3 flex items-center justify-center text-center cursor-pointer transition-all"
                            :class="isDraggingFavicon ? 'border-amber-400 bg-amber-950/40' : 'border-slate-700/80 hover:border-amber-500/80 bg-slate-900/40 hover:bg-slate-900/70'"
                        >
                            <Upload class="w-4 h-4 text-amber-400 mr-2 shrink-0" />
                            <span class="text-xs font-medium text-white">
                                {{ form.favicon_file ? form.favicon_file.name : 'Favicon dosyası seç veya sürükle (.ico, .png, .svg)' }}
                            </span>
                        </div>

                        <div v-if="form.favicon_file" class="flex items-center justify-between text-xs text-amber-300 bg-amber-950/40 border border-amber-500/30 p-2 rounded-lg">
                            <span class="truncate font-mono text-[11px]">{{ form.favicon_file.name }} ({{ Math.round(form.favicon_file.size / 1024) }} KB)</span>
                            <button type="button" @click="clearFaviconFile" class="text-slate-400 hover:text-rose-400 ml-2">
                                <X class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button Bar -->
            <div class="pt-2 flex items-center justify-between">
                <button
                    type="submit"
                    :disabled="form.processing || isSaving"
                    class="px-6 py-3 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/30 transition-all flex items-center space-x-2 disabled:opacity-50 cursor-pointer disabled:cursor-not-allowed"
                >
                    <Check class="w-4 h-4" :class="{ 'animate-spin': form.processing || isSaving }" />
                    <span>{{ (form.processing || isSaving) ? t('admin.logo_saving', 'Kaydediliyor...') : t('admin.logo_save_btn', 'Logoları & Markayı Kaydet') }}</span>
                </button>
                <span class="text-[11px] text-slate-500">
                    Karanlık ve aydınlık tema logoları anında aktif hale gelir.
                </span>
            </div>
        </form>
    </div>
</template>
