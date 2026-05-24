<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_price_list_service_category', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_price_list_id');
            $table->foreignId('service_category_id');
            $table->timestamps();

            $table->foreign('company_price_list_id', 'cplsc_price_list_fk')
                ->references('id')
                ->on('company_price_lists')
                ->cascadeOnDelete();
            $table->foreign('service_category_id', 'cplsc_category_fk')
                ->references('id')
                ->on('service_categories')
                ->cascadeOnDelete();
            $table->unique(['company_price_list_id', 'service_category_id'], 'price_list_category_unique');
        });

        DB::table('company_price_items')
            ->select('company_price_list_id', 'service_category_id')
            ->whereNull('deleted_at')
            ->distinct()
            ->orderBy('company_price_list_id')
            ->chunk(500, function ($rows): void {
                $now = now();

                foreach ($rows as $row) {
                    DB::table('company_price_list_service_category')->updateOrInsert(
                        [
                            'company_price_list_id' => $row->company_price_list_id,
                            'service_category_id' => $row->service_category_id,
                        ],
                        [
                            'created_at' => $now,
                            'updated_at' => $now,
                        ],
                    );
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_price_list_service_category');
    }
};
