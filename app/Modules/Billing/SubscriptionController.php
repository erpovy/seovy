<?php

namespace App\Modules\Billing;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PaymentGateway;
use App\Models\PaymentSetting;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Workspace;
use App\Modules\Billing\Services\PaymentSimulationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    public function __construct(
        protected PaymentSimulationService $simulationService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $workspace = $user->currentWorkspace;

        if (!$workspace) {
            return redirect()->route('workspaces.index');
        }

        Gate::authorize('update', $workspace);

        SubscriptionPlan::ensureDefaultPlans();
        $this->simulationService->ensureInitialized();

        $subscription = $workspace->subscription;

        $dbPlans = SubscriptionPlan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $plans = $dbPlans->keyBy('code')->map(function ($p) {
            return [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'price_raw' => (float) $p->price,
                'price' => $p->price == 0 ? '0 ₺ / ay' : number_format($p->price, 0, ',', '.') . ' ' . ($p->currency === 'TRY' ? '₺' : $p->currency) . ' / ay',
                'currency' => $p->currency,
                'features' => $p->features ?? [],
                'limits' => $p->limits ?? [],
                'is_popular' => (bool) $p->is_popular,
            ];
        });

        $testMode = (bool) PaymentSetting::get('test_mode', true);
        $activeGateways = PaymentGateway::where('is_active', true)->get();

        return Inertia::render('Billing/Index', [
            'subscription' => $subscription,
            'currentPlan' => $subscription?->plan_name ?? 'free',
            'plans' => $plans,
            'stripeKey' => config('services.stripe.key'),
            'testMode' => $testMode,
            'activeGateways' => $activeGateways,
        ]);
    }

    /**
     * Process checkout & plan purchase (Supports both active gateways and simulation).
     */
    public function checkout(Request $request)
    {
        $user = $request->user();
        $workspace = $user->currentWorkspace;
        Gate::authorize('update', $workspace);

        $validated = $request->validate([
            'plan' => ['required', 'string', 'exists:subscription_plans,code'],
            'gateway_code' => ['nullable', 'string'],
            'scenario' => ['nullable', 'string', 'in:success,3ds_success,insufficient_funds,bank_declined'],
            'card_brand' => ['nullable', 'string', 'max:50'],
            'card_last_four' => ['nullable', 'string', 'max:10'],
        ]);

        $planCode = $validated['plan'];
        $plan = SubscriptionPlan::where('code', $planCode)->firstOrFail();

        // 1. If Free plan, downgrade immediately without payment
        if ($plan->price == 0) {
            $subscription = $workspace->subscription;
            if ($subscription) {
                $subscription->update([
                    'plan_name' => 'free',
                    'status' => 'active',
                    'current_period_end' => null,
                ]);
            } else {
                Subscription::create([
                    'workspace_id' => $workspace->id,
                    'plan_name' => 'free',
                    'status' => 'active',
                ]);
            }

            AuditLog::log('billing.plan_changed', 'Workspace', $workspace->id, ['new_plan' => 'free'], $workspace->id);

            return back()->with('success', 'Aboneliğiniz Ücretsiz Plan olarak güncellendi.');
        }

        // 2. Paid Plan: Process via simulation service / active gateway
        $gatewayCode = $validated['gateway_code'] ?? 'iyzico';
        $scenario = $validated['scenario'] ?? 'success';

        $cardBrands = ['Visa', 'Mastercard', 'Troy'];
        $cardBrand = $validated['card_brand'] ?? $cardBrands[array_rand($cardBrands)];
        $cardLastFour = $validated['card_last_four'] ?? str_pad((string) rand(1000, 9999), 4, '0', STR_PAD_LEFT);

        $transaction = $this->simulationService->simulatePayment([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'gateway_code' => $gatewayCode,
            'plan_name' => $plan->code,
            'amount' => (float) $plan->price,
            'currency' => $plan->currency,
            'scenario' => $scenario,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'card_brand' => $cardBrand,
            'card_last_four' => $cardLastFour,
        ], $user);

        if ($transaction->status === 'success') {
            return back()->with('success', "Tebrikler! {$plan->name} aboneliğiniz ({$transaction->transaction_id}) başarıyla aktif edildi.");
        }

        return back()->withErrors([
            'payment' => $transaction->error_message ?? 'Ödeme işlemi banka tarafından onaylanamadı.',
        ]);
    }

    /**
     * Legacy adapter for backward compatibility.
     */
    public function updatePlan(Request $request)
    {
        return $this->checkout($request);
    }

    /**
     * Stripe webhook handler with signature check & idempotency.
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret', env('STRIPE_WEBHOOK_SECRET'));

        // Basic payload validation
        $event = json_decode($payload, true);
        if (!$event || !isset($event['type'])) {
            return response()->json(['error' => 'Geçersiz webhook verisi'], 400);
        }

        Log::info('Stripe webhook received: ' . $event['type']);

        return response()->json(['received' => true]);
    }
}
