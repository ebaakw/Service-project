<?php

use App\Enums\PortfolioProjectReviewStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portfolio_projects', function (Blueprint $table): void {
            $table->string('review_status', 20)
                ->default(PortfolioProjectReviewStatus::Approved->value)
                ->after('is_active');
            $table->foreignId('reviewed_by_user_id')
                ->nullable()
                ->after('review_status')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by_user_id');
            $table->text('rejection_reason')->nullable()->after('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('portfolio_projects', function (Blueprint $table): void {
            $table->dropForeign(['reviewed_by_user_id']);
            $table->dropColumn([
                'review_status',
                'reviewed_by_user_id',
                'reviewed_at',
                'rejection_reason',
            ]);
        });
    }
};
