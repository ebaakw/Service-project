<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('technicians', function (Blueprint $table) {
            $table->string('review_status')->default('pending')->after('is_active');
            $table->foreignId('reviewed_by_user_id')->nullable()->after('review_status')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by_user_id');
            $table->text('rejection_reason')->nullable()->after('reviewed_at');

            $table->index('review_status');
            $table->index(['company_id', 'review_status']);
        });

        // Backfill existing technicians to approved to avoid breaking current production data.
        DB::table('technicians')->update([
            'review_status' => 'approved',
        ]);
    }

    public function down(): void
    {
        Schema::table('technicians', function (Blueprint $table) {
            $table->dropIndex(['review_status']);
            $table->dropIndex(['company_id', 'review_status']);

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

