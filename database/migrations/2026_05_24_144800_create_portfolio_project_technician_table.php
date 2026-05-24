<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_project_technician', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('portfolio_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technician_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['portfolio_project_id', 'technician_id'], 'portfolio_project_technician_unique');
        });

        DB::table('portfolio_projects')
            ->whereNotNull('technician_id')
            ->orderBy('id')
            ->select(['id', 'technician_id'])
            ->chunkById(500, function ($projects): void {
                $now = now();
                $rows = $projects->map(fn ($project): array => [
                    'portfolio_project_id' => $project->id,
                    'technician_id' => $project->technician_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('portfolio_project_technician')->insertOrIgnore($rows);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_project_technician');
    }
};
