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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_platform_admin')->default(false)->after('password');
            $table->text('two_factor_secret')->nullable()->after('is_platform_admin');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            $table->unsignedBigInteger('current_workspace_id')->nullable()->after('two_factor_confirmed_at');
        });

        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('personal_team')->default(true);
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->integer('retention_days')->default(90);
            $table->timestamps();
        });

        Schema::create('workspace_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('viewer'); // owner, admin, specialist, viewer
            $table->timestamps();

            $table->unique(['workspace_id', 'user_id']);
        });

        Schema::create('workspace_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('email');
            $table->string('role')->default('viewer');
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['workspace_id', 'email']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->nullable()->constrained('workspaces')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('resource_type')->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['workspace_id', 'action']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('workspace_invitations');
        Schema::dropIfExists('workspace_users');
        Schema::dropIfExists('workspaces');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_platform_admin',
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
                'current_workspace_id',
            ]);
        });
    }
};
