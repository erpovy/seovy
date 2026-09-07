<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import axios from 'axios';
import {
    Activity,
    CheckCircle2,
    XCircle,
    Key,
    Shield,
    Server,
    ArrowRight,
    ArrowLeft,
    Lock,
    User,
    Mail,
    Globe,
    AlertCircle,
    Database,
    RefreshCw,
    Copy,
    Check,
    ExternalLink,
    Sparkles,
    Terminal,
    Layers,
    Sliders
} from 'lucide-vue-next';
import LanguageSelector from '@/Components/LanguageSelector.vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps<{
    requirements: Record<string, {
        name: string;
        pass: boolean;
        current?: string;
        required?: string;
    }>;
    allPassed: boolean;
    serverInfo: {
        php_version: string;
        server_software: string;
        os: string;
    };
    detectedUrl: string;
    defaultDb: {
        connection: string;
        host: string;
        port: number;
        database: string;
        username: string;
        password: string;
    };
}>();

// Current Wizard Step: 1 = Requirements, 2 = License, 3 = Database, 4 = Admin & App, 5 = Complete
const currentStep = ref(1);

const steps = [
    { number: 1, title: 'Gereksinimler', icon: Server },
    { number: 2, title: 'Lisans Kodu', icon: Key },
    { number: 3, title: 'Veritabanı', icon: Database },
    { number: 4, title: 'Yönetici & Ayarlar', icon: Sliders },
    { number: 5, title: 'Tamamlandı', icon: CheckCircle2 },
];

// Main Installation Form
const form = useForm({
    purchase_code: '',
    db_connection: props.defaultDb?.connection || 'mysql',
    db_host: props.defaultDb?.host || '127.0.0.1',
    db_port: props.defaultDb?.port || 3306,
    db_database: props.defaultDb?.database || 'seovy',
    db_username: props.defaultDb?.username || 'root',
    db_password: props.defaultDb?.password || '',
    app_name: 'Seovy',
    app_url: props.detectedUrl || window.location.origin,
    install_mode: 'self_hosted',
    admin_name: 'Sistem Yöneticisi',
    admin_email: 'admin@seovy.local',
    admin_password: '',
    admin_password_confirmation: '',
});

// Database Test State
const testingDb = ref(false);
const dbTestResult = ref<{ success: boolean; message: string } | null>(null);

const testDatabaseConnection = async () => {
    testingDb.value = true;
    dbTestResult.value = null;
    try {
        const response = await axios.post('/install/test-db', {
            db_connection: form.db_connection,
            db_host: form.db_host,
            db_port: form.db_port,
            db_database: form.db_database,
            db_username: form.db_username,
            db_password: form.db_password,
        });
        dbTestResult.value = {
            success: true,
            message: response.data.message || 'Veritabanı bağlantısı başarılı!',
        };
    } catch (err: any) {
        dbTestResult.value = {
            success: false,
            message: err.response?.data?.message || err.message || 'Bağlantı kurulamadı.',
        };
    } finally {
        testingDb.value = false;
    }
};

// Copy Cron Job
const cronCopied = ref(false);
const cronCommand = computed(() => `* * * * * cd ${window.location.hostname} && php artisan schedule:run >> /dev/null 2>&1`);

const copyCron = () => {
    navigator.clipboard.writeText(cronCommand.value);
    cronCopied.value = true;
    setTimeout(() => {
        cronCopied.value = false;
    }, 2500);
};

