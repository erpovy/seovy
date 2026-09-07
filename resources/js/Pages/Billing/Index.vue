<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    CreditCard,
    Check,
    Zap,
    Shield,
    Sparkles,
    Globe,
    Search,
    Users,
    Tag,
    Lock,
    X,
    AlertCircle,
    CheckCircle2
} from 'lucide-vue-next';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps<{
    subscription: any;
    currentPlan: string;
    plans: Record<string, any>;
    stripeKey: string | null;
    testMode: boolean;
    activeGateways: Array<any>;
}>();

// Checkout Modal State
const isCheckoutOpen = ref(false);
const selectedPlan = ref<any>(null);

const checkoutForm = useForm({
    plan: '',
    gateway_code: props.activeGateways && props.activeGateways.length > 0 ? props.activeGateways[0].code : 'iyzico',
    scenario: 'success',
    card_brand: 'Visa',
    card_holder: '',
    card_number: '',
    card_expiry: '',
    card_cvv: '',
});

const onPlanButtonClick = (key: string, p: any) => {
    if (key === 'free') {
        // Direct free plan downgrade
        checkoutForm.plan = 'free';
        checkoutForm.post('/billing/checkout');
        return;
    }

    selectedPlan.value = { ...p, code: key };
    checkoutForm.plan = key;
    checkoutForm.card_holder = 'Kart Sahibi';
    checkoutForm.card_number = '4242 •••• •••• 4242';
    checkoutForm.card_expiry = '12/28';
    checkoutForm.card_cvv = '123';
    isCheckoutOpen.value = true;
};

const closeCheckout = () => {
    isCheckoutOpen.value = false;
    selectedPlan.value = null;
};

const submitCheckout = () => {
    checkoutForm.post('/billing/checkout', {
        onSuccess: () => {
            closeCheckout();
        },
    });
};
</script>

