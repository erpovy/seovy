<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Search,
    ShieldCheck,
    BarChart3,
    Layers,
    FileText,
    Sparkles,
    CreditCard,
    Zap,
    Lock,
    ArrowRight,
    Activity,
    CheckCircle2
} from 'lucide-vue-next';
import LanguageSelector from '@/Components/LanguageSelector.vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
    laravelVersion?: string;
    phpVersion?: string;
}>();

const page = usePage();
const systemSettings = computed(() => ((page.props as any)?.system_settings) || {});
const logoDark = computed(() => systemSettings.value.logo_dark || systemSettings.value.logo || null);
const brandName = computed(() => systemSettings.value.brand_name || 'Seovy');
const logoFailed = ref(false);

const staticFeatures = computed(() => [
    {
        key: 'crawler',
        icon: Search,
        color: 'indigo',
        badge: t('features.crawler_badge', 'Motor v2.0'),
        title: t('features.crawler_title', 'SSRF Korumalı Asenkron Crawler'),
        description: t('features.crawler_desc', 'AWS metadata, localhost ve yerel IP bloklaması ile güvenli, saniyede onlarca sayfa tarayabilen asenkron crawler altyapısı.'),
        bg: 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20',
        badgeClass: 'bg-indigo-500/10 text-indigo-300 border-indigo-500/20',
        borderHover: 'hover:border-indigo-500/50',
    },
    {
        key: 'audit',
        icon: ShieldCheck,
        color: 'emerald',
        badge: t('features.audit_badge', '25+ Kural'),
        title: t('features.audit_title', 'Teknik SEO & Sayfa İçi Denetim'),
        description: t('features.audit_desc', 'Kırık linkler, HTTP durum kodları, başlık hiyerarşisi, canonical etiketler, OpenGraph ve meta robots otomatik denetimi.'),
        bg: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        badgeClass: 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20',
        borderHover: 'hover:border-emerald-500/50',
    },
    {
        key: 'rank_tracking',
        icon: BarChart3,
        color: 'cyan',
        badge: t('features.rank_badge', 'Canlı SERP'),
        title: t('features.rank_title', 'Canlı Google SERP Takibi'),
        description: t('features.rank_desc', 'Akıllı motor ve API destekli anahtar kelime sıralamaları, günlük değişim analizleri ve pozisyon geçmişi grafikleri.'),
        bg: 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20',
        badgeClass: 'bg-cyan-500/10 text-cyan-300 border-cyan-500/20',
        borderHover: 'hover:border-cyan-500/50',
    },
    {
        key: 'workspaces',
        icon: Layers,
        color: 'violet',
        badge: t('features.workspaces_badge', 'İşbirliği'),
        title: t('features.workspaces_title', 'Çoklu Çalışma Alanı & Ekip Rolleri'),
        description: t('features.workspaces_desc', 'Müşterileriniz ve projeleriniz için izole çalışma alanları. Yönetici, editör ve görüntüleyici rol bazlı izin matrisi.'),
        bg: 'bg-violet-500/10 text-violet-400 border-violet-500/20',
        badgeClass: 'bg-violet-500/10 text-violet-300 border-violet-500/20',
        borderHover: 'hover:border-violet-500/50',
    },
    {
        key: 'reports',
        icon: FileText,
        color: 'amber',
        badge: t('features.reports_badge', 'White-Label'),
        title: t('features.reports_title', 'Özelleştirilebilir PDF & CSV Raporları'),
        description: t('features.reports_desc', 'Kendi şirket logonuz ve marka kimliğinizle tek tıkla profesyonel denetim raporları oluşturun ve paylaşın.'),
        bg: 'bg-amber-500/10 text-amber-400 border-amber-500/20',
        badgeClass: 'bg-amber-500/10 text-amber-300 border-amber-500/20',
        borderHover: 'hover:border-amber-500/50',
    },
    {
        key: 'ai_insights',
        icon: Sparkles,
        color: 'pink',
        badge: t('features.ai_badge', 'Yapay Zeka'),
        title: t('features.ai_title', 'Yapay Zeka Destekli SEO Önerileri'),
        description: t('features.ai_desc', 'İçerik optimizasyonu, meta etiket üretimi ve teknik hataların düzeltilmesi için yapay zeka destekli akıllı öneriler.'),
        bg: 'bg-pink-500/10 text-pink-400 border-pink-500/20',
        badgeClass: 'bg-pink-500/10 text-pink-300 border-pink-500/20',
        borderHover: 'hover:border-pink-500/50',
    },
    {
        key: 'billing',
        icon: CreditCard,
        color: 'indigo',
        badge: t('features.billing_badge', 'Entegrasyon'),
        title: t('features.billing_title', 'Çoklu Sanal POS & Abonelik Yönetimi'),
        description: t('features.billing_desc', 'Stripe, PayTR, Iyzico, Paddle ve 10+ ödeme altyapısı ile tam uyumlu otomatik faturalandırma ve kota sistemi.'),
        bg: 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20',
        badgeClass: 'bg-indigo-500/10 text-indigo-300 border-indigo-500/20',
        borderHover: 'hover:border-indigo-500/50',
    },
    {
        key: 'branding',
        icon: Zap,
        color: 'amber',
        badge: t('features.branding_badge', 'Kişiselleştirme'),
        title: t('features.branding_title', 'Özel Markalama & Çift Tema'),
        description: t('features.branding_desc', 'Karanlık ve aydınlık tema desteği, özel logo yükleme, dinamik favicon ve tam beyaz etiket (white-label) görünüm.'),
        bg: 'bg-amber-500/10 text-amber-400 border-amber-500/20',
        badgeClass: 'bg-amber-500/10 text-amber-300 border-amber-500/20',
        borderHover: 'hover:border-amber-500/50',
    },
    {
        key: 'security',
        icon: Lock,
        color: 'emerald',
        badge: t('features.security_badge', 'Gizlilik'),
        title: t('features.security_title', 'Tam Veri Gizliliği & Güvenlik'),
        description: t('features.security_desc', 'Kendi sunucunuzda çalışan, verilerinizin 3. taraflarla paylaşılmadığı, tam şifrelemeli bağımsız SaaS mimarisi.'),
        bg: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        badgeClass: 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20',
        borderHover: 'hover:border-emerald-500/50',
    },
]);
</script>

