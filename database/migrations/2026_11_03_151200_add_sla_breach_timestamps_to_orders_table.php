<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->timestamp('accept_sla_breached_at')->nullable()->after('assigned_at');
            $table->timestamp('arrival_sla_breached_at')->nullable()->after('arrived_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['accept_sla_breached_at', 'arrival_sla_breached_at']);
        });
    }
};
