<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\PaymentGateway;
use App\Models\PaymentSetting;
use App\Models\PaymentTransaction;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentSimulationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;
    protected Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@seovy.test',
            'password' => bcrypt('password123'),
            'is_platform_admin' => true,
        ]);

        $this->regularUser = User::create([
            'name' => 'Regular Specialist',
            'email' => 'specialist@seovy.test',
            'password' => bcrypt('password123'),
            'is_platform_admin' => false,
        ]);

        $this->workspace = Workspace::create([
            'name' => 'Acme Agency',
            'slug' => 'acme-agency',
            'owner_id' => $this->regularUser->id,
        ]);
        $this->workspace->users()->attach($this->regularUser->id, ['role' => 'owner']);
        $this->regularUser->current_workspace_id = $this->workspace->id;
        $this->regularUser->save();
    }

    public function test_admin_can_view_payments_and_gateways_are_seeded(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin?tab=payments');
        $response->assertOk();

        // Check that 10 default gateways exist
        $this->assertEquals(10, PaymentGateway::count());
        $this->assertDatabaseHas('payment_gateways', ['code' => 'iyzico', 'country_code' => 'TR']);
        $this->assertDatabaseHas('payment_gateways', ['code' => 'stripe', 'country_code' => 'US']);
        $this->assertDatabaseHas('payment_gateways', ['code' => 'klarna', 'country_code' => 'DE']);
        $this->assertDatabaseHas('payment_gateways', ['code' => 'payplug', 'country_code' => 'FR']);
        $this->assertDatabaseHas('payment_gateways', ['code' => 'redsys', 'country_code' => 'ES']);
        $this->assertDatabaseHas('payment_gateways', ['code' => 'satispay', 'country_code' => 'IT']);
        $this->assertDatabaseHas('payment_gateways', ['code' => 'mercadopago', 'country_code' => 'PT']);
        $this->assertDatabaseHas('payment_gateways', ['code' => 'yookassa', 'country_code' => 'RU']);
        $this->assertDatabaseHas('payment_gateways', ['code' => 'alipay', 'country_code' => 'ZH']);
        $this->assertDatabaseHas('payment_gateways', ['code' => 'tappayments', 'country_code' => 'AR']);

        // Default test mode is true
        $this->assertTrue((bool) PaymentSetting::get('test_mode', true));
    }

    public function test_admin_can_toggle_gateway_active_and_credentials(): void
    {
        $this->actingAs($this->admin)->get('/admin?tab=payments'); // ensure seeded

        $iyzico = PaymentGateway::where('code', 'iyzico')->first();
        $this->assertNotNull($iyzico);

        $response = $this->actingAs($this->admin)->patch("/admin/payments/gateways/{$iyzico->id}", [
            'is_active' => false,
            'mode' => 'live',
            'credentials' => [
                'api_key' => 'live_iyzico_api_key_123',
                'secret_key' => 'live_iyzico_secret_key_456',
            ],
            'currency' => 'TRY',
        ]);

        $response->assertSessionHas('success');
        $iyzico->refresh();
        $this->assertFalse($iyzico->is_active);
        $this->assertEquals('live', $iyzico->mode);
        $this->assertEquals('live_iyzico_api_key_123', $iyzico->credentials['api_key']);
    }

    public function test_admin_can_toggle_global_test_mode(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/payments/settings', [
            'test_mode' => false,
            'default_gateway' => 'stripe',
        ]);

        $response->assertSessionHas('success');
        $this->assertFalse((bool) PaymentSetting::get('test_mode'));
        $this->assertEquals('stripe', PaymentSetting::get('default_gateway'));

        // Toggle back to true
        $this->actingAs($this->admin)->post('/admin/payments/settings', [
            'test_mode' => true,
        ]);
        $this->assertTrue((bool) PaymentSetting::get('test_mode'));
    }

    public function test_admin_can_simulate_successful_purchase_and_update_subscription(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/payments/simulate', [
            'workspace_id' => $this->workspace->id,
            'gateway_code' => 'iyzico',
            'plan_name' => 'agency',
            'amount' => 1499.00,
            'scenario' => 'success',
            'customer_name' => 'John Doe',
            'customer_email' => 'john@acme.com',
            'card_brand' => 'Troy',
        ]);

        $response->assertSessionHas('success');

        $transaction = PaymentTransaction::where('workspace_id', $this->workspace->id)->first();
        $this->assertNotNull($transaction);
        $this->assertEquals('success', $transaction->status);
        $this->assertTrue($transaction->is_simulation);
        $this->assertEquals('agency', $transaction->plan_name);
        $this->assertEquals('1499.00', (string) $transaction->amount);
        $this->assertEquals('Troy', $transaction->card_brand);
        $this->assertStringStartsWith('TXN-SIM-', $transaction->transaction_id);

        // Verify Workspace subscription was activated to 'agency'
        $subscription = Subscription::where('workspace_id', $this->workspace->id)->first();
        $this->assertNotNull($subscription);
        $this->assertEquals('agency', $subscription->plan_name);
        $this->assertEquals('active', $subscription->status);

        // Verify Audit Log entry
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'billing.payment_simulated',
            'workspace_id' => $this->workspace->id,
        ]);
    }

    public function test_admin_can_simulate_failed_purchase(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/payments/simulate', [
            'workspace_id' => $this->workspace->id,
            'gateway_code' => 'stripe',
            'plan_name' => 'pro',
            'scenario' => 'insufficient_funds',
        ]);

        $response->assertSessionHas('success');

        $transaction = PaymentTransaction::where('workspace_id', $this->workspace->id)
            ->where('simulation_scenario', 'insufficient_funds')
            ->first();

        $this->assertNotNull($transaction);
        $this->assertEquals('failed', $transaction->status);
        $this->assertStringContainsString('Yetersiz Bakiye', $transaction->error_message);
    }

    public function test_admin_can_simulate_refund(): void
    {
        $this->actingAs($this->admin)->post('/admin/payments/simulate', [
            'workspace_id' => $this->workspace->id,
            'gateway_code' => 'klarna',
            'plan_name' => 'pro',
            'amount' => 499.00,
            'scenario' => 'success',
        ]);

        $transaction = PaymentTransaction::where('workspace_id', $this->workspace->id)->first();
        $this->assertNotNull($transaction);

        $response = $this->actingAs($this->admin)->post("/admin/payments/transactions/{$transaction->id}/refund");
        $response->assertSessionHas('success');

        $transaction->refresh();
        $this->assertEquals('refunded', $transaction->status);
        $this->assertNotNull($transaction->response_payload['refunded_at']);
    }

    public function test_non_admin_cannot_access_or_simulate_payments(): void
    {
        $response = $this->actingAs($this->regularUser)->get('/admin?tab=payments');
        $response->assertForbidden();

        $postResponse = $this->actingAs($this->regularUser)->post('/admin/payments/simulate', [
            'gateway_code' => 'iyzico',
            'plan_name' => 'pro',
            'scenario' => 'success',
        ]);
        $postResponse->assertForbidden();
    }
}
