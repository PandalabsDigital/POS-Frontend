<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('dimension', 20);
            $table->decimal('to_base', 16, 8)->default(1);
            $table->boolean('is_custom')->default(false);
            $table->timestamps();
        });

        Schema::create('inventory_categories', function (Blueprint $table) {
            $table->id();
            $table->string('group_name');
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['group_name', 'name']);
        });

        Schema::create('inventory_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 40)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('inventory_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('payment_terms')->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku', 64)->nullable()->unique();
            $table->string('barcode', 64)->nullable();
            $table->foreignId('inventory_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subcategory')->nullable();
            $table->foreignId('stock_unit_id')->constrained('inventory_units');
            $table->foreignId('purchase_unit_id')->constrained('inventory_units');
            $table->decimal('purchase_to_stock_factor', 16, 6)->default(1);
            $table->decimal('minimum_stock', 14, 4)->default(0);
            $table->decimal('maximum_stock', 14, 4)->nullable();
            $table->decimal('reorder_level', 14, 4)->nullable();
            $table->decimal('reorder_quantity', 14, 4)->nullable();
            $table->decimal('average_cost', 12, 4)->default(0);
            $table->decimal('last_purchase_cost', 12, 4)->default(0);
            $table->foreignId('preferred_supplier_id')->nullable()->constrained('inventory_suppliers')->nullOnDelete();
            $table->string('storage_location')->nullable();
            $table->boolean('track_expiry')->default(false);
            $table->boolean('track_batches')->default(false);
            $table->string('tax_category')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_finished_good')->default(false);
            $table->timestamps();
            $table->index(['is_active', 'name']);
        });

        Schema::create('inventory_item_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_supplier_id')->constrained()->cascadeOnDelete();
            $table->decimal('last_cost', 12, 4)->nullable();
            $table->timestamps();
            $table->unique(['inventory_item_id', 'inventory_supplier_id'], 'inv_item_supplier_unique');
        });

        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_location_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 14, 4)->default(0);
            $table->decimal('average_cost', 12, 4)->default(0);
            $table->decimal('minimum_stock', 14, 4)->nullable();
            $table->decimal('maximum_stock', 14, 4)->nullable();
            $table->decimal('par_level', 14, 4)->nullable();
            $table->decimal('reorder_quantity', 14, 4)->nullable();
            $table->timestamps();
            $table->unique(['inventory_item_id', 'inventory_location_id'], 'inv_balance_item_location');
        });

        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_location_id')->constrained()->cascadeOnDelete();
            $table->string('batch_number')->nullable();
            $table->date('manufactured_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->decimal('quantity', 14, 4)->default(0);
            $table->decimal('unit_cost', 12, 4)->default(0);
            $table->timestamps();
            $table->index(['inventory_item_id', 'expires_at']);
        });

        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40);
            $table->string('reference_type', 40)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_number')->nullable();
            $table->decimal('quantity_in', 14, 4)->default(0);
            $table->decimal('quantity_out', 14, 4)->default(0);
            $table->decimal('balance_after', 14, 4)->default(0);
            $table->decimal('unit_cost', 12, 4)->default(0);
            $table->decimal('total_value', 12, 4)->default(0);
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['inventory_item_id', 'occurred_at']);
            $table->index(['type', 'occurred_at']);
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('inventory_purchases', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->nullable();
            $table->date('invoice_date')->nullable();
            $table->date('received_date');
            $table->foreignId('inventory_supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inventory_location_id')->constrained();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('status', 20)->default('received');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('inventory_purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('inventory_units');
            $table->decimal('quantity', 14, 4);
            $table->decimal('stock_quantity', 14, 4);
            $table->decimal('unit_cost', 12, 4);
            $table->decimal('tax_amount', 12, 4)->default(0);
            $table->decimal('discount_amount', 12, 4)->default(0);
            $table->decimal('line_total', 12, 4);
            $table->string('batch_number')->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('output_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->string('name');
            $table->string('type', 20)->default('sale');
            $table->decimal('yield_quantity', 14, 4)->default(1);
            $table->foreignId('yield_unit_id')->nullable()->constrained('inventory_units')->nullOnDelete();
            $table->decimal('waste_percent', 8, 2)->default(0);
            $table->text('instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });

        Schema::create('inventory_recipe_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_recipe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 14, 4);
            $table->foreignId('unit_id')->constrained('inventory_units');
            $table->decimal('waste_percent', 8, 2)->default(0);
            $table->boolean('is_essential')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('inventory_wastage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_location_id')->constrained();
            $table->foreignId('unit_id')->constrained('inventory_units');
            $table->decimal('quantity', 14, 4);
            $table->decimal('stock_quantity', 14, 4);
            $table->decimal('cost', 12, 4)->default(0);
            $table->string('reason', 40);
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('occurred_at');
            $table->timestamps();
        });

        Schema::create('inventory_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_location_id')->constrained('inventory_locations');
            $table->foreignId('to_location_id')->constrained('inventory_locations');
            $table->string('status', 20)->default('completed');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('inventory_units');
            $table->decimal('quantity', 14, 4);
            $table->decimal('stock_quantity', 14, 4);
            $table->timestamps();
        });

        Schema::create('inventory_stocktakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_location_id')->constrained();
            $table->string('status', 20)->default('draft');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_stocktake_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_stocktake_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('expected_quantity', 14, 4)->default(0);
            $table->decimal('counted_quantity', 14, 4)->nullable();
            $table->decimal('difference', 14, 4)->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 40);
            $table->foreignId('inventory_item_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('old_quantity', 14, 4)->nullable();
            $table->decimal('new_quantity', 14, 4)->nullable();
            $table->string('reason')->nullable();
            $table->string('reference')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('inventory_posted_at')->nullable();
            $table->timestamp('inventory_reversed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['inventory_posted_at', 'inventory_reversed_at']);
        });

        Schema::dropIfExists('inventory_audit_logs');
        Schema::dropIfExists('inventory_stocktake_items');
        Schema::dropIfExists('inventory_stocktakes');
        Schema::dropIfExists('inventory_transfer_items');
        Schema::dropIfExists('inventory_transfers');
        Schema::dropIfExists('inventory_wastage');
        Schema::dropIfExists('inventory_recipe_items');
        Schema::dropIfExists('inventory_recipes');
        Schema::dropIfExists('inventory_purchase_items');
        Schema::dropIfExists('inventory_purchases');
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('inventory_batches');
        Schema::dropIfExists('inventory_balances');
        Schema::dropIfExists('inventory_item_suppliers');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('inventory_suppliers');
        Schema::dropIfExists('inventory_locations');
        Schema::dropIfExists('inventory_categories');
        Schema::dropIfExists('inventory_units');
    }
};
