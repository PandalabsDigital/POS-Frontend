<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('email')->nullable()->after('phone');
            $table->boolean('marketing_consent')->default(false)->after('email');
            $table->timestamp('marketing_consent_at')->nullable()->after('marketing_consent');
            $table->string('source', 32)->default('pos')->after('marketing_consent_at');
            $table->string('branch_name')->nullable()->after('source');
            $table->string('terminal_name')->nullable()->after('branch_name');
            $table->foreignId('created_by')->nullable()->after('terminal_name')->constrained('users')->nullOnDelete();
            $table->unsignedInteger('loyalty_points')->default(0)->after('created_by');
            $table->decimal('total_spent', 12, 2)->default(0)->after('loyalty_points');
            $table->unsignedInteger('visits_count')->default(0)->after('total_spent');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('customer_email')->nullable()->after('customer_phone');
            $table->string('customer_capture_status', 40)->nullable()->after('customer_email');
            $table->string('customer_capture_reason', 80)->nullable()->after('customer_capture_status');
            $table->string('customer_capture_note', 191)->nullable()->after('customer_capture_reason');
            $table->foreignId('customer_capture_user_id')->nullable()->after('customer_capture_note')->constrained('users')->nullOnDelete();
            $table->timestamp('customer_capture_at')->nullable()->after('customer_capture_user_id');
            $table->string('capture_source', 32)->nullable()->after('customer_capture_at');
            $table->string('capture_terminal')->nullable()->after('capture_source');
        });

        Schema::create('customer_capture_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 40);
            $table->string('reason', 80)->nullable();
            $table->string('note', 191)->nullable();
            $table->string('source', 32)->default('pos');
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_capture_events');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_capture_user_id');
            $table->dropColumn([
                'customer_email',
                'customer_capture_status',
                'customer_capture_reason',
                'customer_capture_note',
                'customer_capture_at',
                'capture_source',
                'capture_terminal',
            ]);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn([
                'email',
                'marketing_consent',
                'marketing_consent_at',
                'source',
                'branch_name',
                'terminal_name',
                'loyalty_points',
                'total_spent',
                'visits_count',
            ]);
        });
    }
};
