<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Globe, ArrowLeft, ArrowRight, Settings2, ShieldCheck } from 'lucide-vue-next';

const form = useForm({
    name: '',
    start_url: 'https://',
    target_country: 'TR',
    target_language: 'tr',
    timezone: 'Europe/Istanbul',
    crawl_settings: {
        max_depth: 3,
        max_pages: 250,
        respect_robots: true,
        follow_subdomains: false,
        rate_limit: 5,
    },
});

const submit = () => {
    form.post('/projects');
};
</script>

<template>
    <AppLayout title="Yeni Web Sitesi Ekle">
        <Head title="Yeni Web Sitesi Ekle" />

        <div class="max-w-3xl mx-auto space-y-6">
            <div class="flex items-center space-x-3">
                <Link href="/projects" class="p-2 rounded-xl bg-slate-900 border border-slate-800 text-slate-400 hover:text-white transition-colors">
                    <ArrowLeft class="w-4 h-4" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-white">Yeni Web Sitesi Ekle</h1>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Teknik SEO taraması ve performans takibi için sitenizi tanımlayın.
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="p-8 rounded-3xl bg-slate-900/50 border border-slate-800/80 space-y-6">
                <!-- Project Name -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Site / Marka Adı
                    </label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        class="w-full px-4 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500"
                        placeholder="Örnek: E-Ticaret Sitem"
                    />
                    <div v-if="form.errors.name" class="mt-1 text-xs text-rose-400">{{ form.errors.name }}</div>
                </div>

                <!-- Start URL -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Başlangıç URL'si
                    </label>
                    <div class="relative">
                        <input
                            v-model="form.start_url"
                            type="url"
                            required
                            class="w-full px-4 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-sm text-white font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500"
                            placeholder="https://example.com"
                        />
                    </div>
                    <p class="mt-1.5 text-[11px] text-slate-500">
                        Tarayıcı motoru bu adresten başlayarak sitenizin tüm dahili sayfalarını keşfedecektir.
                    </p>
                    <div v-if="form.errors.start_url" class="mt-1 text-xs text-rose-400">{{ form.errors.start_url }}</div>
                </div>

                <!-- Country, Language, Timezone Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                            Hedef Ülke
                        </label>
                        <select
                            v-model="form.target_country"
                            class="w-full px-3 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                        >
                            <option value="TR">Türkiye (TR)</option>
                            <option value="US">Amerika Birleşik Devletleri (US)</option>
                            <option value="GB">Birleşik Krallık (GB)</option>
                            <option value="DE">Almanya (DE)</option>
                            <option value="FR">Fransa (FR)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                            Hedef Dil
                        </label>
                        <select
                            v-model="form.target_language"
                            class="w-full px-3 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                        >
                            <option value="tr">Türkçe (tr)</option>
                            <option value="en">İngilizce (en)</option>
                            <option value="de">Almanca (de)</option>
                            <option value="fr">Fransızca (fr)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                            Saat Dilimi
                        </label>
                        <select
                            v-model="form.timezone"
                            class="w-full px-3 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                        >
                            <option value="Europe/Istanbul">Europe/Istanbul (GMT+3)</option>
                            <option value="UTC">UTC</option>
                            <option value="Europe/London">Europe/London</option>
                            <option value="America/New_York">America/New_York</option>
                        </select>
                    </div>
                </div>

                <!-- Crawl Settings Accordion -->
                <div class="pt-4 border-t border-slate-800 space-y-4">
                    <div class="flex items-center space-x-2 text-sm font-semibold text-white">
                        <Settings2 class="w-4 h-4 text-indigo-400" />
                        <span>Varsayılan Tarama Parametreleri</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Maksimum Tarama Derinliği (1-8)</label>
                            <input
                                v-model.number="form.crawl_settings.max_depth"
                                type="number"
                                min="1"
                                max="8"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white"
                            />
                        </div>

                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Maksimum Sayfa Sayısı</label>
                            <input
                                v-model.number="form.crawl_settings.max_pages"
                                type="number"
                                min="5"
                                max="5000"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white"
                            />
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-4 pt-2">
                        <label class="flex items-center space-x-2 cursor-pointer text-xs text-slate-300">
                            <input
                                v-model="form.crawl_settings.respect_robots"
                                type="checkbox"
                                class="rounded bg-slate-800 border-slate-700 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span>Robots.txt kurallarına ve disallow direktiflerine uy</span>
                        </label>

                        <label class="flex items-center space-x-2 cursor-pointer text-xs text-slate-300">
                            <input
                                v-model="form.crawl_settings.follow_subdomains"
                                type="checkbox"
                                class="rounded bg-slate-800 border-slate-700 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span>Alt alan adlarını (subdomains) taramaya dahil et</span>
                        </label>
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-end space-x-3">
                    <Link
                        href="/projects"
                        class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold"
                    >
                        Vazgeç
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 flex items-center space-x-1.5 transition-all disabled:opacity-50"
                    >
                        <span>Projeyi Oluştur</span>
                        <ArrowRight class="w-4 h-4" />
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
