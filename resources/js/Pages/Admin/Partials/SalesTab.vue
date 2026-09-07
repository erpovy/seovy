<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    Clock,
    Search,
    CreditCard,
    DollarSign,
    CheckCircle2,
    XCircle,
    RotateCcw,
    Sparkles,
    ExternalLink,
    Filter,
    X,
    User,
    Briefcase,
    Copy,
    Check,
    Calendar
} from 'lucide-vue-next';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps<{
    recentTransactions: Array<any>;
    stats: {
        total_payment_volume: number;
        total_payment_transactions: number;
        simulated_transactions: number;
        active_gateways_count: number;
    };
    filters: {
        sales_search?: string;
        sales_plan?: string;
        sales_mode?: string;
        sales_status?: string;
    };
}>();

const searchQuery = ref(props.filters.sales_search || '');
const planFilter = ref(props.filters.sales_plan || 'all');
const modeFilter = ref(props.filters.sales_mode || 'all');
const statusFilter = ref(props.filters.sales_status || 'all');
const selectedTxn = ref<any>(null);
const copySuccess = ref(false);
const refundingId = ref<number | null>(null);

let searchTimeout: any = null;

const applyFilters = () => {
    router.get(
        '/admin',
        {
            tab: 'sales',
            sales_search: searchQuery.value.trim() || undefined,
            sales_plan: planFilter.value !== 'all' ? planFilter.value : undefined,
            sales_mode: modeFilter.value !== 'all' ? modeFilter.value : undefined,
            sales_status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        },
        {
            preserveState: true,
            replace: true,
        }
    );
};

const onSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
};

const copyText = (text: string) => {
    navigator.clipboard.writeText(text);
    copySuccess.value = true;
    setTimeout(() => {
        copySuccess.value = false;
    }, 1500);
};

const runRefund = (txn: any) => {
    if (!confirm(t('admin.pos_refund_confirm') || 'Bu satın alma işlemi için iade başlatmak istediğinize emin misiniz?')) return;
    refundingId.value = txn.id;
    router.post(`/admin/payments/transactions/${txn.id}/refund`, {}, {
        preserveScroll: true,
        onFinish: () => {
            refundingId.value = null;
        }
    });
};
</script>

