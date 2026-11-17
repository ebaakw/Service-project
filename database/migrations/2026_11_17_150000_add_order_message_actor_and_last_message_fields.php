<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_messages', function (Blueprint $table): void {
            $table->foreignId('actor_user_id')
                ->nullable()
                ->after('sender_user_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('actor_context', 32)
                ->default('technician')
                ->after('actor_user_id');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->timestamp('last_message_at')->nullable()->after('is_schedule_manual');
            $table->string('last_message_preview', 160)->nullable()->after('last_message_at');
            $table->string('last_message_sender_role', 32)->nullable()->after('last_message_preview');
            $table->index(['company_id', 'last_message_at']);
        });

        // Backfill actor fields from existing rows (chunked).
        DB::table('order_messages')
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    $context = match ((string) $row->sender_role) {
                        'customer' => 'customer',
                        'system' => 'technician',
                        default => 'technician',
                    };

                    DB::table('order_messages')
                        ->where('id', $row->id)
                        ->update([
                            'actor_user_id' => $row->sender_user_id,
                            'actor_context' => $context,
                        ]);
                }
            });

        // Backfill last_message_* from latest message per order.
        $latestIds = DB::table('order_messages')
            ->selectRaw('MAX(id) as id')
            ->groupBy('order_id')
            ->pluck('id');

        foreach ($latestIds->chunk(200) as $chunk) {
            $messages = DB::table('order_messages')->whereIn('id', $chunk)->get();
            foreach ($messages as $message) {
                DB::table('orders')
                    ->where('id', $message->order_id)
                    ->update([
                        'last_message_at' => $message->created_at,
                        'last_message_preview' => mb_substr((string) $message->body, 0, 160),
                        'last_message_sender_role' => $message->sender_role,
                    ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'last_message_at']);
            $table->dropColumn([
                'last_message_at',
                'last_message_preview',
                'last_message_sender_role',
            ]);
        });

        Schema::table('order_messages', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('actor_user_id');
            $table->dropColumn('actor_context');
        });
    }
};
