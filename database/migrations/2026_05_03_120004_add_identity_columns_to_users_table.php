<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->ulid('ulid')->nullable()->unique()->after('id');
            $table->string('phone')->nullable()->unique()->after('email');
            $table->string('type')->default('customer')->after('password');
            $table->string('status')->default('active')->after('type');
            $table->string('avatar_path')->nullable()->after('status');
            $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->softDeletes();
        });

        foreach (DB::table('users')->orderBy('id')->cursor() as $user) {
            DB::table('users')->where('id', $user->id)->update([
                'ulid' => (string) Str::ulid(),
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->ulid('ulid')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'ulid',
                'phone',
                'type',
                'status',
                'avatar_path',
                'phone_verified_at',
                'last_login_at',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
            $table->string('password')->nullable(false)->change();
        });
    }
};