<template>
    <div class="space-y-6">
        <!-- Top Metrics Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-5 rounded-3xl bg-slate-900/50 border border-slate-800/80 space-y-1.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ $t('admin.sales_total_volume') }}</span>
                    <DollarSign class="w-4 h-4 text-emerald-400" />
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-white">
                    {{ (stats.total_payment_volume || 0).toLocaleString() }} ₺
                </div>
                <div class="text-[11px] text-emerald-400 flex items-center space-x-1">
                    <CheckCircle2 class="w-3 h-3" />
                    <span>{{ $t('admin.sales_revenue_verified') }}</span>
                </div>
            </div>

            <div class="p-5 rounded-3xl bg-slate-900/50 border border-slate-800/80 space-y-1.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ $t('admin.sales_total_count') }}</span>
                    <CreditCard class="w-4 h-4 text-indigo-400" />
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-white">
                    {{ stats.total_payment_transactions || 0 }}
                </div>
                <div class="text-[11px] text-slate-400">
                    {{ $t('admin.sales_all_accounts') }}
                </div>
            </div>

            <div class="p-5 rounded-3xl bg-slate-900/50 border border-slate-800/80 space-y-1.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ $t('admin.pos_simulated_count') }}</span>
                    <Sparkles class="w-4 h-4 text-amber-400" />
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-amber-300">
                    {{ stats.simulated_transactions || 0 }}
                </div>
                <div class="text-[11px] text-amber-400/80">
                    {{ $t('admin.sales_test_simulations') }}
                </div>
            </div>

            <div class="p-5 rounded-3xl bg-slate-900/50 border border-slate-800/80 space-y-1.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ $t('admin.sales_live_count') }}</span>
                    <CheckCircle2 class="w-4 h-4 text-cyan-400" />
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-cyan-300">
                    {{ Math.max(0, (stats.total_payment_transactions || 0) - (stats.simulated_transactions || 0)) }}
                </div>
                <div class="text-[11px] text-cyan-400/80">
                    {{ $t('admin.sales_live_orders') }}
                </div>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="p-4 rounded-3xl bg-slate-900/60 border border-slate-800/80 space-y-3">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <!-- Search Box -->
                <div class="relative flex-1 max-w-md">
                    <Search class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
                    <input
                        v-model="searchQuery"
                        @input="onSearchInput"
                        type="text"
                        :placeholder="$t('admin.sales_search_placeholder')"
                        class="w-full pl-10 pr-4 py-2 bg-slate-950/80 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                    />
                </div>

                <!-- Dropdown Filters -->
                <div class="flex items-center flex-wrap gap-2 text-xs">
                    <!-- Plan Filter -->
                    <select
                        v-model="planFilter"
                        @change="applyFilters"
                        class="px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-slate-300 focus:outline-none focus:border-indigo-500"
                    >
                        <option value="all">{{ $t('admin.sales_filter_all_plans') }}</option>
                        <option value="pro">Pro Plan</option>
                        <option value="agency">Ajans Planı</option>
                        <option value="free">Ücretsiz Plan</option>
                    </select>

                    <!-- Mode Filter -->
                    <select
                        v-model="modeFilter"
                        @change="applyFilters"
                        class="px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-slate-300 focus:outline-none focus:border-indigo-500"
                    >
                        <option value="all">{{ $t('admin.sales_filter_all_modes') }}</option>
                        <option value="live">{{ $t('admin.pos_mode_live') }}</option>
                        <option value="simulation">{{ $t('admin.pos_mode_simulation') }}</option>
                    </select>

                    <!-- Status Filter -->
                    <select
                        v-model="statusFilter"
                        @change="applyFilters"
                        class="px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-slate-300 focus:outline-none focus:border-indigo-500"
                    >
                        <option value="all">{{ $t('admin.sales_filter_all_statuses') }}</option>
                        <option value="success">{{ $t('admin.pos_status_success') }}</option>
                        <option value="refunded">{{ $t('admin.pos_status_refunded') }}</option>
                        <option value="failed">{{ $t('admin.pos_status_failed') }}</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Sales Logs Table -->
        <div class="overflow-hidden rounded-3xl border border-slate-800/80 bg-slate-900/60 shadow-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-950/80 text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-800 font-semibold">
                        <tr>
                            <th class="py-3.5 px-4">{{ $t('admin.sales_th_txn') }}</th>
                            <th class="py-3.5 px-4">{{ $t('admin.sales_th_account') }}</th>
                            <th class="py-3.5 px-4">{{ $t('admin.sales_th_plan') }}</th>
                            <th class="py-3.5 px-4">{{ $t('admin.sales_th_amount') }}</th>
                            <th class="py-3.5 px-4">{{ $t('admin.sales_th_gateway') }}</th>
                            <th class="py-3.5 px-4">{{ $t('admin.sales_th_mode') }}</th>
                            <th class="py-3.5 px-4">{{ $t('admin.sales_th_status') }}</th>
                            <th class="py-3.5 px-4">{{ $t('admin.sales_th_date') }}</th>
                            <th class="py-3.5 px-4 text-right">{{ $t('admin.pos_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <tr v-if="recentTransactions.length === 0">
                            <td colspan="9" class="p-12 text-center text-slate-500">
                                <CreditCard class="w-10 h-10 mx-auto mb-2 text-slate-600" />
                                <div class="font-semibold text-white">{{ $t('admin.sales_no_records') }}</div>
                                <div class="text-xs text-slate-500 mt-1">{{ $t('admin.sales_no_records_desc') }}</div>
                            </td>
                        </tr>

                        <tr
                            v-for="txn in recentTransactions"
                            :key="txn.id"
                            class="hover:bg-slate-800/30 transition-colors"
                        >
                            <!-- TXN ID -->
                            <td class="py-3.5 px-4 font-mono font-bold text-white whitespace-nowrap">
                                <div class="flex items-center space-x-1.5">
                                    <span>{{ txn.transaction_id }}</span>
                                    <button
                                        type="button"
                                        @click="copyText(txn.transaction_id)"
                                        class="p-1 text-slate-500 hover:text-white"
                                        title="Kopyala"
                                    >
                                        <Copy class="w-3 h-3" />
                                    </button>
                                </div>
                            </td>

                            <!-- Account / Customer -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center space-x-2">
                                    <div class="w-7 h-7 rounded-lg bg-indigo-600/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold text-xs shrink-0">
                                        {{ (txn.workspace?.name || txn.customer_name || 'U').charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="truncate max-w-[180px]">
                                        <div class="font-semibold text-white truncate">
                                            {{ txn.workspace?.name || txn.customer_name || 'Bilinmiyor' }}
                                        </div>
                                        <div class="text-[11px] text-slate-500 truncate">
                                            {{ txn.customer_email || txn.user?.email || '-' }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Plan -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span
                                    class="px-2.5 py-1 rounded-lg text-xs font-bold uppercase tracking-wider"
                                    :class="{
                                        'bg-purple-500/10 text-purple-300 border border-purple-500/20': txn.plan_name === 'agency',
                                        'bg-indigo-500/10 text-indigo-300 border border-indigo-500/20': txn.plan_name === 'pro',
                                        'bg-slate-800 text-slate-300 border border-slate-700': txn.plan_name === 'free' || !['pro', 'agency'].includes(txn.plan_name)
                                    }"
                                >
                                    {{ txn.plan_name }}
                                </span>
                            </td>

                            <!-- Amount -->
                            <td class="py-3.5 px-4 font-mono font-bold text-white whitespace-nowrap">
                                {{ Number(txn.amount).toLocaleString('tr-TR', { minimumFractionDigits: 2 }) }} {{ txn.currency }}
                            </td>

                            <!-- Gateway -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-md bg-slate-950 border border-slate-800 text-slate-300 text-[11px] font-medium">
                                    {{ txn.gateway_name }}
                                </span>
                            </td>

                            <!-- Mode -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span
                                    class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase"
                                    :class="txn.is_simulation ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'"
                                >
                                    {{ txn.is_simulation ? $t('admin.pos_mode_simulation') : $t('admin.pos_mode_live') }}
                                </span>
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
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

                            <!-- Date -->
                            <td class="py-3.5 px-4 text-slate-400 text-[11px] whitespace-nowrap font-mono">
                                {{ new Date(txn.created_at).toLocaleString() }}
                            </td>

                            <!-- Actions -->
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end space-x-2">
                                    <button
                                        v-if="txn.status === 'success'"
                                        type="button"
                                        @click="runRefund(txn)"
                                        :disabled="refundingId === txn.id"
                                        class="px-2.5 py-1 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/30 text-amber-300 text-[11px] font-semibold transition-colors disabled:opacity-50"
                                    >
                                        {{ $t('admin.pos_action_refund') }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="selectedTxn = txn"
                                        class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors"
                                        title="Sipariş Detayı / Fiş"
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

        <!-- Order / Receipt Modal -->
        <div
            v-if="selectedTxn"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-in fade-in duration-150"
        >
            <div class="w-full max-w-lg rounded-3xl bg-slate-900 border border-slate-800 p-6 sm:p-7 shadow-2xl space-y-5">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                            <CheckCircle2 class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white">{{ $t('admin.sales_modal_receipt_title') }}</h3>
                            <p class="text-xs text-slate-400 font-mono">{{ selectedTxn.transaction_id }}</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="selectedTxn = null"
                        class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-2.5 text-xs">
                    <div class="flex justify-between py-1.5 border-b border-slate-800/60">
                        <span class="text-slate-400">Satın Alan Hesap:</span>
                        <span class="font-bold text-white">{{ selectedTxn.workspace?.name || selectedTxn.customer_name }}</span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-slate-800/60">
                        <span class="text-slate-400">Müşteri E-Postası:</span>
                        <span class="font-mono text-slate-300">{{ selectedTxn.customer_email || selectedTxn.user?.email || '-' }}</span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-slate-800/60">
                        <span class="text-slate-400">Satın Alınan Paket:</span>
                        <span class="font-bold text-indigo-400 uppercase">{{ selectedTxn.plan_name }}</span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-slate-800/60">
                        <span class="text-slate-400">Ödenen Tutar:</span>
                        <span class="font-bold text-emerald-400 text-sm font-mono">{{ selectedTxn.amount }} {{ selectedTxn.currency }}</span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-slate-800/60">
                        <span class="text-slate-400">Ödeme Sağlayıcı:</span>
                        <span class="text-slate-200">{{ selectedTxn.gateway_name }}</span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-slate-800/60">
                        <span class="text-slate-400">Kart / Ödeme Yöntemi:</span>
                        <span class="text-slate-300 font-mono">{{ selectedTxn.card_brand }} •••• {{ selectedTxn.card_last_four }}</span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-slate-800/60">
                        <span class="text-slate-400">İşlem Modu:</span>
                        <span class="font-bold" :class="selectedTxn.is_simulation ? 'text-amber-400' : 'text-emerald-400'">
                            {{ selectedTxn.is_simulation ? 'Test / Simülasyon' : 'Canlı (Production)' }}
                        </span>
                    </div>

                    <div v-if="selectedTxn.error_message" class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-xs">
                        <strong>Hata:</strong> {{ selectedTxn.error_message }}
                    </div>

                    <div class="pt-2">
                        <span class="text-slate-400 block mb-1">Banka & POS Yanıtı (JSON):</span>
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