// Navigation
const nextStep = () => {
    if (currentStep.value < 4) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

// Submit Installation
const isInstalling = ref(false);

const submitInstallation = () => {
    isInstalling.value = true;
    form.post('/install', {
        onError: () => {
            isInstalling.value = false;
        },
        onSuccess: () => {
            isInstalling.value = false;
        },
    });
};
</script>

<template>
    <Head title="Kurulum Sihirbazı - Seovy CodeCanyon Edition" />

    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-between py-8 px-4 sm:px-6 lg:px-8 selection:bg-indigo-500 selection:text-white">
        <!-- Top Header & Language Toggle -->
        <header class="max-w-4xl mx-auto w-full flex items-center justify-between pb-6 border-b border-slate-800/80">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center shadow-lg shadow-indigo-600/30">
                    <Activity class="w-5 h-5 text-white" />
                </div>
                <div>
                    <h1 class="text-lg font-bold text-white tracking-tight flex items-center space-x-2">
                        <span>Seovy</span>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 font-mono font-normal">v1.0.0</span>
                    </h1>
                    <p class="text-xs text-slate-400">CodeCanyon Otomatik Kurulum Sihirbazı</p>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                <LanguageSelector placement="bottom" />
            </div>
        </header>

        <!-- Main Stepper Card -->
        <main class="max-w-4xl mx-auto w-full my-8">
            <div class="bg-slate-900/60 backdrop-blur-xl border border-slate-800/80 rounded-3xl shadow-2xl overflow-hidden">
                <!-- Stepper Progress Bar -->
                <div class="border-b border-slate-800/80 bg-slate-950/40 p-4 sm:p-6">
                    <div class="flex items-center justify-between max-w-2xl mx-auto">
                        <template v-for="(step, index) in steps" :key="step.number">
                            <div class="flex items-center space-x-2">
                                <div
                                    class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl flex items-center justify-center text-xs font-bold transition-all"
                                    :class="[
                                        currentStep === step.number
                                            ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 ring-2 ring-indigo-400/50'
                                            : currentStep > step.number
                                                ? 'bg-emerald-600/20 text-emerald-400 border border-emerald-500/30'
                                                : 'bg-slate-800 text-slate-500 border border-slate-700/50'
                                    ]"
                                >
                                    <CheckCircle2 v-if="currentStep > step.number" class="w-4 h-4" />
                                    <component :is="step.icon" v-else class="w-4 h-4" />
                                </div>
                                <span
                                    class="hidden sm:inline-block text-xs font-medium"
                                    :class="currentStep >= step.number ? 'text-white' : 'text-slate-500'"
                                >
                                    {{ step.title }}
                                </span>
                            </div>

                            <div
                                v-if="index < steps.length - 1"
                                class="flex-1 h-0.5 mx-2 sm:mx-4 transition-colors"
                                :class="currentStep > step.number ? 'bg-emerald-500/50' : 'bg-slate-800'"
                            ></div>
                        </template>
                    </div>
                </div>

                <!-- Form Content Area -->
                <div class="p-6 sm:p-10">
                    <!-- Global Error Alert -->
                    <div v-if="Object.keys(form.errors).length > 0" class="mb-6 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs space-y-1.5">
                        <div class="font-bold flex items-center space-x-1.5 mb-1">
                            <AlertCircle class="w-4 h-4 shrink-0" />
                            <span>Lütfen formdaki eksik veya hatalı alanları düzeltin:</span>
                        </div>
                        <ul class="list-disc list-inside space-y-0.5 ml-2">
                            <li v-for="(err, field) in form.errors" :key="field">
                                {{ err }}
                            </li>
                        </ul>
                    </div>

                    <!-- ======================================================================= -->
                    <!-- STEP 1: REQUIREMENTS & PERMISSIONS -->
                    <!-- ======================================================================= -->
                    <div v-if="currentStep === 1" class="space-y-6">
                        <div>
                            <h2 class="text-lg font-bold text-white">Sunucu Gereksinimleri & Dizin İzinleri</h2>
                            <p class="text-xs text-slate-400 mt-1">
                                Platformun eksiksiz çalışabilmesi için PHP uzantılarının ve dizin yazma izinlerinin doğrulanması gerekir.
                            </p>
                        </div>

                        <!-- System Status Summary -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-4 rounded-2xl bg-slate-950/60 border border-slate-800 text-xs">
                            <div>
                                <span class="text-slate-500 block">PHP Sürümü:</span>
                                <span class="font-mono font-bold text-white">{{ serverInfo.php_version }}</span>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Web Sunucusu:</span>
                                <span class="font-medium text-white truncate block">{{ serverInfo.server_software }}</span>
                            </div>
                            <div>
                                <span class="text-slate-500 block">İşletim Sistemi:</span>
                                <span class="font-medium text-white">{{ serverInfo.os }}</span>
                            </div>
                        </div>

                        <!-- Requirements Checklist Table -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
                            <div
                                v-for="(req, key) in requirements"
                                :key="key"
                                class="p-3.5 rounded-xl border flex items-center justify-between transition-all"
                                :class="req.pass ? 'bg-slate-950/40 border-emerald-500/20 text-slate-300' : 'bg-rose-500/10 border-rose-500/30 text-rose-300'"
                            >
                                <div class="pr-2">
                                    <span class="font-medium block">{{ req.name }}</span>
                                    <span v-if="req.current" class="text-[10px] text-slate-500 font-mono">
                                        Mevcut: {{ req.current }} (Gereken: {{ req.required }})
                                    </span>
                                </div>
                                <div class="shrink-0">
                                    <span v-if="req.pass" class="inline-flex items-center space-x-1 text-emerald-400 font-semibold text-[11px]">
                                        <CheckCircle2 class="w-4 h-4" />
                                        <span class="hidden sm:inline">Uyumlu</span>
                                    </span>
                                    <span v-else class="inline-flex items-center space-x-1 text-rose-400 font-semibold text-[11px]">
                                        <XCircle class="w-4 h-4" />
                                        <span>Eksik</span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div v-if="!allPassed" class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-300 text-xs flex items-start space-x-3">
                            <AlertCircle class="w-5 h-5 shrink-0 mt-0.5" />
                            <div>
                                <span class="font-bold block">Bazı sistem gereksinimleri karşılanamadı!</span>
                                <p class="mt-0.5 text-slate-400">
                                    Kırmızı ile işaretli dizinler için cPanel / FTP üzerinden yazma izni (chmod 775 veya 777) vermeniz gerekmektedir. Eksik PHP uzantılarını hosting panelinizin PHP Extensions bölümünden aktif edebilirsiniz.
                                </p>
                            </div>
                        </div>

                        <!-- Step 1 Actions -->
                        <div class="pt-4 flex justify-end">
                            <button
                                @click="nextStep"
                                :disabled="!allPassed"
                                class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs shadow-lg shadow-indigo-600/30 flex items-center space-x-2 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span>Lisans Adımına Geç</span>
                                <ArrowRight class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- ======================================================================= -->
                    <!-- STEP 2: CODECANYON LICENSE / PURCHASE CODE -->
                    <!-- ======================================================================= -->
                    <div v-else-if="currentStep === 2" class="space-y-6">
                        <div>
                            <h2 class="text-lg font-bold text-white">Envato / CodeCanyon Lisans Doğrulama</h2>
                            <p class="text-xs text-slate-400 mt-1">
                                Satın aldığınız Seovy lisans kodunu girin. Yerel test veya geliştirici ortamında iseniz bu adımı atlayabilirsiniz.
                            </p>
                        </div>

                        <div class="p-5 rounded-2xl bg-indigo-500/5 border border-indigo-500/20 space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1.5 flex items-center justify-between">
                                    <span>Envato Satın Alma Kodu (Purchase Code)</span>
                                    <span class="text-[11px] text-slate-500 font-normal">İsteğe Bağlı / Standart</span>
                                </label>
                                <div class="relative">
                                    <Key class="w-4 h-4 absolute left-3.5 top-3 text-slate-500" />
                                    <input
                                        v-model="form.purchase_code"
                                        type="text"
                                        class="w-full pl-10 pr-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white font-mono placeholder:text-slate-600 focus:ring-2 focus:ring-indigo-500/40"
                                        placeholder="Örn: 8a4e3210-9b6c-4f12-8e9a-7c8d9e0f1a2b"
                                    />
                                </div>
                                <p class="text-[11px] text-slate-400 mt-2 flex items-center space-x-1.5">
                                    <span>Satın alma kodunuza Envato hesabınızın</span>
                                    <code class="text-indigo-400 font-mono bg-slate-950 px-1 py-0.5 rounded">Downloads &rarr; License certificate</code>
                                    <span>bölümünden ulaşabilirsiniz.</span>
                                </p>
                            </div>

                            <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
                                <span class="flex items-center space-x-1.5">
                                    <CheckCircle2 class="w-4 h-4 text-emerald-400" />
                                    <span>Düzenli güncellemeler ve teknik destek için lisansınızı saklayın.</span>
                                </span>
                            </div>
                        </div>

                        <!-- Step 2 Actions -->
                        <div class="pt-4 flex items-center justify-between">
                            <button
                                @click="prevStep"
                                class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-medium text-xs flex items-center space-x-2 transition-all"
                            >
                                <ArrowLeft class="w-4 h-4" />
                                <span>Geri</span>
                            </button>
                            <button
                                @click="nextStep"
                                class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs shadow-lg shadow-indigo-600/30 flex items-center space-x-2 transition-all"
                            >
                                <span>Veritabanı Yapılandırmasına Geç</span>
                                <ArrowRight class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- ======================================================================= -->
                    <!-- STEP 3: DATABASE CONFIGURATION & LIVE TEST -->
                    <!-- ======================================================================= -->
                    <div v-else-if="currentStep === 3" class="space-y-6">
                        <div>
                            <h2 class="text-lg font-bold text-white">Veritabanı Yapılandırması</h2>
                            <p class="text-xs text-slate-400 mt-1">
                                cPanel veya veritabanı sunucunuzda oluşturduğunuz veritabanı bilgilerini girin. Kuruluma geçmeden önce bağlantıyı canlı olarak test edebilirsiniz.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Database Driver -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Veritabanı Sürücüsü</label>
                                <select
                                    v-model="form.db_connection"
                                    class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:ring-2 focus:ring-indigo-500/40"
                                >
                                    <option value="mysql">MySQL / MariaDB (Önerilen - cPanel Uyumlu)</option>
                                    <option value="pgsql">PostgreSQL (Kurumsal Sunucular)</option>
                                    <option value="sqlite">SQLite (Tek Dosya / Geliştirme)</option>
                                </select>
                            </div>

                            <!-- Database Host -->
                            <div v-if="form.db_connection !== 'sqlite'">
                                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Sunucu Adresi (Host)</label>
                                <input
                                    v-model="form.db_host"
                                    type="text"
                                    required
                                    class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white font-mono"
                                    placeholder="127.0.0.1 veya localhost"
                                />
                            </div>

                            <!-- Port & Database Name -->
                            <div v-if="form.db_connection !== 'sqlite'">
                                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Port</label>
                                <input
                                    v-model="form.db_port"
                                    type="number"
                                    class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white font-mono"
                                    :placeholder="form.db_connection === 'pgsql' ? '5432' : '3306'"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                    {{ form.db_connection === 'sqlite' ? 'Veritabanı Dosya Yolu' : 'Veritabanı Adı (Database Name)' }}
                                </label>
                                <input
                                    v-model="form.db_database"
                                    type="text"
                                    required
                                    class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white font-mono"
                                    :placeholder="form.db_connection === 'sqlite' ? 'database/database.sqlite' : 'cpaneluser_seovy'"
                                />
                            </div>

                            <!-- Username & Password -->
                            <div v-if="form.db_connection !== 'sqlite'">
                                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Kullanıcı Adı (Username)</label>
                                <input
                                    v-model="form.db_username"
                                    type="text"
                                    class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white font-mono"
                                    placeholder="cpaneluser_dbuser"
                                />
                            </div>

                            <div v-if="form.db_connection !== 'sqlite'">
                                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Veritabanı Parolası</label>
                                <input
                                    v-model="form.db_password"
                                    type="password"
                                    class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white font-mono"
                                    placeholder="Veritabanı şifreniz"
                                />
                            </div>
                        </div>

                        <!-- Live Test Connection Button & Status Box -->
                        <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <span class="text-xs font-semibold text-white block">Canlı Bağlantı Testi</span>
                                <span class="text-[11px] text-slate-400">Girdiğiniz veritabanı bilgilerinin doğruluğunu test edin.</span>
                            </div>

                            <button
                                type="button"
                                @click="testDatabaseConnection"
                                :disabled="testingDb || !form.db_database"
                                class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-indigo-300 border border-indigo-500/30 text-xs font-semibold flex items-center justify-center space-x-2 transition-all disabled:opacity-50"
                            >
                                <RefreshCw v-if="testingDb" class="w-3.5 h-3.5 animate-spin text-indigo-400" />
                                <Database v-else class="w-3.5 h-3.5 text-indigo-400" />
                                <span>{{ testingDb ? 'Test Ediliyor...' : 'Bağlantıyı Test Et' }}</span>
                            </button>
                        </div>

                        <!-- Test Result Alert -->
                        <div
                            v-if="dbTestResult"
                            class="p-4 rounded-2xl border text-xs flex items-center space-x-2.5 transition-all"
                            :class="dbTestResult.success ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-300' : 'bg-rose-500/10 border-rose-500/20 text-rose-300'"
                        >
                            <CheckCircle2 v-if="dbTestResult.success" class="w-4 h-4 shrink-0 text-emerald-400" />
                            <XCircle v-else class="w-4 h-4 shrink-0 text-rose-400" />
                            <span>{{ dbTestResult.message }}</span>
                        </div>

                        <!-- Step 3 Actions -->
                        <div class="pt-4 flex items-center justify-between">
                            <button
                                @click="prevStep"
                                class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-medium text-xs flex items-center space-x-2 transition-all"
                            >
                                <ArrowLeft class="w-4 h-4" />
                                <span>Geri</span>
                            </button>
                            <button
                                @click="nextStep"
                                :disabled="!form.db_database"
                                class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs shadow-lg shadow-indigo-600/30 flex items-center space-x-2 transition-all disabled:opacity-50"
                            >
                                <span>Yönetici & Uygulama Ayarlarına Geç</span>
                                <ArrowRight class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- ======================================================================= -->
                    <!-- STEP 4: APPLICATION & MASTER ADMINISTRATOR -->
                    <!-- ======================================================================= -->
                    <div v-else-if="currentStep === 4" class="space-y-6">
                        <div>
                            <h2 class="text-lg font-bold text-white">Uygulama & Süper Yönetici Tanımı</h2>
                            <p class="text-xs text-slate-400 mt-1">
                                Platformun adını, çalışma modunu ve ana süper yönetici (Platform Admin) hesabınızı belirleyin.
                            </p>
                        </div>

                        <!-- App Settings -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Platform / Marka Adı</label>
                                <input
                                    v-model="form.app_name"
                                    type="text"
                                    required
                                    class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white"
                                    placeholder="Seovy"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Uygulama URL'i (APP_URL)</label>
                                <input
                                    v-model="form.app_url"
                                    type="url"
                                    required
                                    class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white font-mono"
                                    placeholder="https://alanadiniz.com"
                                />
                            </div>
                        </div>

                        <!-- Deployment Mode -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Dağıtım Modu (Deployment Mode)</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label
                                    class="p-4 rounded-2xl border cursor-pointer flex items-start space-x-3 transition-all"
                                    :class="form.install_mode === 'self_hosted' ? 'bg-indigo-600/10 border-indigo-500/50 ring-1 ring-indigo-500/30' : 'bg-slate-950/40 border-slate-800 hover:border-slate-700'"
                                >
                                    <input
                                        type="radio"
                                        v-model="form.install_mode"
                                        value="self_hosted"
                                        class="mt-1 text-indigo-600 focus:ring-indigo-500 bg-slate-900 border-slate-700"
                                    />
                                    <div>
                                        <span class="text-xs font-bold text-white block">Self-Hosted / Ajans Modu</span>
                                        <span class="text-[11px] text-slate-400 mt-0.5 block">
                                            Kendi siteleriniz veya ajansınız için sınırsız proje, sayfa ve tarama kotaları.
                                        </span>
                                    </div>
                                </label>

                                <label
                                    class="p-4 rounded-2xl border cursor-pointer flex items-start space-x-3 transition-all"
                                    :class="form.install_mode === 'saas' ? 'bg-indigo-600/10 border-indigo-500/50 ring-1 ring-indigo-500/30' : 'bg-slate-950/40 border-slate-800 hover:border-slate-700'"
                                >
                                    <input
                                        type="radio"
                                        v-model="form.install_mode"
                                        value="saas"
                                        class="mt-1 text-indigo-600 focus:ring-indigo-500 bg-slate-900 border-slate-700"
                                    />
                                    <div>
                                        <span class="text-xs font-bold text-white block">Çok Kullanıcılı SaaS Modu</span>
                                        <span class="text-[11px] text-slate-400 mt-0.5 block">
                                            Üyelik, paket kotaları ve Stripe/PayTR üzerinden abonelik satışı yapılabilen SaaS altyapısı.
                                        </span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Admin Account Form -->
                        <div class="pt-4 border-t border-slate-800/80 space-y-4">
                            <h3 class="text-xs font-bold text-white uppercase tracking-wider flex items-center space-x-2">
                                <Shield class="w-4 h-4 text-emerald-400" />
                                <span>Platform Süper Yöneticisi</span>
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs text-slate-400 mb-1">Yönetici Adı Soyadı</label>
                                    <input
                                        v-model="form.admin_name"
                                        type="text"
                                        required
                                        class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white"
                                        placeholder="Admin Kullanıcı"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs text-slate-400 mb-1">Yönetici E-Posta Adresi</label>
                                    <input
                                        v-model="form.admin_email"
                                        type="email"
                                        required
                                        class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white"
                                        placeholder="admin@alanadiniz.com"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs text-slate-400 mb-1">Parola (En az 8 karakter)</label>
                                    <input
                                        v-model="form.admin_password"
                                        type="password"
                                        required
                                        class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white"
                                        placeholder="••••••••"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs text-slate-400 mb-1">Parola Tekrarı</label>
                                    <input
                                        v-model="form.admin_password_confirmation"
                                        type="password"
                                        required
                                        class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white"
                                        placeholder="••••••••"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Step 4 Actions / Final Submit -->
                        <div class="pt-4 flex items-center justify-between">
                            <button
                                @click="prevStep"
                                :disabled="isInstalling"
                                class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-medium text-xs flex items-center space-x-2 transition-all disabled:opacity-50"
                            >
                                <ArrowLeft class="w-4 h-4" />
                                <span>Geri</span>
                            </button>

                            <button
                                type="button"
                                @click="submitInstallation"
                                :disabled="isInstalling || form.processing"
                                class="px-8 py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-xs shadow-xl shadow-indigo-600/30 flex items-center space-x-2.5 transition-all disabled:opacity-50"
                            >
                                <RefreshCw v-if="isInstalling || form.processing" class="w-4 h-4 animate-spin text-white" />
                                <Sparkles v-else class="w-4 h-4 text-amber-300" />
                                <span>{{ (isInstalling || form.processing) ? 'Kuruluyor & Tablolar Hazırlanıyor...' : 'Kurulumu Tamamla ve Kilitle' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="max-w-4xl mx-auto w-full text-center text-xs text-slate-500 pt-4 border-t border-slate-900">
            <p>Seovy Platform &bull; CodeCanyon Certified Installer &bull; Laravel v12.x Architecture</p>
        </footer>
    </div>
</template>
