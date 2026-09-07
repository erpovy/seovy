<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import {
    CreditCard,
    Globe,
    Activity,
    CheckCircle2,
    XCircle,
    Clock,
    Settings,
    ShieldCheck,
    Sparkles,
    Play,
    RotateCcw,
    AlertTriangle,
    Sliders,
    DollarSign,
    Check,
    Zap,
    Smartphone,
    QrCode,
    ExternalLink,
    Lock,
    Shield,
    X
} from 'lucide-vue-next';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps<{
    gateways: Array<any>;
    paymentSettings: {
        test_mode: boolean;
        default_gateway: string;
    };
    recentTransactions: Array<any>;
    workspacesList: Array<any>;
    stats: {
        total_payment_volume: number;
        total_payment_transactions: number;
        simulated_transactions: number;
        active_gateways_count: number;
    };
}>();

// Flags mapping for visual appeal
const countryFlags: Record<string, string> = {
    'TR': '🇹🇷',
    'US': '🇺🇸',
    'DE': '🇩🇪',
    'FR': '🇫🇷',
    'ES': '🇪🇸',
    'IT': '🇮🇹',
    'PT': '🇧🇷/🇵🇹',
    'RU': '🇷🇺',
    'ZH': '🇨🇳',
    'AR': '🇸🇦/🇦🇪',
};

// Filter for Gateways
const selectedRegion = ref<string>('all');
const filteredGateways = computed(() => {
    if (selectedRegion.value === 'all') return props.gateways;
    if (selectedRegion.value === 'europe') {
        return props.gateways.filter(g => ['DE', 'FR', 'ES', 'IT'].includes(g.country_code));
    }
    if (selectedRegion.value === 'americas_asia') {
        return props.gateways.filter(g => ['US', 'PT', 'RU', 'ZH'].includes(g.country_code));
    }
    if (selectedRegion.value === 'mena_turkey') {
        return props.gateways.filter(g => ['TR', 'AR'].includes(g.country_code));
    }
    return props.gateways;
});

// Gateway Edit Modal State
const editingGateway = ref<any>(null);
const editForm = useForm({
    is_active: false,
    mode: 'test',
    currency: 'USD',
    credentials: {} as Record<string, any>,
    settings: {} as Record<string, any>,
});

const openEditGateway = (gw: any) => {
    editingGateway.value = gw;
    editForm.is_active = !!gw.is_active;
    editForm.mode = gw.mode || 'test';
    editForm.currency = gw.currency || 'USD';
    editForm.credentials = { ...(gw.credentials || {}) };
    editForm.settings = { ...(gw.settings || {}) };
};

const closeEditGateway = () => {
    editingGateway.value = null;
};

const saveGatewaySettings = () => {
    if (!editingGateway.value) return;
    editForm.patch(`/admin/payments/gateways/${editingGateway.value.id}`, {
        onSuccess: () => {
            closeEditGateway();
        }
    });
};

const toggleGatewayActive = (gw: any) => {
    router.patch(`/admin/payments/gateways/${gw.id}`, {
        is_active: !gw.is_active,
        mode: gw.mode,
        currency: gw.currency,
        credentials: gw.credentials,
        settings: gw.settings,
    }, { preserveScroll: true });
};

// Global Test Mode Toggle
const isTogglingTestMode = ref(false);
const toggleTestMode = () => {
    isTogglingTestMode.value = true;
    router.post('/admin/payments/settings', {
        test_mode: !props.paymentSettings.test_mode,
        default_gateway: props.paymentSettings.default_gateway,
    }, {
        preserveScroll: true,
        onFinish: () => {
            isTogglingTestMode.value = false;
        }
    });
};

// Purchase Simulator Form
const simForm = useForm({
    workspace_id: props.workspacesList && props.workspacesList.length > 0 ? props.workspacesList[0].id : null,
    gateway_code: 'iyzico',
    plan_name: 'pro',
    amount: 499.00,
    scenario: 'success',
    customer_name: '',
    customer_email: '',
    card_brand: 'Visa',
});

