<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_problems', function (Blueprint $table) {
            $table->string('icon_path')->nullable()->after('description_en');
        });
    }

    public function down(): void
    {
        Schema::table('service_problems', function (Blueprint $table) {
            $table->dropColumn('icon_path');
        });
    }
};
