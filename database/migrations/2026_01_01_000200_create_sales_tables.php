<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-branch, per-day counter used to generate gap-free invoice numbers under row lock.
        Schema::create('invoice_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('scope', 20); // e.g. 20261109 or RET-20261109
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
            $table->unique(['branch_id', 'scope']);
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('cashier_id')->constrained('users')->restrictOnDelete();
            $table->string('invoice_no', 40)->unique();
            $table->uuid('idempotency_key')->unique();
            $table->string('customer_name', 120)->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->string('discount_type', 10)->nullable(); // fixed | percent
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->decimal('refunded_total', 12, 2)->default(0);
            $table->string('status', 20)->default('completed'); // completed | partially_returned | returned
            $table->string('notes', 255)->nullable();
            $table->timestamp('sold_at');
            $table->timestamps();
            $table->index(['shop_id', 'sold_at']);
            $table->index(['branch_id', 'sold_at']);
            $table->index(['cashier_id', 'sold_at']);
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name', 150); // snapshot
            $table->string('sku', 50);           // snapshot
            $table->string('unit', 10);          // snapshot
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_price', 12, 2); // snapshot
            $table->decimal('cost_price', 12, 2)->nullable(); // snapshot
            $table->decimal('line_total', 12, 2);
            $table->decimal('returned_quantity', 12, 3)->default(0);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->string('method', 20)->default('cash');
            $table->decimal('amount', 12, 2);          // amount applied to the bill
            $table->decimal('amount_tendered', 12, 2); // cash handed over
            $table->decimal('change_due', 12, 2)->default(0);
            $table->string('reference', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('sale_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('return_no', 40)->unique();
            $table->decimal('refund_amount', 12, 2);
            $table->string('reason', 255);
            $table->timestamps();
            $table->index(['shop_id', 'created_at']);
            $table->index(['branch_id', 'created_at']);
        });

        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->decimal('amount', 12, 2);
            $table->boolean('restocked')->default(true);
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 60)->index();
            $table->nullableMorphs('subject');
            $table->string('description', 255);
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['shop_id', 'created_at']);
        });

        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('filename', 190)->unique();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('checksum', 64)->nullable();
            $table->string('status', 20)->default('completed');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes', 255)->nullable();
            $table->timestamp('restore_tested_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['backups', 'activity_logs', 'sale_return_items', 'sale_returns', 'payments', 'sale_items', 'sales', 'invoice_sequences'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
