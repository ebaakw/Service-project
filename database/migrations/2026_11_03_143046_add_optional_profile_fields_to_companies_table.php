<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->json('contacts')->nullable()->after('email');
            $table->text('address')->nullable()->after('contacts');
            $table->text('working_hours')->nullable()->after('address');
            $table->string('iban')->nullable()->after('working_hours');
            $table->string('bank_name')->nullable()->after('iban');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['contacts', 'address', 'working_hours', 'iban', 'bank_name']);
        });
    }
};
