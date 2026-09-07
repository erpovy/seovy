<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\PaymentGateway;
use App\Models\PaymentSetting;
use App\Models\PaymentTransaction;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanManagementAndCheckoutTest extends TestCase
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
            'name' => 'SEO Specialist',
            'email' => 'seo@seovy.test',
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

        // Seed default gateways and test settings
        PaymentGateway::ensureDefaultGateways();
        PaymentSetting::set('test_mode', true);
        PaymentSetting::set('currency', 'TRY');

        // Seed default plans
        SubscriptionPlan::ensureDefaultPlans();
    }

    public function test_default_plans_are_seeded(): void
    {
        $this->assertEquals(3, SubscriptionPlan::count());
        $this->assertDatabaseHas('subscription_plans', ['code' => 'free', 'price' => 0]);
        $this->assertDatabaseHas('subscription_plans', ['code' => 'pro', 'price' => 499]);
        $this->assertDatabaseHas('subscription_plans', ['code' => 'agency', 'price' => 1499]);
    }

    public function test_admin_can_view_plans_tab(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin?tab=plans');
        $response->assertOk();
    }

    public function test_admin_can_update_plan_price_and_quotas(): void
    {
        $proPlan = SubscriptionPlan::where('code', 'pro')->first();

        $response = $this->actingAs($this->admin)->patch("/admin/plans/{$proPlan->id}", [
            'name' => 'Pro Plus',
            'price' => 599,
            'currency' => 'TRY',
            'limits' => [
                'max_projects' => 15,
                'max_pages_monthly' => 30000,
                'max_keywords' => 250,
                'team_members' => 5,
            ],
            'features' => ['advanced_crawler', 'api_access'],
            'is_active' => true,
            'is_popular' => true,
        ]);

        $response->assertSessionHas('success');
        $proPlan->refresh();

        $this->assertEquals('Pro Plus', $proPlan->name);
        $this->assertEquals(599, $proPlan->price);
        $this->assertEquals(15, $proPlan->limits['max_projects']);
        $this->assertEquals(30000, $proPlan->limits['max_pages_monthly']);
        $this->assertTrue($proPlan->is_popular);
    }

    public function test_admin_can_create_custom_plan(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/plans', [
            'name' => 'Enterprise Custom',
            'code' => 'enterprise',
            'price' => 2999,
            'currency' => 'TRY',
            'limits' => [
                'max_projects' => 100,
                'max_pages_monthly' => 500000,
                'max_keywords' => 5000,
                'team_members' => 50,
            ],
            'features' => ['white_label', 'dedicated_support', 'unlimited_crawls'],
            'is_active' => true,
            'is_popular' => false,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('subscription_plans', ['code' => 'enterprise', 'price' => 2999]);
    }

    public function test_regular_user_cannot_access_admin_plan_routes(): void
    {
        $response = $this->actingAs($this->regularUser)->get('/admin?tab=plans');
        $response->assertForbidden();

        $response = $this->actingAs($this->regularUser)->post('/admin/plans', [
            'name' => 'Hacker Plan',
            'code' => 'hacker',
            'price' => 0,
            'currency' => 'TRY',
        ]);
        $response->assertForbidden();
    }

    public function test_user_can_view_billing_page_with_dynamic_plans(): void
    {
        $response = $this->actingAs($this->regularUser)->get('/billing');
        $response->assertOk();
    }

    public function test_user_can_purchase_pro_plan_in_test_mode(): void
    {
        $response = $this->actingAs($this->regularUser)->post('/billing/checkout', [
            'plan' => 'pro',
            'gateway_code' => 'iyzico',
            'scenario' => 'success',
            'card_brand' => 'Troy',
            'card_last_four' => '1234',
        ]);

        $response->assertSessionHas('success');

        // Verify subscription updated
        $subscription = $this->workspace->subscription()->first();
        $this->assertNotNull($subscription);
        $this->assertEquals('pro', $subscription->plan_name);
        $this->assertEquals('active', $subscription->status);

        // Verify limits correspond to pro plan
        $limits = $subscription->getLimits();
        $this->assertEquals(5, $limits['max_projects']);
        $this->assertEquals(20000, $limits['max_pages_monthly']);

        // Verify PaymentTransaction created
        $this->assertDatabaseHas('payment_transactions', [
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->regularUser->id,
            'status' => 'success',
            'is_simulation' => true,
        ]);

        // Verify AuditLog recorded
        $this->assertDatabaseHas('audit_logs', [
            'workspace_id' => $this->workspace->id,
            'action' => 'billing.payment_simulated',
        ]);
    }

    public function test_failed_checkout_scenario_does_not_upgrade_plan(): void
    {
        // Initial subscription is free
        $sub = $this->workspace->subscription()->create([
            'plan_name' => 'free',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->regularUser)->post('/billing/checkout', [
            'plan' => 'agency',
            'gateway_code' => 'stripe',
            'scenario' => 'bank_declined',
            'card_brand' => 'Visa',
            'card_last_four' => '9999',
        ]);

        $response->assertSessionHasErrors(['payment']);

        // Verify subscription remains free
        $sub->refresh();
        $this->assertEquals('free', $sub->plan_name);

        // Verify failed transaction recorded
        $this->assertDatabaseHas('payment_transactions', [
            'workspace_id' => $this->workspace->id,
            'status' => 'failed',
            'simulation_scenario' => 'bank_declined',
        ]);
    }

    public function test_user_can_downgrade_to_free_plan(): void
    {
        $this->workspace->subscription()->create([
            'plan_name' => 'pro',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->regularUser)->post('/billing/checkout', [
            'plan' => 'free',
        ]);

        $response->assertSessionHas('success');

        $sub = $this->workspace->subscription()->first();
        $this->assertEquals('free', $sub->plan_name);
    }

    public function test_admin_can_view_and_filter_sales_log(): void
    {
        // Execute a purchase first
        $this->actingAs($this->regularUser)->post('/billing/checkout', [
            'plan' => 'agency',
            'gateway_code' => 'iyzico',
            'scenario' => 'success',
            'card_brand' => 'Mastercard',
            'card_last_four' => '5678',
        ]);

        // Admin checks sales tab
        $response = $this->actingAs($this->admin)->get('/admin?tab=sales');
        $response->assertOk();

        // Admin filters sales by plan
        $filterResponse = $this->actingAs($this->admin)->get('/admin?tab=sales&sales_plan=agency');
        $filterResponse->assertOk();
    }
}
