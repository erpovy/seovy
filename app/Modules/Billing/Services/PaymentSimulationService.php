<?php

namespace App\Modules\Billing\Services;

use App\Models\AuditLog;
use App\Models\PaymentGateway;
use App\Models\PaymentSetting;
use App\Models\PaymentTransaction;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Str;

class PaymentSimulationService
{
    /**
     * Ensure default gateways and settings are initialized in database.
     */
    public function ensureInitialized(): void
    {
        if (PaymentGateway::count() === 0) {
            $defaultGateways = PaymentGateway::getDefaultGateways();
            foreach ($defaultGateways as $gw) {
                PaymentGateway::create($gw);
            }
        }

        if (PaymentSetting::where('key', 'test_mode')->doesntExist()) {
            PaymentSetting::set('test_mode', true);
        }

        if (PaymentSetting::where('key', 'default_gateway')->doesntExist()) {
            PaymentSetting::set('default_gateway', 'iyzico');
        }
    }

    /**
     * Check if test/simulation mode is currently active.
     */
    public function isTestModeActive(): bool
    {
        return (bool) PaymentSetting::get('test_mode', true);
    }

    /**
     * Set test/simulation mode.
     */
    public function setTestMode(bool $active): void
    {
        PaymentSetting::set('test_mode', $active);
    }

    /**
     * Simulate a purchase transaction.
     */
    public function simulatePayment(array $data, ?User $actor = null): PaymentTransaction
    {
        $this->ensureInitialized();

        $gatewayCode = $data['gateway_code'] ?? 'iyzico';
        $gateway = PaymentGateway::where('code', $gatewayCode)->first();
        $gatewayName = $gateway ? $gateway->name : strtoupper($gatewayCode);
        $currency = $gateway ? $gateway->currency : ($data['currency'] ?? 'TRY');

        $workspaceId = $data['workspace_id'] ?? null;
        $workspace = $workspaceId ? Workspace::find($workspaceId) : null;
        $userId = $data['user_id'] ?? ($actor ? $actor->id : $workspace?->owner_id);

        $planName = $data['plan_name'] ?? 'pro';
        $scenario = $data['scenario'] ?? 'success'; // success, 3ds_success, insufficient_funds, bank_declined

        $amount = (float) ($data['amount'] ?? ($planName === 'agency' ? 1499.00 : ($planName === 'pro' ? 499.00 : 0.00)));

        $customerName = $data['customer_name'] ?? ($actor?->name ?? $workspace?->owner?->name ?? 'Demo Müşteri');
        $customerEmail = $data['customer_email'] ?? ($actor?->email ?? $workspace?->owner?->email ?? 'demo@seovy.com');

        $cardBrands = ['Visa', 'Mastercard', 'Troy', 'Amex'];
        $cardBrand = $data['card_brand'] ?? $cardBrands[array_rand($cardBrands)];
        $cardLastFour = $data['card_last_four'] ?? str_pad((string) rand(1000, 9999), 4, '0', STR_PAD_LEFT);

        $txnId = 'TXN-SIM-' . date('Ymd') . '-' . strtoupper(Str::random(6));

        $status = 'pending';
        $errorMessage = null;

        if ($scenario === 'success' || $scenario === '3ds_success') {
            $status = 'success';

            // If a workspace was provided, update its subscription plan
            if ($workspace) {
                $sub = $workspace->subscription;
                if ($sub) {
                    $sub->update([
                        'plan_name' => $planName,
                        'status' => 'active',
                        'current_period_end' => now()->addMonth(),
                    ]);
                } else {
                    Subscription::create([
                        'workspace_id' => $workspace->id,
                        'plan_name' => $planName,
                        'status' => 'active',
                        'current_period_end' => now()->addMonth(),
                    ]);
                }

                AuditLog::log('billing.payment_simulated', 'Workspace', $workspace->id, [
                    'transaction_id' => $txnId,
                    'gateway' => $gatewayName,
                    'plan' => $planName,
                    'amount' => "{$amount} {$currency}",
                    'scenario' => $scenario,
                    'mode' => 'SIMULATION',
                ], $workspace->id);
            }
        } elseif ($scenario === 'insufficient_funds') {
            $status = 'failed';
            $errorMessage = 'Banka Yanıtı: Yetersiz Bakiye / Kart Limiti (ERR-51 / Insufficient Funds)';
        } elseif ($scenario === 'bank_declined') {
            $status = 'failed';
            $errorMessage = 'İşlem Reddedildi: Banka Yetki Vermedi (ERR-05 / Do Not Honor)';
        } else {
            $status = 'failed';
            $errorMessage = 'Simüle edilmiş işlem reddi.';
        }

        $transaction = PaymentTransaction::create([
            'transaction_id' => $txnId,
            'workspace_id' => $workspaceId,
            'user_id' => $userId,
            'gateway_code' => $gatewayCode,
            'gateway_name' => $gatewayName,
            'plan_name' => $planName,
            'amount' => $amount,
            'currency' => $currency,
            'status' => $status,
            'is_simulation' => true,
            'simulation_scenario' => $scenario,
            'card_brand' => $cardBrand,
            'card_last_four' => $cardLastFour,
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'error_message' => $errorMessage,
            'response_payload' => [
                'simulation' => true,
                'gateway_code' => $gatewayCode,
                'gateway_name' => $gatewayName,
                'auth_code' => $status === 'success' ? 'AUTH-' . rand(100000, 999999) : null,
                '3ds_verified' => ($scenario === '3ds_success'),
                'simulated_at' => now()->toIso8601String(),
            ],
        ]);

        return $transaction;
    }

    /**
     * Simulate a refund for a transaction.
     */
    public function simulateRefund(PaymentTransaction $transaction, ?User $actor = null): PaymentTransaction
    {
        $transaction->update([
            'status' => 'refunded',
            'response_payload' => array_merge($transaction->response_payload ?? [], [
                'refunded_at' => now()->toIso8601String(),
                'refund_auth' => 'REF-' . rand(100000, 999999),
                'refunded_by' => $actor?->name ?? 'Admin',
            ]),
        ]);

        if ($transaction->workspace_id) {
            AuditLog::log('billing.refund_simulated', 'Workspace', $transaction->workspace_id, [
                'transaction_id' => $transaction->transaction_id,
                'amount' => "{$transaction->amount} {$transaction->currency}",
                'mode' => 'SIMULATION',
            ], $transaction->workspace_id);
        }

        return $transaction;
    }
}
