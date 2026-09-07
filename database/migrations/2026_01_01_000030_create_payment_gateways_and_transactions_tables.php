<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->string('country_code', 10);
            $table->string('country_name', 100);
            $table->string('language_code', 10);
            $table->string('currency', 10)->default('USD');
            $table->string('icon', 100)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('mode', 20)->default('test'); // test, live
            $table->json('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['country_code', 'is_active']);
            $table->index('language_code');
        });

        Schema::create('payment_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id', 100)->unique();
            $table->foreignId('workspace_id')->nullable()->constrained('workspaces')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('gateway_code', 50);
            $table->string('gateway_name', 100);
            $table->string('plan_name', 50)->default('pro');
            $table->decimal('amount', 12, 2)->default(0.00);
            $table->string('currency', 10)->default('USD');
            $table->string('status', 30)->default('pending'); // success, failed, refunded, pending
            $table->boolean('is_simulation')->default(true);
            $table->string('simulation_scenario', 50)->nullable();
            $table->string('card_brand', 30)->nullable();
            $table->string('card_last_four', 10)->nullable();
            $table->string('customer_name', 150)->nullable();
            $table->string('customer_email', 150)->nullable();
            $table->text('error_message')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamps();

            $table->index(['status', 'is_simulation']);
            $table->index(['workspace_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payment_settings');
        Schema::dropIfExists('payment_gateways');
    }
};
