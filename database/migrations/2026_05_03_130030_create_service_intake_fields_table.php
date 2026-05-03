<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_intake_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_problem_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label_ar');
            $table->string('label_en')->nullable();
            $table->string('field_type');
            $table->json('options')->nullable();
            $table->string('placeholder_ar')->nullable();
            $table->string('placeholder_en')->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_intake_fields');
    }
};
