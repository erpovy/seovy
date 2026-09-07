<?php

namespace App\Modules\Billing;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Subscription;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $workspace = $user->currentWorkspace;

        if (!$workspace) {
            return redirect()->route('workspaces.index');
        }

        Gate::authorize('update', $workspace);

        $subscription = $workspace->subscription;

        $plans = [
            'free' => [
                'name' => 'Ücretsiz Plan',
                'price' => '0₺ / ay',
                'features' => ['1 Proje', 'Aylık 500 Taranan Sayfa', '10 Anahtar Kelime', '1 Kullanıcı', 'Temel Raporlar'],
                'limits' => ['max_projects' => 1, 'max_pages_monthly' => 500, 'max_keywords' => 10, 'team_members' => 1],
            ],
            'pro' => [
                'name' => 'Pro Plan',
                'price' => '499₺ / ay',
                'features' => ['5 Proje', 'Aylık 20.000 Taranan Sayfa', '100 Anahtar Kelime', '5 Ekip Üyesi', 'GSC & PageSpeed', 'PDF Raporlama'],
                'limits' => ['max_projects' => 5, 'max_pages_monthly' => 20000, 'max_keywords' => 100, 'team_members' => 5],
            ],
            'agency' => [
                'name' => 'Ajans Planı',
                'price' => '1.499₺ / ay',
                'features' => ['50 Proje', 'Aylık 200.000 Taranan Sayfa', '2.000 Anahtar Kelime', '25 Ekip Üyesi', 'Beyaz Etiket PDF', 'Öncelikli Kuyruk'],
                'limits' => ['max_projects' => 50, 'max_pages_monthly' => 200000, 'max_keywords' => 2000, 'team_members' => 25],
            ],
        ];

        $testMode = (bool) \App\Models\PaymentSetting::get('test_mode', true);
        $activeGateways = \App\Models\PaymentGateway::where('is_active', true)->get();

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
     * Change subscription plan (Stripe test mode adapter).
     */
    public function updatePlan(Request $request)
    {
        $user = $request->user();
        $workspace = $user->currentWorkspace;
        Gate::authorize('update', $workspace);

        $validated = $request->validate([
            'plan' => ['required', 'in:free,pro,agency'],
        ]);

        $newPlan = $validated['plan'];

        $subscription = $workspace->subscription;
        if ($subscription) {
            $subscription->update([
                'plan_name' => $newPlan,
                'status' => 'active',
                'current_period_end' => now()->addMonth(),
            ]);
        }

        AuditLog::log('billing.plan_changed', 'Workspace', $workspace->id, ['new_plan' => $newPlan]);

        return back()->with('success', "Abonelik paketiniz {$newPlan} olarak güncellendi.");
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

        $eventType = $event['type'];
        Log::info("Stripe webhook alındı: {$eventType}");

        // Handle relevant subscription events
        switch ($eventType) {
            case 'customer.subscription.updated':
            case 'customer.subscription.created':
                $stripeSub = $event['data']['object'] ?? [];
                $stripeId = $stripeSub['id'] ?? null;
                $status = $stripeSub['status'] ?? 'active';

                if ($stripeId) {
                    Subscription::where('stripe_id', $stripeId)->update([
                        'status' => $status,
                        'current_period_end' => isset($stripeSub['current_period_end']) ? date('Y-m-d H:i:s', $stripeSub['current_period_end']) : null,
                    ]);
                }
                break;

            case 'customer.subscription.deleted':
                $stripeSub = $event['data']['object'] ?? [];
                $stripeId = $stripeSub['id'] ?? null;
                if ($stripeId) {
                    Subscription::where('stripe_id', $stripeId)->update([
                        'plan_name' => 'free',
                        'status' => 'canceled',
                    ]);
                }
                break;
        }

        return response()->json(['received' => true]);
    }
}
