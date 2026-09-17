<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rules', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('country_code', 8);
            $table->string('tax_name');
            $table->string('tax_type');
            $table->string('classification', 32);
            $table->string('family')->nullable();
            $table->string('supply_scope', 16)->default('any');
            $table->decimal('rate', 8, 3)->default(0);
            $table->string('itc', 16)->default('na');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tax_rule_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_rule_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('rate', 8, 3)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('tax_settings', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 8)->default('IN');
            $table->boolean('tax_registered')->default(true);
            $table->string('tax_registration_number')->nullable();
            $table->string('tax_authority')->nullable();
            $table->boolean('tax_invoice_enabled')->default(true);
            $table->boolean('prices_include_tax')->default(false);
            $table->foreignId('default_tax_rule_id')->nullable()->constrained('tax_rules')->nullOnDelete();
            $table->string('supply_type', 16)->default('intra');
            $table->boolean('service_charge_enabled')->default(false);
            $table->decimal('service_charge_rate', 8, 3)->default(0);
            $table->boolean('service_charge_taxable')->default(false);
            $table->timestamps();
        });

        Schema::create('tax_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('country_code', 8)->nullable();
            $table->string('action');
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('tax_rule_id')->nullable()->after('is_active')->constrained()->nullOnDelete();
        });

        Schema::table('menu_items', function (Blueprint $table) {
            $table->foreignId('tax_rule_id')->nullable()->after('is_active')->constrained()->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('service_charge_amount', 10, 2)->default(0);
            $table->decimal('taxable_amount', 10, 2)->default(0);
            $table->string('tax_label')->nullable();
            $table->boolean('tax_applicable')->default(true);
            $table->json('tax_snapshot')->nullable();
            $table->string('payment_method', 32)->nullable();
            $table->string('country_code', 8)->nullable();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('tax_rule_code')->nullable();
            $table->decimal('taxable_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->json('tax_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['tax_rule_code', 'taxable_amount', 'tax_amount', 'tax_snapshot']);
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'discount_amount', 'service_charge_amount', 'taxable_amount', 'tax_label',
                'tax_applicable', 'tax_snapshot', 'payment_method', 'country_code',
            ]);
        });
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tax_rule_id');
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tax_rule_id');
        });
        Schema::dropIfExists('tax_audit_logs');
        Schema::dropIfExists('tax_settings');
        Schema::dropIfExists('tax_rule_components');
        Schema::dropIfExists('tax_rules');
    }
};
