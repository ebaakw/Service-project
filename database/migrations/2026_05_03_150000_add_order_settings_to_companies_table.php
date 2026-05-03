<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->decimal('default_inspection_fee', 10, 3)->nullable()->after('is_featured');
            $table->boolean('allow_final_invoice_without_items')->default(false)->after('default_inspection_fee');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['default_inspection_fee', 'allow_final_invoice_without_items']);
        });
    }
};
