<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes(); // "archived"
            $table->unique(['shop_id', 'name']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name', 150);
            $table->string('sku', 50);
            $table->string('barcode', 60)->nullable();
            $table->string('unit', 10); // pcs, kg, g, l, ml, bottle, pack, dozen
            $table->decimal('price', 12, 2);
            $table->decimal('cost_price', 12, 2)->nullable();
            $table->decimal('low_stock_threshold', 12, 3)->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('image_path')->nullable();
            $table->timestamps();
            $table->softDeletes(); // "archived" - never hard-deleted once sold
            $table->unique(['shop_id', 'sku']);
            $table->index(['shop_id', 'name']);
            $table->index(['shop_id', 'barcode']);
        });

        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 12, 3)->default(0);
            $table->decimal('low_stock_threshold', 12, 3)->nullable(); // branch override
            $table->timestamps();
            $table->unique(['branch_id', 'product_id']);
            $table->index(['shop_id', 'branch_id']);
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20); // opening | stock_in | stock_out | adjustment | sale | return
            $table->decimal('quantity_change', 12, 3);
            $table->decimal('quantity_before', 12, 3);
            $table->decimal('quantity_after', 12, 3);
            $table->string('reason', 255)->nullable();
            $table->nullableMorphs('reference');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['branch_id', 'product_id', 'created_at']);
            $table->index(['shop_id', 'type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
