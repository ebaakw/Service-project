<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->timestamp('quote_accepted_at')->nullable()->after('arrived_at');
        });

        Schema::table('company_reviews', function (Blueprint $table): void {
            $table->foreignId('order_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->after('order_id')->constrained()->nullOnDelete();
            $table->unique('order_id');
        });
    }

    public function down(): void
    {
        Schema::table('company_reviews', function (Blueprint $table): void {
            $table->dropUnique(['order_id']);
            $table->dropConstrainedForeignId('technician_id');
            $table->dropConstrainedForeignId('order_id');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('quote_accepted_at');
        });
    }
};
