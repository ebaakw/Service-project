<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->text('schedule_note')->nullable()->after('estimated_duration_minutes');
            $table->timestamp('schedule_updated_at')->nullable()->after('schedule_note');
            $table->foreignId('schedule_updated_by_user_id')
                ->nullable()
                ->after('schedule_updated_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->boolean('is_schedule_manual')->default(false)->after('schedule_updated_by_user_id');
        });

        Schema::create('order_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sender_role', 32);
            $table->string('type', 32)->default('text');
            $table->text('body');
            $table->timestamp('proposed_scheduled_at')->nullable();
            $table->unsignedInteger('proposed_duration_minutes')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_messages');

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('schedule_updated_by_user_id');
            $table->dropColumn([
                'schedule_note',
                'schedule_updated_at',
                'is_schedule_manual',
            ]);
        });
    }
};