const onPlanChange = () => {
    if (simForm.plan_name === 'pro') simForm.amount = 499.00;
    else if (simForm.plan_name === 'agency') simForm.amount = 1499.00;
    else if (simForm.plan_name === 'free') simForm.amount = 0.00;
};

const runSimulation = () => {
    simForm.post('/admin/payments/simulate', {
        preserveScroll: true,
    });
};

// Refund Simulation
const refundingId = ref<number | null>(null);
const runRefund = (txn: any) => {
    if (!confirm(t('admin.pos_refund_confirm'))) return;
    refundingId.value = txn.id;
    router.post(`/admin/payments/transactions/${txn.id}/refund`, {}, {
        preserveScroll: true,
        onFinish: () => {
            refundingId.value = null;
        }
    });
};

// Transaction details modal
const selectedTxn = ref<any>(null);
</script>

<template>
    <div class="space-y-8">
        <!-- 1. Top Statistics Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-5 rounded-3xl bg-slate-900/50 border border-slate-800/80 space-y-1.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ $t('admin.pos_total_volume') }}</span>
                    <DollarSign class="w-4 h-4 text-emerald-400" />
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-white">
                    {{ (stats.total_payment_volume || 0).toLocaleString() }} ₺
                </div>
                <div class="text-[11px] text-emerald-400/80 flex items-center space-x-1">
                    <CheckCircle2 class="w-3 h-3" />
                    <span>{{ $t('admin.pos_status_success') }}</span>
                </div>
            </div>

            <div class="p-5 rounded-3xl bg-slate-900/50 border border-slate-800/80 space-y-1.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ $t('admin.pos_total_transactions') }}</span>
                    <Activity class="w-4 h-4 text-indigo-400" />
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-white">{{ stats.total_payment_transactions || 0 }}</div>
                <div class="text-[11px] text-slate-400 font-mono">
                    {{ (stats.total_payment_transactions || 0) - (stats.simulated_transactions || 0) }} {{ $t('admin.pos_mode_live').toLowerCase() }}
                </div>
            </div>

            <div class="p-5 rounded-3xl bg-slate-900/50 border border-slate-800/80 space-y-1.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ $t('admin.pos_simulated_count') }}</span>
                    <Sparkles class="w-4 h-4 text-amber-400" />
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-amber-300">{{ stats.simulated_transactions || 0 }}</div>
                <div class="text-[11px] text-amber-400/80 flex items-center space-x-1">
                    <span>{{ $t('admin.pos_mode_simulation') }}</span>
                </div>
            </div>

            <div class="p-5 rounded-3xl bg-slate-900/50 border border-slate-800/80 space-y-1.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ $t('admin.pos_active_gateways') }}</span>
                    <CreditCard class="w-4 h-4 text-pink-400" />
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-white">
                    {{ stats.active_gateways_count || 0 }} / {{ gateways.length }}
                </div>
                <div class="text-[11px] text-slate-400">
                    10 {{ $t('admin.pos_country') }}
                </div>
            </div>
        </div>

        <!-- 2. Virtual POS Gateways Grid (10 Providers for each language/country) -->
        <div class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-white flex items-center space-x-2">
                        <CreditCard class="w-5 h-5 text-indigo-400" />
                        <span>{{ $t('admin.pos_gateways_title') }}</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        {{ $t('admin.pos_gateways_subtitle') }}
                    </p>
                </div>

                <!-- Region Filter Buttons -->
                <div class="flex items-center flex-wrap gap-1.5 text-xs bg-slate-900/40 p-1 rounded-xl border border-slate-800">
                    <button
                        @click="selectedRegion = 'all'"
                        class="px-3 py-1.5 rounded-lg transition-colors"
                        :class="selectedRegion === 'all' ? 'bg-indigo-600 text-white font-semibold' : 'text-slate-400 hover:text-white'"
                    >
                        {{ $t('common.all') || 'Tümü' }} ({{ gateways.length }})
                    </button>
                    <button
                        @click="selectedRegion = 'mena_turkey'"
                        class="px-3 py-1.5 rounded-lg transition-colors"
                        :class="selectedRegion === 'mena_turkey' ? 'bg-indigo-600 text-white font-semibold' : 'text-slate-400 hover:text-white'"
                    >
                        🇹🇷 TR & 🇸🇦 MENA
                    </button>
                    <button
                        @click="selectedRegion = 'europe'"
                        class="px-3 py-1.5 rounded-lg transition-colors"
                        :class="selectedRegion === 'europe' ? 'bg-indigo-600 text-white font-semibold' : 'text-slate-400 hover:text-white'"
                    >
                        🇪🇺 DE, FR, ES, IT
                    </button>
                    <button
                        @click="selectedRegion = 'americas_asia'"
                        class="px-3 py-1.5 rounded-lg transition-colors"
                        :class="selectedRegion === 'americas_asia' ? 'bg-indigo-600 text-white font-semibold' : 'text-slate-400 hover:text-white'"
                    >
                        🌎 US, BR, RU, CN
                    </button>
                </div>
            </div>

            <!-- Gateways Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="gw in filteredGateways"
                    :key="gw.id"
                    class="p-5 rounded-3xl bg-slate-900/60 border transition-all flex flex-col justify-between group"
                    :class="gw.is_active ? 'border-indigo-500/40 shadow-lg shadow-indigo-500/5' : 'border-slate-800/80 opacity-80'"
                >
                    <div>
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center space-x-3">
                                <div class="w-11 h-11 rounded-2xl bg-slate-950 border border-slate-800 flex items-center justify-center text-xl shadow-inner group-hover:scale-105 transition-transform">
                                    {{ countryFlags[gw.country_code] || '🌐' }}
                                </div>
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <h3 class="font-bold text-sm text-white">{{ gw.name }}</h3>
                                        <span class="text-[10px] px-2 py-0.5 rounded-full font-mono font-bold bg-slate-800 text-slate-300 border border-slate-700">
                                            {{ gw.currency }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-slate-400 mt-0.5">
                                        {{ gw.country_name }} ({{ gw.language_code.toUpperCase() }})
                                    </p>
                                </div>
                            </div>

                            <!-- Toggle Status Switch -->
                            <button
                                type="button"
                                @click="toggleGatewayActive(gw)"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                :class="gw.is_active ? 'bg-emerald-500' : 'bg-slate-700'"
                                :title="gw.is_active ? $t('admin.pos_active') : $t('admin.pos_inactive')"
                            >
                                <span
                                    class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"
                                    :class="gw.is_active ? 'translate-x-5' : 'translate-x-0'"
                                />
                            </button>
                        </div>

                        <p class="text-xs text-slate-400 leading-relaxed mb-4 min-h-[36px]">
                            {{ gw.description }}
                        </p>
                    </div>

                    <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs">
                        <div class="flex items-center space-x-2">
                            <span
                                class="px-2 py-0.5 rounded-md text-[10px] font-semibold uppercase tracking-wider"
                                :class="gw.mode === 'test' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'"
                            >
                                {{ gw.mode === 'test' ? 'Test Sandbox' : 'Live Production' }}
                            </span>
                            <span v-if="gw.is_active" class="text-[11px] text-emerald-400 font-medium">● {{ $t('admin.pos_active') }}</span>
                            <span v-else class="text-[11px] text-slate-500 font-medium">○ {{ $t('admin.pos_inactive') }}</span>
                        </div>

                        <button
                            type="button"
                            @click="openEditGateway(gw)"
                            class="px-3 py-1.5 rounded-xl bg-slate-950 hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-white text-xs font-semibold flex items-center space-x-1.5 transition-colors"
                        >
                            <Settings class="w-3.5 h-3.5" />
                            <span>{{ $t('admin.pos_edit_gateway') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. EN ALTTA: TEST MODU VE SİMÜLASYON PANELİ -->
        <div class="space-y-6 pt-4 border-t-2 border-slate-800/80">
            <!-- Test Mode Global Switch Banner -->
            <div
                class="p-6 rounded-3xl border shadow-2xl transition-all relative overflow-hidden"
                :class="paymentSettings.test_mode ? 'bg-amber-950/20 border-amber-500/40 shadow-amber-500/5' : 'bg-slate-900/60 border-slate-800/80'"
            >
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
                    <div class="space-y-2 max-w-3xl">
                        <div class="flex items-center space-x-3">
                            <span
                                class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider"
                                :class="paymentSettings.test_mode ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40 animate-pulse' : 'bg-slate-800 text-slate-400 border border-slate-700'"
                            >
                                <span class="w-2 h-2 rounded-full" :class="paymentSettings.test_mode ? 'bg-amber-400' : 'bg-slate-500'"></span>
                                <span>{{ paymentSettings.test_mode ? $t('admin.pos_test_mode_active') : $t('admin.pos_test_mode_inactive') }}</span>
                            </span>
                            <span class="text-xs text-slate-400 font-mono">
                                (Sandbox Simulation Engine)
                            </span>
                        </div>

                        <h3 class="text-xl font-bold text-white">
                            {{ $t('admin.pos_test_mode_title') }}
                        </h3>

                        <p class="text-xs text-slate-300 leading-relaxed">
                            {{ $t('admin.pos_test_mode_desc') }}
                        </p>
                    </div>

                    <div class="flex items-center space-x-3 shrink-0">
                        <button
                            type="button"
                            @click="toggleTestMode"
                            :disabled="isTogglingTestMode"
                            class="px-6 py-3 rounded-2xl text-xs font-bold shadow-xl flex items-center space-x-2 transition-all disabled:opacity-50"
                            :class="paymentSettings.test_mode ? 'bg-amber-500 hover:bg-amber-400 text-slate-950 shadow-amber-500/20' : 'bg-slate-800 hover:bg-slate-700 text-white border border-slate-700'"
                        >
                            <Sliders class="w-4 h-4" />
                            <span>{{ $t('admin.pos_toggle_test_mode') }}: {{ paymentSettings.test_mode ? $t('admin.pos_status_off', 'KAPAT') : $t('admin.pos_status_on', 'AÇ') }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Satın Alma Simülatörü Formu -->
            <div class="p-6 sm:p-8 rounded-3xl bg-slate-900/70 border border-slate-800/80 shadow-2xl space-y-6">
                <div>
                    <div class="flex items-center space-x-2 text-indigo-400 font-bold text-xs uppercase tracking-wider mb-1">
                        <Sparkles class="w-4 h-4 text-indigo-400" />
                        <span>{{ $t('admin.pos_simulator_title') }}</span>
                    </div>
                    <h3 class="text-lg font-bold text-white">{{ $t('admin.pos_simulator_title') }}</h3>
                    <p class="text-xs text-slate-400 mt-0.5">
                        {{ $t('admin.pos_simulator_desc') }}
                    </p>
                </div>

                <form @submit.prevent="runSimulation" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- 1. Workspace / Customer -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-2">
                                {{ $t('admin.pos_select_workspace') }}
                            </label>
                            <select
                                v-model="simForm.workspace_id"
                                required
                                class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500"
                            >
                                <option v-for="ws in workspacesList" :key="ws.id" :value="ws.id">
                                    {{ ws.name }} ({{ ws.owner?.email || $t('admin.customer', 'Müşteri') }})
                                </option>
                            </select>
                        </div>

                        <!-- 2. Gateway Provider -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-2">
                                {{ $t('admin.pos_select_gateway') }}
                            </label>
                            <select
                                v-model="simForm.gateway_code"
                                required
                                class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500"
                            >
                                <option v-for="gw in gateways" :key="gw.code" :value="gw.code">
                                    {{ countryFlags[gw.country_code] || '' }} {{ gw.name }} ({{ gw.currency }})
                                </option>
                            </select>
                        </div>

                        <!-- 3. Plan & Amount -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-2">
                                {{ $t('admin.pos_select_plan') }}
                            </label>
                            <select
                                v-model="simForm.plan_name"
                                @change="onPlanChange"
                                required
                                class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500"
                            >
                                <option value="pro">{{ $t('billing.plan_pro_title', 'Pro Plan') }} (499 ₺ / $29)</option>
                                <option value="agency">{{ $t('admin.pos_plan_agency', 'Ajans Planı (1.499 ₺ / $89)') }}</option>
                                <option value="free">{{ $t('admin.pos_plan_free', 'Ücretsiz Plan (0 ₺)') }}</option>
                                <option value="custom">{{ $t('admin.pos_plan_custom', 'Özel Tutar') }}</option>
                            </select>
                        </div>

                        <!-- 4. Simulation Scenario -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-2">
                                {{ $t('admin.pos_select_scenario') }}
                            </label>
                            <select
                                v-model="simForm.scenario"
                                required
                                class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500"
                            >
                                <option value="success">{{ $t('admin.pos_scenario_success') }}</option>
                                <option value="3ds_success">{{ $t('admin.pos_scenario_3ds') }}</option>
                                <option value="insufficient_funds">{{ $t('admin.pos_scenario_insufficient') }}</option>
                                <option value="bank_declined">{{ $t('admin.pos_scenario_declined') }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Custom Amount row if custom chosen -->
                    <div v-if="simForm.plan_name === 'custom'" class="pt-2 max-w-xs">
                        <label class="block text-xs text-slate-400 mb-1">{{ $t('admin.pos_custom_amount') }}</label>
                        <input
                            v-model.number="simForm.amount"
                            type="number"
                            min="1"
                            step="0.01"
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white"
                        />
                    </div>

                    <div class="pt-3 flex items-center justify-end">
                        <button
                            type="submit"
                            :disabled="simForm.processing"
                            class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-lg shadow-indigo-600/30 flex items-center space-x-2 transition-all disabled:opacity-50"
                        >
                            <Play class="w-4 h-4 fill-white" />
                            <span>{{ simForm.processing ? $t('admin.pos_simulating') : $t('admin.pos_simulate_btn') }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- İşlem ve Simülasyon Kayıtları (Ledger) Tablosu -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-white flex items-center space-x-2">
                            <Clock class="w-4 h-4 text-slate-400" />
                            <span>{{ $t('admin.pos_ledger_title') }}</span>
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">
                            {{ $t('admin.pos_ledger_subtitle', 'Gerçekleşen tüm canlı ve simüle edilmiş satın alma işlemleri.') }}
                        </p>
                    </div>
                </div>

                <div v-if="recentTransactions.length === 0" class="p-12 rounded-3xl bg-slate-900/40 border border-slate-800 text-center">
                    <CreditCard class="w-12 h-12 text-slate-600 mx-auto mb-3" />
                    <h4 class="text-sm font-semibold text-white">{{ $t('admin.pos_no_transactions', 'Henüz işlem kaydı bulunmuyor') }}</h4>
                    <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1 mb-4">
                        {{ $t('admin.pos_no_transactions_hint', 'Yukarıdaki Satın Alma Simülatörünü kullanarak ilk test işleminizi hemen gerçekleştirebilirsiniz.') }}
                    </p>
                </div>

                <div v-else class="overflow-hidden rounded-2xl border border-slate-800/80 bg-slate-900/60 shadow-xl">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-300">
                            <thead class="bg-slate-950/80 text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-800 font-semibold">
                                <tr>
                                    <th class="py-3 px-4">{{ $t('admin.pos_txn_id') }}</th>
                                    <th class="py-3 px-4">{{ $t('admin.pos_customer') }}</th>
                                    <th class="py-3 px-4">{{ $t('admin.pos_gateway') }}</th>
                                    <th class="py-3 px-4">{{ $t('admin.pos_amount') }}</th>
                                    <th class="py-3 px-4">{{ $t('admin.pos_mode') }}</th>
                                    <th class="py-3 px-4">{{ $t('admin.pos_status') }}</th>
                                    <th class="py-3 px-4">{{ $t('admin.pos_date') }}</th>
                                    <th class="py-3 px-4 text-right">{{ $t('admin.pos_actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60 font-mono text-xs">
                                <tr
                                    v-for="txn in recentTransactions"
                                    :key="txn.id"
                                    class="hover:bg-slate-800/30 transition-colors"
                                >
                                    <td class="py-3 px-4 font-bold text-white">
                                        <div class="flex items-center space-x-1.5">
                                            <span>{{ txn.transaction_id }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 font-sans">
                                        <div class="font-medium text-white">{{ txn.workspace?.name || txn.customer_name || 'Bilinmiyor' }}</div>
                                        <div class="text-[11px] text-slate-500">{{ txn.customer_email || txn.user?.email || '-' }}</div>
                                    </td>
                                    <td class="py-3 px-4 font-sans">
                                        <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-lg bg-slate-800 text-slate-200 border border-slate-700 text-xs font-semibold">
                                            <span>{{ txn.gateway_name }}</span>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 font-bold text-white">
                                        {{ txn.amount }} {{ txn.currency }}
                                    </td>
                                    <td class="py-3 px-4 font-sans">
                                        <span
                                            class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase"
                                            :class="txn.is_simulation ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'"
                                        >
                                            {{ txn.is_simulation ? $t('admin.pos_mode_simulation') : $t('admin.pos_mode_live') }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 font-sans">
                                        <span
                                            class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold"
                                            :class="{
                                                'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20': txn.status === 'success',
                                                'bg-rose-500/10 text-rose-400 border border-rose-500/20': txn.status === 'failed',
                                                'bg-amber-500/10 text-amber-400 border border-amber-500/20': txn.status === 'refunded',
                                                'bg-slate-800 text-slate-400': txn.status === 'pending'
                                            }"
                                        >
                                            <span v-if="txn.status === 'success'">● {{ $t('admin.pos_status_success') }}</span>
                                            <span v-else-if="txn.status === 'failed'">✕ {{ $t('admin.pos_status_failed') }}</span>
                                            <span v-else-if="txn.status === 'refunded'">↺ {{ $t('admin.pos_status_refunded') }}</span>
                                            <span v-else>○ {{ $t('admin.pos_status_pending') }}</span>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-slate-400 text-[11px]">
                                        {{ new Date(txn.created_at).toLocaleString() }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-sans">
                                        <div class="flex items-center justify-end space-x-2">
                                            <button
                                                v-if="txn.status === 'success'"
                                                type="button"
                                                @click="runRefund(txn)"
                                                :disabled="refundingId === txn.id"
                                                class="px-2.5 py-1 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/30 text-amber-300 text-[11px] font-semibold transition-colors disabled:opacity-50"
                                                :title="$t('admin.pos_action_refund')"
                                            >
                                                {{ $t('admin.pos_action_refund') }}
                                            </button>
                                            <button
                                                type="button"
                                                @click="selectedTxn = txn"
                                                class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white transition-colors"
                                                title="Detaylar"
                                            >
                                                <ExternalLink class="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gateway Settings Edit Modal -->
        <div
            v-if="editingGateway"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-in fade-in duration-150"
        >
            <div class="w-full max-w-lg rounded-3xl bg-slate-900 border border-slate-800 p-6 shadow-2xl space-y-5">
                <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-xl">
                            {{ countryFlags[editingGateway.country_code] || '🌐' }}
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white">{{ editingGateway.name }} {{ $t('admin.pos_gateway_settings', 'POS Ayarları') }}</h3>
                            <p class="text-xs text-slate-400">{{ editingGateway.country_name }} &bull; {{ editingGateway.currency }}</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="closeEditGateway"
                        class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="saveGatewaySettings" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">{{ $t('common.status', 'Durum') }}</label>
                            <select
                                v-model="editForm.is_active"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white"
                            >
                                <option :value="true">{{ $t('admin.pos_active_opt', 'Aktif (Kullanılabilir)') }}</option>
                                <option :value="false">{{ $t('admin.pos_inactive_opt', 'Pasif (Kapalı)') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">{{ $t('admin.pos_mode_label', 'Çalışma Modu') }}</label>
                            <select
                                v-model="editForm.mode"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white"
                            >
                                <option value="test">{{ $t('admin.sales_mode_test', 'Test / Sandbox Modu') }}</option>
                                <option value="live">{{ $t('admin.pos_mode_live', 'Canlı (Production) Modu') }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Dynamic Credentials based on gateway code -->
                    <div class="space-y-3 pt-2 border-t border-slate-800">
                        <div v-for="(val, key) in editForm.credentials" :key="key">
                            <label class="block text-xs text-slate-400 capitalize mb-1">
                                {{ key.replace(/_/g, ' ') }}
                            </label>
                            <input
                                v-model="editForm.credentials[key]"
                                type="text"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white font-mono"
                            />
                        </div>
                        <div v-if="Object.keys(editForm.credentials).length === 0" class="text-xs text-slate-500 italic">
                            {{ $t('admin.pos_no_credentials', 'Özel kimlik anahtarı gerektirmiyor veya varsayılan konfigürasyonda.') }}
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-800 flex items-center justify-end space-x-3">
                        <button
                            type="button"
                            @click="closeEditGateway"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold"
                        >
                            {{ $t('common.cancel') }}
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-lg shadow-indigo-600/30 flex items-center space-x-1.5"
                        >
                            <Check class="w-4 h-4" />
                            <span>{{ $t('admin.pos_save_settings') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Transaction Details Modal -->
        <div
            v-if="selectedTxn"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-in fade-in duration-150"
        >
            <div class="w-full max-w-lg rounded-3xl bg-slate-900 border border-slate-800 p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white">{{ $t('admin.pos_txn_details', 'İşlem Detayları') }}: {{ selectedTxn.transaction_id }}</h3>
                    <button @click="selectedTxn = null" class="p-1 text-slate-400 hover:text-white">
                        <X class="w-4 h-4" />
                    </button>
                </div>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-1 border-b border-slate-800/60">
                        <span class="text-slate-400">{{ $t('admin.pos_workspace_label', 'Çalışma Alanı') }}:</span>
                        <span class="text-white font-semibold">{{ selectedTxn.workspace?.name || '-' }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-800/60">
                        <span class="text-slate-400">{{ $t('admin.pos_provider_label', 'POS Sağlayıcı') }}:</span>
                        <span class="text-white font-semibold">{{ selectedTxn.gateway_name }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-800/60">
                        <span class="text-slate-400">{{ $t('admin.pos_amount', 'Tutar') }}:</span>
                        <span class="text-emerald-400 font-bold font-mono">{{ selectedTxn.amount }} {{ selectedTxn.currency }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-800/60">
                        <span class="text-slate-400">{{ $t('admin.sales_payment_method', 'Kart Bilgisi') }}:</span>
                        <span class="text-slate-200 font-mono">{{ selectedTxn.card_brand }} **** {{ selectedTxn.card_last_four }}</span>
                    </div>
                    <div v-if="selectedTxn.error_message" class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-xs">
                        <strong>{{ $t('admin.pos_error_message_label', 'Hata Mesajı') }}:</strong> {{ selectedTxn.error_message }}
                    </div>
                    <div class="pt-2">
                        <span class="text-slate-400 block mb-1">{{ $t('admin.pos_response_payload', 'Yanıt Payload\'ı (JSON)') }}:</span>
                        <pre class="p-3 rounded-xl bg-slate-950 border border-slate-800 text-[11px] text-slate-300 overflow-x-auto font-mono max-h-40">{{ JSON.stringify(selectedTxn.response_payload, null, 2) }}</pre>
                    </div>
                </div>
                <div class="pt-3 border-t border-slate-800 flex justify-end">
                    <button
                        type="button"
                        @click="selectedTxn = null"
                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold"
                    >
                        {{ $t('common.close') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
