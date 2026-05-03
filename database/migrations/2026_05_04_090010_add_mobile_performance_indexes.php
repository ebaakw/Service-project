<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->index(['technician_id', 'status']);
        });

        Schema::table('technicians', function (Blueprint $table): void {
            $table->index(['company_id', 'availability_status']);
        });

        Schema::table('technician_service_categories', function (Blueprint $table): void {
            $table->index('service_category_id');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->index(['order_id', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex(['technician_id', 'status']);
        });

        Schema::table('technicians', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'availability_status']);
        });

        Schema::table('technician_service_categories', function (Blueprint $table): void {
            $table->dropIndex(['service_category_id']);
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex(['order_id', 'payment_status']);
        });
    }
};
