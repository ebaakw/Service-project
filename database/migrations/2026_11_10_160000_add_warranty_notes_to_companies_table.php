<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->text('warranty_note_ar')->nullable()->after('description_en');
            $table->text('warranty_note_en')->nullable()->after('warranty_note_ar');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['warranty_note_ar', 'warranty_note_en']);
        });
    }
};
