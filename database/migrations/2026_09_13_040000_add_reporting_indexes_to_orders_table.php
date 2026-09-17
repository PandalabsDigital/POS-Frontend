<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['status', 'completed_at']);
            $table->index(['user_id', 'completed_at']);
            $table->index(['payment_method', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'completed_at']);
            $table->dropIndex(['user_id', 'completed_at']);
            $table->dropIndex(['payment_method', 'completed_at']);
        });
    }
};
