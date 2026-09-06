<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    TrendingUp,
    Plus,
    Upload,
    ArrowLeft,
    RefreshCw,
    Search,
    AlertCircle,
    CheckCircle2,
    ArrowUp,
    ArrowDown,
    Minus
} from 'lucide-vue-next';

const props = defineProps<{
    project: any;
    keywords: {
        data: Array<any>;
        links: Array<any>;
        total: number;
    };
    providerConfigured: boolean;
    providerName: string;
    filters: {
        search?: string;
    };
}>();

const showAddModal = ref(false);
const showImportModal = ref(false);

const addForm = useForm({
    keyword: '',
    target_url: '',
});

const submitAdd = () => {
    addForm.post(`/projects/${props.project.id}/keywords`, {
        onSuccess: () => {
            showAddModal.value = false;
            addForm.reset();
        },
    });
};

const importForm = useForm({
    file: null as File | null,
});

const submitImport = () => {
    importForm.post(`/projects/${props.project.id}/keywords/import-csv`, {
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
        },
    });
};

const checkRankings = () => {
    router.post(`/projects/${props.project.id}/keywords/check`);
};
</script>

<template>
    <AppLayout :title="`Sıra Takibi - ${project.name}`">
        <Head :title="`Sıra Takibi - ${project.name}`" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center space-x-3">
                    <Link :href="`/projects/${project.id}`" class="p-2 rounded-xl bg-slate-900 border border-slate-800 text-slate-400 hover:text-white transition-colors">
                        <ArrowLeft class="w-4 h-4" />
                    </Link>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-white">Anahtar Kelime Sıra Takibi</h1>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Google arama pozisyonları ve geçmiş sıralama değişimleri ({{ keywords.total }} kelime).
                        </p>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <button
                        @click="showImportModal = true"
                        class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-white text-xs font-semibold flex items-center space-x-1.5 transition-all"
                    >
                        <Upload class="w-3.5 h-3.5 text-indigo-400" />
                        <span>CSV İçe Aktar</span>
                    </button>

                    <button
                        @click="showAddModal = true"
                        class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 flex items-center space-x-1.5 transition-all"
                    >
                        <Plus class="w-3.5 h-3.5" />
                        <span>Kelime Ekle</span>
                    </button>

                    <button
                        @click="checkRankings"
                        class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold flex items-center space-x-1.5 transition-all"
                    >
                        <RefreshCw class="w-3.5 h-3.5" />
                        <span>Sıralamaları Kontrol Et</span>
                    </button>
                </div>
            </div>

            <!-- Provider Status Banner -->
            <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 flex items-center justify-between text-xs">
                <div class="flex items-center space-x-2">
                    <span class="text-slate-400">Aktif SERP Sağlayıcı:</span>
                    <span class="font-bold text-white">{{ providerName }}</span>
                    <span
                        class="px-2 py-0.5 rounded-full text-[10px] font-semibold"
                        :class="providerConfigured ? 'bg-emerald-500/10 text-emerald-400' : 'bg-amber-500/10 text-amber-400'"
                    >
                        {{ providerConfigured ? 'Aktif' : 'Harici API Yok (Lokal/CSV Modu)' }}
                    </span>
                </div>
                <div v-if="!providerConfigured" class="text-slate-500 hidden sm:block">
                    Gerçek API anahtarı olmadan sahte veri gösterilmez; CSV içe aktarımını kullanabilirsiniz.
                </div>
            </div>

            <!-- Keywords Table -->
            <div v-if="keywords.data.length === 0" class="p-12 rounded-3xl bg-slate-900/40 border border-slate-800 text-center text-xs text-slate-500">
                <TrendingUp class="w-12 h-12 text-slate-600 mx-auto mb-3" />
                <h3 class="text-sm font-semibold text-white">Henüz izlenen anahtar kelime yok</h3>
                <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1 mb-4">
                    Sitenizin arama performansını takip etmek için hedef kelimelerinizi ekleyin veya CSV yükleyin.
                </p>
                <button
                    @click="showAddModal = true"
                    class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs"
                >
                    + İlk Kelimeyi Ekle
                </button>
            </div>

            <div v-else class="rounded-3xl bg-slate-900/50 border border-slate-800/80 overflow-hidden">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="p-4">Anahtar Kelime</th>
                            <th class="p-4">Mevcut Sıra</th>
                            <th class="p-4">Değişim</th>
                            <th class="p-4">Arama Hacmi</th>
                            <th class="p-4">Hedef Sayfa</th>
                            <th class="p-4 text-right">Eylem</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <tr v-for="kw in keywords.data" :key="kw.id" class="hover:bg-slate-800/30 transition-colors">
                            <td class="p-4 font-bold text-white">{{ kw.keyword }}</td>
                            <td class="p-4">
                                <span v-if="kw.current_position" class="font-extrabold text-sm text-indigo-400">
                                    #{{ kw.current_position }}
                                </span>
                                <span v-else class="text-slate-500">—</span>
                            </td>
                            <td class="p-4">
                                <span
                                    v-if="kw.previous_position && kw.current_position"
                                    class="flex items-center space-x-1 font-semibold"
                                    :class="kw.previous_position > kw.current_position ? 'text-emerald-400' : (kw.previous_position < kw.current_position ? 'text-rose-400' : 'text-slate-400')"
                                >
                                    <ArrowUp v-if="kw.previous_position > kw.current_position" class="w-3.5 h-3.5" />
                                    <ArrowDown v-else-if="kw.previous_position < kw.current_position" class="w-3.5 h-3.5" />
                                    <Minus v-else class="w-3.5 h-3.5" />
                                    <span>{{ Math.abs(kw.previous_position - kw.current_position) }}</span>
                                </span>
                                <span v-else class="text-slate-500">—</span>
                            </td>
                            <td class="p-4 text-slate-300">{{ kw.search_volume ? kw.search_volume.toLocaleString() : '—' }}</td>
                            <td class="p-4 text-slate-400 max-w-xs truncate font-mono text-[11px]">{{ kw.target_url || '—' }}</td>
                            <td class="p-4 text-right">
                                <Link
                                    :href="`/projects/${project.id}/keywords/${kw.id}`"
                                    method="delete"
                                    as="button"
                                    class="text-rose-400 hover:text-rose-300 font-semibold text-xs"
                                >
                                    Sil
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Add Keyword Modal -->
            <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
                <div class="w-full max-w-md bg-slate-900 border border-slate-800 p-6 rounded-3xl space-y-4 shadow-2xl">
                    <h2 class="text-base font-bold text-white">Yeni Anahtar Kelime Ekle</h2>
                    <form @submit.prevent="submitAdd" class="space-y-3 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Anahtar Kelime</label>
                            <input
                                v-model="addForm.keyword"
                                type="text"
                                required
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white"
                                placeholder="Örn: istanbul diş kliniği"
                            />
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Hedef Sayfa URL'si (Opsiyonel)</label>
                            <input
                                v-model="addForm.target_url"
                                type="url"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-mono"
                                placeholder="https://example.com/hizmetler"
                            />
                        </div>
                        <div class="pt-3 flex items-center justify-end space-x-2">
                            <button
                                type="button"
                                @click="showAddModal = false"
                                class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 hover:text-white"
                            >
                                Vazgeç
                            </button>
                            <button
                                type="submit"
                                :disabled="addForm.processing"
                                class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold"
                            >
                                Ekle
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Import CSV Modal -->
            <div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
                <div class="w-full max-w-md bg-slate-900 border border-slate-800 p-6 rounded-3xl space-y-4 shadow-2xl">
                    <h2 class="text-base font-bold text-white">CSV İle Toplu Kelime İçe Aktar</h2>
                    <p class="text-xs text-slate-400">
                        Format: İlk sütun kelime, ikinci sütun pozisyon (opsiyonel), üçüncü sütun arama hacmi (opsiyonel).
                    </p>
                    <form @submit.prevent="submitImport" class="space-y-4 text-xs">
                        <input
                            type="file"
                            accept=".csv,.txt"
                            required
                            @change="importForm.file = ($event.target as any).files[0]"
                            class="w-full text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-white hover:file:bg-slate-700"
                        />
                        <div class="pt-3 flex items-center justify-end space-x-2">
                            <button
                                type="button"
                                @click="showImportModal = false"
                                class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 hover:text-white"
                            >
                                Vazgeç
                            </button>
                            <button
                                type="submit"
                                :disabled="importForm.processing"
                                class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold"
                            >
                                İçe Aktar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
