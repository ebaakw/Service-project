<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_price_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_price_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_problem_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->string('price_type');
            $table->decimal('price', 10, 3)->nullable();
            $table->decimal('minimum_price', 10, 3)->nullable();
            $table->decimal('inspection_fee', 10, 3)->nullable();
            $table->unsignedInteger('warranty_days')->nullable();
            $table->text('warranty_description_ar')->nullable();
            $table->text('warranty_description_en')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'company_price_list_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_price_items');
    }
};
