<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_price_lists', function (Blueprint $table): void {
            $table->string('discount_type')->nullable()->after('ends_at');
            $table->decimal('discount_value', 10, 3)->nullable()->after('discount_type');
        });

        Schema::table('company_price_items', function (Blueprint $table): void {
            $table->string('discount_type')->nullable()->after('minimum_price');
            $table->decimal('discount_value', 10, 3)->nullable()->after('discount_type');
        });
    }

    public function down(): void
    {
        Schema::table('company_price_items', function (Blueprint $table): void {
            $table->dropColumn(['discount_type', 'discount_value']);
        });

        Schema::table('company_price_lists', function (Blueprint $table): void {
            $table->dropColumn(['discount_type', 'discount_value']);
        });
    }
};
