<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    CreditCard,
    Check,
    Zap,
    Shield,
    Sparkles
} from 'lucide-vue-next';

const props = defineProps<{
    subscription: any;
    currentPlan: string;
    plans: Record<string, any>;
    stripeKey: string | null;
}>();

const form = useForm({
    plan: '',
});

const selectPlan = (planKey: string) => {
    form.plan = planKey;
    form.post('/billing/plan');
};
</script>

<template>
    <AppLayout :title="$t('billing.page_title')">
        <Head :title="$t('billing.page_title')" />

        <div class="space-y-8 max-w-6xl mx-auto">
            <div class="text-center max-w-2xl mx-auto">
                <h1 class="text-3xl font-bold tracking-tight text-white">{{ $t('billing.title') }}</h1>
                <p class="text-sm text-slate-400 mt-2">
                    {{ $t('billing.subtitle') }}
                </p>
            </div>

            <!-- Plans Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4">
                <div
                    v-for="(p, key) in plans"
                    :key="key"
                    class="p-8 rounded-3xl bg-slate-900/50 border transition-all flex flex-col justify-between"
                    :class="currentPlan === key ? 'border-indigo-500 shadow-xl shadow-indigo-500/10 bg-indigo-500/5' : 'border-slate-800/80 hover:border-slate-700'"
                >
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold text-white">{{ p.name }}</h3>
                            <span
                                v-if="currentPlan === key"
                                class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-500/20 text-indigo-400 border border-indigo-500/30"
                            >
                                {{ $t('billing.current_plan_badge') }}
                            </span>
                        </div>

                        <div class="text-3xl font-extrabold text-white">
                            {{ p.price }}
                        </div>

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
                            @click="selectPlan(key as string)"
                            :disabled="form.processing"
                            class="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 transition-all"
                        >
                            {{ key === 'free' ? $t('billing.downgrade_free') : $t('billing.upgrade_plan') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