<template>
    <AppLayout :title="$t('billing.page_title')">
        <Head :title="$t('billing.page_title')" />

        <div class="space-y-8 max-w-6xl mx-auto">
            <!-- Test Mode Notification Banner -->
            <div
                v-if="testMode"
                class="p-4 rounded-2xl bg-amber-950/20 border border-amber-500/30 flex items-center justify-between gap-4 text-xs"
            >
                <div class="flex items-center space-x-3">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-pulse"></span>
                    <div>
                        <strong class="text-amber-300 font-semibold">{{ $t('billing.test_mode_banner_title') }}</strong>
                        <p class="text-slate-400 mt-0.5">{{ $t('billing.test_mode_banner_desc') }}</p>
                    </div>
                </div>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-amber-500/20 text-amber-400 border border-amber-500/30 shrink-0">
                    {{ $t('admin.pos_mode_simulation') }}
                </span>
            </div>

            <!-- Page Header -->
            <div class="text-center max-w-2xl mx-auto">
                <h1 class="text-3xl font-bold tracking-tight text-white">{{ $t('billing.title') }}</h1>
                <p class="text-sm text-slate-400 mt-2">
                    {{ $t('billing.subtitle') }}
                </p>
            </div>

            <!-- Plans Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-2">
                <div
                    v-for="(p, key) in plans"
                    :key="key"
                    class="p-8 rounded-3xl bg-slate-900/50 border transition-all flex flex-col justify-between relative overflow-hidden"
                    :class="currentPlan === key ? 'border-indigo-500 shadow-2xl shadow-indigo-500/10 bg-indigo-500/5' : 'border-slate-800/80 hover:border-slate-700'"
                >
                    <div v-if="p.is_popular" class="absolute -right-12 top-6 bg-indigo-600 text-white text-[10px] font-extrabold uppercase px-12 py-1 rotate-45 shadow-lg tracking-wider">
                        {{ $t('admin.plans_popular') }}
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between pr-8">
                            <h3 class="text-lg font-bold text-white">{{ p.name }}</h3>
                            <span
                                v-if="currentPlan === key"
                                class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-500/20 text-emerald-400 border border-emerald-500/30"
                            >
                                {{ $t('billing.current_plan_badge') }}
                            </span>
                        </div>

                        <div class="text-3xl font-extrabold text-white font-mono">
                            {{ p.price }}
                        </div>

                        <!-- Quota Summary Box -->
                        <div v-if="p.limits" class="p-3.5 rounded-2xl bg-slate-950/70 border border-slate-800/80 space-y-1.5 text-xs text-slate-300">
                            <div class="flex items-center space-x-2">
                                <Globe class="w-3.5 h-3.5 text-cyan-400" />
                                <span>{{ p.limits.max_projects || 1 }} {{ $t('admin.plans_limit_projects') }}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <Search class="w-3.5 h-3.5 text-violet-400" />
                                <span>{{ (p.limits.max_pages_monthly || 500).toLocaleString() }} {{ $t('admin.plans_limit_pages') }}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <Tag class="w-3.5 h-3.5 text-emerald-400" />
                                <span>{{ p.limits.max_keywords || 10 }} {{ $t('admin.plans_limit_keywords') }}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <Users class="w-3.5 h-3.5 text-amber-400" />
                                <span>{{ p.limits.team_members || 1 }} {{ $t('admin.plans_limit_members') }}</span>
                            </div>
                        </div>

                        <!-- Features bullets -->
                        <div class="pt-4 border-t border-slate-800 space-y-2.5 text-xs text-slate-300">
                            <div v-for="(feat, idx) in p.features" :key="idx" class="flex items-center space-x-2">
                                <Check class="w-4 h-4 text-emerald-400 shrink-0" />
                                <span>{{ feat }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-8">
                        <button
                            v-if="currentPlan === key"
                            disabled
                            class="w-full py-3 rounded-xl bg-slate-800 text-slate-400 text-xs font-semibold cursor-default"
                        >
                            {{ $t('billing.active_plan') }}
                        </button>
                        <button
                            v-else
                            @click="onPlanButtonClick(key as string, p)"
                            :disabled="checkoutForm.processing"
                            class="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-lg shadow-indigo-600/30 transition-all flex items-center justify-center space-x-2 disabled:opacity-50"
                        >
                            <CreditCard class="w-3.5 h-3.5" />
                            <span>{{ key === 'free' ? $t('billing.downgrade_free') : $t('billing.upgrade_plan') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Checkout & Purchase Modal -->
        <div
            v-if="isCheckoutOpen && selectedPlan"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-md animate-in fade-in duration-150"
        >
            <div class="w-full max-w-lg rounded-3xl bg-slate-900 border border-slate-800 p-6 sm:p-8 shadow-2xl space-y-6">
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-11 h-11 rounded-2xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
                            <CreditCard class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">{{ $t('billing.checkout_title') }}</h3>
                            <p class="text-xs text-slate-400">{{ selectedPlan.name }} &bull; {{ selectedPlan.price }}</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="closeCheckout"
                        class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- Simulation Info Banner -->
                <div
                    v-if="testMode"
                    class="p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-xs space-y-1"
                >
                    <div class="flex items-center space-x-2 text-amber-300 font-semibold">
                        <Sparkles class="w-4 h-4" />
                        <span>{{ $t('billing.simulation_active_badge') }}</span>
                    </div>
                    <p class="text-[11px] text-slate-400">
                        {{ $t('billing.simulation_notice_text') }}
                    </p>
                </div>

                <!-- Checkout Form -->
                <form @submit.prevent="submitCheckout" class="space-y-4 text-xs">
                    <!-- Gateway selection -->
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1.5">{{ $t('billing.select_gateway') }}</label>
                        <select
                            v-model="checkoutForm.gateway_code"
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500"
                        >
                            <option v-for="gw in activeGateways" :key="gw.code" :value="gw.code">
                                {{ gw.name }} ({{ gw.currency }})
                            </option>
                        </select>
                    </div>

                    <!-- Simulation Scenario (when test mode active) -->
                    <div v-if="testMode">
                        <label class="block font-semibold text-slate-300 mb-1.5">{{ $t('billing.test_scenario_label') }}</label>
                        <select
                            v-model="checkoutForm.scenario"
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500"
                        >
                            <option value="success">{{ $t('admin.pos_scenario_success') }}</option>
                            <option value="3ds_success">{{ $t('admin.pos_scenario_3ds') }}</option>
                            <option value="insufficient_funds">{{ $t('admin.pos_scenario_insufficient') }}</option>
                            <option value="bank_declined">{{ $t('admin.pos_scenario_declined') }}</option>
                        </select>
                    </div>

                    <!-- Card Details -->
                    <div class="space-y-3 pt-2 border-t border-slate-800/80">
                        <div>
                            <label class="block text-slate-400 mb-1">{{ $t('billing.card_holder') }}</label>
                            <input
                                v-model="checkoutForm.card_holder"
                                type="text"
                                required
                                class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-mono"
                            />
                        </div>

                        <div>
                            <label class="block text-slate-400 mb-1">{{ $t('billing.card_number') }}</label>
                            <input
                                v-model="checkoutForm.card_number"
                                type="text"
                                required
                                class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-mono"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-slate-400 mb-1">{{ $t('billing.card_expiry') }}</label>
                                <input
                                    v-model="checkoutForm.card_expiry"
                                    type="text"
                                    placeholder="MM/YY"
                                    required
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-mono"
                                />
                            </div>
                            <div>
                                <label class="block text-slate-400 mb-1">CVC / CVV</label>
                                <input
                                    v-model="checkoutForm.card_cvv"
                                    type="text"
                                    placeholder="123"
                                    required
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-mono"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Error Alert -->
                    <div v-if="checkoutForm.errors.payment" class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-xs flex items-center space-x-2">
                        <AlertCircle class="w-4 h-4 shrink-0 text-rose-400" />
                        <span>{{ checkoutForm.errors.payment }}</span>
                    </div>

                    <!-- Order Total & Submit -->
                    <div class="pt-4 border-t border-slate-800 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] text-slate-500 block">{{ $t('billing.total_payable') }}</span>
                            <span class="text-base font-extrabold text-white font-mono">{{ selectedPlan.price }}</span>
                        </div>

                        <div class="flex items-center space-x-3">
                            <button
                                type="button"
                                @click="closeCheckout"
                                class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold"
                            >
                                {{ $t('common.cancel') }}
                            </button>
                            <button
                                type="submit"
                                :disabled="checkoutForm.processing"
                                class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold shadow-lg shadow-indigo-600/30 flex items-center space-x-1.5 disabled:opacity-50"
                            >
                                <Lock class="w-3.5 h-3.5" />
                                <span>{{ testMode ? $t('billing.confirm_test_payment') : $t('billing.pay_now') }}</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
