<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('technicians', function (Blueprint $table) {
            $table->boolean('is_accepting_orders')->default(true)->after('availability_status');
            $table->unsignedInteger('estimated_job_duration_minutes')->nullable()->after('is_accepting_orders');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('queue_position')->nullable()->after('assigned_at');
            $table->unsignedInteger('estimated_duration_minutes')->nullable()->after('queue_position');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['queue_position', 'estimated_duration_minutes']);
        });

        Schema::table('technicians', function (Blueprint $table) {
            $table->dropColumn(['is_accepting_orders', 'estimated_job_duration_minutes']);
        });
    }
};
