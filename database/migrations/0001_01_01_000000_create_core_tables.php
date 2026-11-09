<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Core tenancy tables: shops, users, branches, branch assignments,
 * password resets and sessions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 160)->unique();
            $table->string('owner_name', 120);
            $table->string('email', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('city', 80)->nullable();
            $table->string('status', 20)->default('active')->index(); // active | suspended
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('name', 120);
            $table->string('email', 150)->unique();
            $table->string('phone', 30)->nullable();
            $table->string('role', 30)->index(); // super_admin | shop_admin | branch_manager | cashier
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['shop_id', 'role']);
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->restrictOnDelete();
            $table->string('name', 120);
            $table->string('code', 10);
            $table->string('address', 255)->nullable();
            $table->string('phone', 30)->nullable();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['shop_id', 'code']);
            $table->unique(['shop_id', 'name']);
        });

        Schema::create('branch_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['branch_id', 'user_id']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('shop_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('currency', 10)->default('PKR');
            $table->string('logo_path')->nullable();
            $table->string('receipt_header', 255)->nullable();
            $table->string('receipt_footer', 255)->nullable();
            $table->string('invoice_prefix', 10)->default('INV');
            $table->boolean('tax_enabled')->default(false);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('default_low_stock_threshold', 12, 3)->default(10);
            $table->decimal('max_discount_percent', 5, 2)->default(50);
            $table->timestamps();
        });

        Schema::create('signup_requests', function (Blueprint $table) {
            $table->id();
            $table->string('shop_name', 150);
            $table->string('owner_name', 120);
            $table->string('email', 150);
            $table->string('phone', 30);
            $table->string('city', 80)->nullable();
            $table->text('message')->nullable();
            $table->string('status', 20)->default('pending')->index(); // pending | approved | rejected
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('shop_id')->nullable()->constrained()->nullOnDelete();
            $table->string('review_note', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signup_requests');
        Schema::dropIfExists('shop_settings');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('branch_user');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('users');
        Schema::dropIfExists('shops');
    }
};