<template>
    <Head :title="`${$t('features.page_title', 'Platform Özellikleri')} - ${brandName}`" />

    <div class="min-h-screen bg-slate-950 text-slate-100 selection:bg-indigo-500 selection:text-white flex flex-col justify-between">
        <!-- Top Navigation -->
        <header class="border-b border-slate-800/80 bg-slate-900/40 backdrop-blur-md sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 min-h-[76px] py-2 flex items-center justify-between">
                <Link href="/" class="flex items-center space-x-3 group py-1">
                    <template v-if="logoDark && !logoFailed">
                        <img
                            :key="logoDark"
                            :src="logoDark"
                            :alt="brandName"
                            class="h-[72px] max-w-[340px] object-contain object-left rounded-lg shadow-sm"
                            @error="logoFailed = true"
                            @load="logoFailed = false"
                        />
                    </template>
                    <template v-else>
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center shadow-lg shadow-indigo-500/20 group-hover:scale-105 transition-transform">
                            <Activity class="w-5 h-5 text-white" />
                        </div>
                        <span class="text-xl font-bold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-white via-slate-200 to-slate-400">
                            {{ brandName }}
                        </span>
                    </template>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 font-medium">
                        v1.0
                    </span>
                </Link>

                <nav class="flex items-center space-x-3 sm:space-x-4">
                    <Link
                        href="/"
                        class="px-3 py-2 text-sm font-medium text-slate-400 hover:text-white transition-colors"
                    >
                        {{ $t('features.home_link', 'Ana Sayfa') }}
                    </Link>

                    <Link
                        href="/features"
                        class="px-3 py-2 text-sm font-semibold text-indigo-400 border-b-2 border-indigo-500 transition-colors"
                    >
                        {{ $t('features.features_link', 'Özellikler') }}
                    </Link>

                    <LanguageSelector placement="bottom" />

                    <Link
                        v-if="$page.props.auth?.user"
                        href="/dashboard"
                        class="px-4 py-2 text-sm font-medium rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white transition-all shadow-md shadow-indigo-600/30 flex items-center space-x-1.5"
                    >
                        <span>{{ $t('welcome.dashboard_button', 'Kontrol Paneli') }}</span>
                        <ArrowRight class="w-4 h-4" />
                    </Link>
                    <template v-else>
                        <Link
                            href="/login"
                            class="px-3 sm:px-4 py-2 text-sm font-medium text-slate-300 hover:text-white transition-colors"
                        >
                            {{ $t('welcome.login', 'Giriş Yap') }}
                        </Link>
                        <Link
                            href="/register"
                            class="px-3 sm:px-4 py-2 text-sm font-medium rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white transition-all shadow-md shadow-indigo-600/30"
                        >
                            {{ $t('welcome.register', 'Kayıt Ol') }}
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Hero Section -->
        <main class="flex-1">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24 text-center">
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-semibold mb-6">
                    <Zap class="w-3.5 h-3.5" />
                    <span>{{ $t('features.page_badge', 'Platform Mimarisi & Yetenekleri') }}</span>
                </div>

                <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight max-w-4xl mx-auto leading-tight sm:leading-tight">
                    {{ $t('features.hero_title', 'Kurumsal Düzeyde Teknik SEO & Tarama Altyapısı') }}
                </h1>

                <p class="mt-6 text-base sm:text-lg text-slate-400 max-w-2xl mx-auto font-normal leading-relaxed">
                    {{ $t('features.hero_subtitle', 'Kendi sunucunuzda çalışan, çoklu çalışma alanları, SSRF korumalı asenkron crawler ve 25+ teknik analiz kuralı içeren hepsi bir arada SEO platformu.') }}
                </p>

                <!-- Action CTA -->
                <div class="mt-8 flex items-center justify-center gap-3">
                    <Link
                        href="/register"
                        class="px-6 py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold shadow-lg shadow-indigo-600/25 flex items-center space-x-2 transition-all transform hover:-translate-y-0.5 text-sm"
                    >
                        <span>{{ $t('features.cta_start', 'Hemen Başlayın') }}</span>
                        <ArrowRight class="w-4 h-4" />
                    </Link>
                    <Link
                        href="/"
                        class="px-6 py-3 rounded-xl bg-slate-900/80 hover:bg-slate-800 text-slate-300 hover:text-white font-medium border border-slate-800 transition-all text-sm"
                    >
                        {{ $t('features.cta_back_home', 'Ana Sayfaya Dön') }}
                    </Link>
                </div>

                <!-- Features Grid -->
                <div class="mt-16 sm:mt-20 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 text-left max-w-6xl mx-auto">
                    <div
                        v-for="feature in staticFeatures"
                        :key="feature.key"
                        class="p-7 rounded-3xl bg-slate-900/40 border border-slate-800/80 hover:border-slate-700 backdrop-blur-sm transition-all duration-200 flex flex-col justify-between group hover:-translate-y-1 hover:shadow-xl hover:shadow-indigo-500/5"
                        :class="feature.borderHover"
                    >
                        <div>
                            <div class="flex items-center justify-between mb-5">
                                <div
                                    class="w-13 h-13 rounded-2xl flex items-center justify-center p-3.5 border transition-transform group-hover:scale-110"
                                    :class="feature.bg"
                                >
                                    <Component
                                        :is="feature.icon"
                                        class="w-6 h-6"
                                    />
                                </div>
                                <span
                                    class="text-[11px] font-bold px-2.5 py-0.5 rounded-full border"
                                    :class="feature.badgeClass"
                                >
                                    {{ feature.badge }}
                                </span>
                            </div>

                            <h3 class="text-lg font-bold text-white mb-2.5 group-hover:text-indigo-300 transition-colors">
                                {{ feature.title }}
                            </h3>

                            <p class="text-sm text-slate-400 leading-relaxed">
                                {{ feature.description }}
                            </p>
                        </div>

                        <div class="pt-5 mt-5 border-t border-slate-800/60 flex items-center justify-between text-xs text-slate-500">
                            <span class="flex items-center space-x-1 font-medium text-emerald-400">
                                <CheckCircle2 class="w-3.5 h-3.5" />
                                <span>{{ $t('features.active_badge', 'Aktif Özellik') }}</span>
                            </span>
                            <span class="font-mono text-[10px] text-slate-600 uppercase tracking-wider">
                                {{ feature.color }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Bottom Conversion Card -->
                <div class="mt-20 max-w-4xl mx-auto p-8 sm:p-12 rounded-3xl bg-gradient-to-tr from-indigo-950/60 via-slate-900/90 to-purple-950/40 border border-indigo-500/30 text-center shadow-2xl relative overflow-hidden">
                    <div class="absolute -right-16 -top-16 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-purple-500/10 rounded-full blur-3xl pointer-events-none"></div>

                    <h2 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">
                        {{ $t('features.conversion_title', 'Web sitenizi bugün analiz etmeye başlayın') }}
                    </h2>
                    <p class="mt-3 text-sm sm:text-base text-slate-400 max-w-xl mx-auto">
                        {{ $t('features.conversion_subtitle', 'Tüm teknik hataları saniyeler içinde tespit edin, SEO skorunuzu yükseltin ve ekibinizle tek platformda çalışın.') }}
                    </p>
                    <div class="mt-6 flex flex-col sm:flex-row items-center justify-center gap-3">
                        <Link
                            href="/register"
                            class="w-full sm:w-auto px-7 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold shadow-lg shadow-indigo-600/30 flex items-center justify-center space-x-2 transition-all text-sm"
                        >
                            <span>{{ $t('features.conversion_register', 'Ücretsiz Hesap Oluştur') }}</span>
                            <ArrowRight class="w-4 h-4" />
                        </Link>
                        <Link
                            href="/login"
                            class="w-full sm:w-auto px-6 py-3 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-300 font-medium border border-slate-800 text-sm"
                        >
                            {{ $t('features.conversion_login', 'Mevcut Hesaba Giriş') }}
                        </Link>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-900 bg-slate-950/60 py-6 text-center text-xs text-slate-500">
            <p>Laravel v{{ laravelVersion || '12.x' }} &bull; PHP v{{ phpVersion || '8.2' }} &bull; {{ brandName }} Platform</p>
        </footer>
    </div>
</template>
